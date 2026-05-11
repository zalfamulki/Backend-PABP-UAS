<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.menu', 'queue', 'user']);
        
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $orders = $query->get();
        return response()->json(['data' => $orders]);
    }

    public function show($id)
    {
        $order = Order::with(['items.menu', 'queue', 'user'])->find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json(['data' => $order]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'store_id' => 'required|integer',
            'total_price' => 'required|numeric',
            'notes' => 'nullable|string',
            'items' => 'required|array',
            'items.*.menu_id' => 'required|integer',
            'items.*.quantity' => 'required|integer',
            'items.*.subtotal' => 'required|numeric',
        ]);

        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => Auth::id(),
                'store_id' => $request->store_id,
                'total_price' => $request->total_price,
                'status' => 'pending',
                'notes' => $request->notes,
            ]);

            foreach ($request->items as $item) {
                $menuItem = \App\Models\MenuItem::find($item['menu_id']);
                
                if (!$menuItem || $menuItem->stock < $item['quantity']) {
                    throw new \Exception("Insufficient stock for item: " . ($menuItem ? $menuItem->name : "Unknown"));
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'menu_id' => $item['menu_id'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['subtotal']
                ]);

                // Decrement stock immediately
                $menuItem->decrement('stock', $item['quantity']);
            }

            // Create queue entry
            $lastQueue = Queue::where('store_id', $request->store_id)->max('queue_position');
            $nextPos = $lastQueue ? $lastQueue + 1 : 1;
            
            Queue::create([
                'order_id' => $order->id,
                'store_id' => $request->store_id,
                'queue_position' => $nextPos,
                'status' => 'waiting',
            ]);

            DB::commit();
            return response()->json(['data' => $order->load(['items.menu', 'queue', 'user']), 'message' => 'Order created and queued successfully'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to create order', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $order = Order::with('items')->find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->update($request->only(['status']));
        
        // If order status changes, update queue status
        if ($request->has('status')) {
            $queueStatus = 'waiting';
            switch ($request->status) {
                case 'preparing': $queueStatus = 'processing'; break;
                case 'pending':   $queueStatus = 'waiting'; break;
                case 'ready':     $queueStatus = 'processing'; break; // Assuming ready means ready for pickup
                case 'completed': $queueStatus = 'completed'; break;
                case 'cancelled': $queueStatus = 'cancelled'; break;
                default: $queueStatus = 'waiting'; break;
            }
            
            Queue::where('order_id', $order->id)->update(['status' => $queueStatus]);
        }

        return response()->json(['data' => $order->load(['items.menu', 'queue', 'user']), 'message' => 'Order updated successfully']);
    }

    public function cancel(Request $request, $id)
    {
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        $order->update(['status' => 'cancelled']);
        Queue::where('order_id', $order->id)->update(['status' => 'cancelled']);

        // Return stock
        foreach ($order->load('items')->items as $item) {
            \App\Models\MenuItem::where('id', $item->menu_id)->increment('stock', $item->quantity);
        }

        return response()->json(['data' => $order->load(['items.menu', 'queue', 'user']), 'message' => 'Order cancelled successfully']);
    }
}
