<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StatsController extends Controller
{
    public function revenue(Request $request)
    {
        $user = Auth::user();
        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {
            return response()->json(['message' => 'Seller store not found'], 404);
        }

        $totalRevenue = Order::where('store_id', $store->id)
            ->where('status', 'completed')
            ->withTrashed()
            ->sum('total_price');

        $completedCount = Order::where('store_id', $store->id)
            ->where('status', 'completed')
            ->withTrashed()
            ->count();

        return response()->json([
            'data' => [
                'total_revenue' => (float) $totalRevenue,
                'completed_count' => $completedCount,
            ]
        ]);
    }

    public function dashboard(Request $request)
    {
        $user = Auth::user();
        $store = Store::where('user_id', $user->id)->first();

        if (!$store) {
            return response()->json(['message' => 'Seller store not found'], 404);
        }

        $totalRevenue = Order::where('store_id', $store->id)
            ->where('status', 'completed')
            ->withTrashed()
            ->sum('total_price');

        $pendingOrders = Order::where('store_id', $store->id)
            ->where('status', 'pending')
            ->count();

        return response()->json([
            'data' => [
                'totalOrdersToday' => (int) $pendingOrders,
                'revenueToday' => (float) $totalRevenue,
                'pendingOrders' => (int) $pendingOrders,
                'peakHours' => [],
            ]
        ]);
    }
}
