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
        
        // If the user is a seller, restrict to their store
        if (Auth::check()) {
            $user = Auth::user();
            $store = \App\Models\Store::where('user_id', $user->id)->first();
            if ($store) {
                $query->where('store_id', $store->id);
            }
        }
        
        // Allow filtering by specific store_id if provided (and user has access)
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
            'items' => 'required|array',
            'items.*.menu_id' => 'required|integer',
            'items.*.quantity' => 'required|integer',
            'items.*.subtotal' => 'required|numeric',
        ]);

        // Group items by store_id
        $itemsByStore = [];
        foreach ($request->items as $item) {
            $menuItem = \App\Models\MenuItem::find($item['menu_id']);
            if (!$menuItem) continue;
            
            $itemsByStore[$menuItem->store_id][] = [
                'menu_id' => $item['menu_id'],
                'quantity' => $item['quantity'],
                'subtotal' => $item['subtotal'],
                'menuItem' => $menuItem
            ];
        }

        DB::beginTransaction();
        try {
            $createdOrders = [];
            foreach ($itemsByStore as $storeId => $items) {
                $totalPrice = array_sum(array_column($items, 'subtotal'));
                
                $order = Order::create([
                    'user_id' => Auth::id(),
                    'store_id' => $storeId,
                    'total_price' => $totalPrice,
                    'status' => 'pending',
                    'notes' => $request->notes,
                ]);

                foreach ($items as $item) {
                    if ($item['menuItem']->stock < $item['quantity']) {
                        throw new \Exception("Insufficient stock for item: " . $item['menuItem']->name);
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'menu_id' => $item['menu_id'],
                        'quantity' => $item['quantity'],
                        'subtotal' => $item['subtotal']
                    ]);
                    $item['menuItem']->decrement('stock', $item['quantity']);
                }

                $lastQueue = Queue::where('store_id', $storeId)->max('queue_position');
                Queue::create([
                    'order_id' => $order->id,
                    'store_id' => $storeId,
                    'queue_position' => ($lastQueue ?? 0) + 1,
                    'status' => 'waiting',
                ]);
                
                $createdOrders[] = $order->load(['items.menu', 'queue', 'user']);
            }

            DB::commit();
            return response()->json(['data' => $createdOrders, 'message' => 'Orders created successfully'], 201);
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
