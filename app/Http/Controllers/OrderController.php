<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Queue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PushNotificationController;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['items.menu', 'queue', 'user']);
        
        if (Auth::check()) {
            $user = Auth::user();
            // Check if user is a seller
            $store = \App\Models\Store::where('user_id', $user->id)->first();
            
            if ($store) {
                // Sellers see orders for their store
                $query->where('store_id', $store->id);
            } else {
                // Customers see only their own orders
                $query->where('user_id', $user->id);
            }
        }
        
        // Allow explicit filtering by specific store_id or user_id if provided
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

        $validated = $request->validate([
            'status' => 'required|string',
            'estimated_finish_time' => 'nullable|integer'
        ]);

        $newStatus = $validated['status'];

        // Logic for handling the specific preparing status flow
        if ($newStatus === 'preparing' && $request->has('estimated_finish_time')) {
            $order->estimated_finish_time = now()->addMinutes($validated['estimated_finish_time']);
        }

        $oldStatus = $order->status;
        $order->status = $newStatus;
        $order->save();
        
        // Send push notification to customer on status changes
        if ($newStatus === 'preparing' && $oldStatus !== 'preparing') {
            PushNotificationController::sendToUser(
                $order->user_id,
                'Order Accepted! 🎉',
                'Your order #' . $order->id . ' is now being prepared. We\'ll notify you when it\'s ready!',
                ['order_id' => $order->id, 'status' => 'preparing', 'route' => '/customer/queue']
            );
        } elseif ($newStatus === 'ready' && $oldStatus !== 'ready') {
            PushNotificationController::sendToUser(
                $order->user_id,
                'Order Ready! ✅',
                'Your order #' . $order->id . ' is ready for pickup. Please come to the counter!',
                ['order_id' => $order->id, 'status' => 'ready', 'route' => '/customer/queue']
            );
        } elseif ($newStatus === 'cancelled' && $oldStatus !== 'cancelled') {
            PushNotificationController::sendToUser(
                $order->user_id,
                'Order Cancelled',
                'Your order #' . $order->id . ' has been cancelled.',
                ['order_id' => $order->id, 'status' => 'cancelled', 'route' => '/customer/queue']
            );
        }
        
        // Map order status to queue status
        $queueStatus = 'waiting';
        switch ($newStatus) {
            case 'preparing': $queueStatus = 'processing'; break;
            case 'ready':     $queueStatus = 'processing'; break; 
            case 'completed': $queueStatus = 'completed'; break;
            case 'cancelled': $queueStatus = 'cancelled'; break;
            default:          $queueStatus = 'waiting'; break;
        }
        
        Queue::where('order_id', $order->id)->update(['status' => $queueStatus]);

        return response()->json(['data' => $order->load(['items.menu', 'queue', 'user']), 'message' => 'Order status updated to ' . $newStatus]);
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

    public function destroy($id)
    {
        try {
            $order = Order::find($id);
            if (!$order) {
                return response()->json(['message' => 'Order not found'], 404);
            }

            $user = Auth::user();
            
            // Check if user is the customer who placed the order
            $isCustomer = $order->user_id == $user->id;
            
            // Check if user is a seller and owns the store for this order
            $store = \App\Models\Store::where('user_id', $user->id)->first();
            $isSeller = $store && $order->store_id == $store->id;

            if (!$isCustomer && !$isSeller) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Perform a HARD DELETE (force delete) to ensure data is completely removed
            // and cascades to related items via DB constraints (ON DELETE CASCADE)
            $order->forceDelete();
            
            return response()->json(['message' => 'Order history deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete order', 'error' => $e->getMessage()], 500);
        }
    }
}
