<?php

namespace App\Http\Controllers;

use App\Models\MenuItem;
use Illuminate\Http\Request;

class MenuItemController extends Controller
{
    public function index(Request $request)
    {
        $query = MenuItem::with('store');
        if ($request->has('store_id')) {
            $query->where('store_id', $request->store_id);
        }
        $menus = $query->get();
        return response()->json(['data' => $menus]);
    }

    public function store(Request $request)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $store = \App\Models\Store::where('user_id', $user->id)->firstOrFail();

        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'category' => 'required|string',
            'description' => 'nullable|string',
            'estimated_time' => 'nullable|integer',
            'image_url' => 'nullable|string',
            'is_available' => 'boolean',
            'stock' => 'nullable|integer',
        ]);

        $validated['store_id'] = $store->id;

        $menu = MenuItem::create($validated);
        // Load the store relationship for the response
        $menu->load('store');
        
        return response()->json(['data' => $menu, 'message' => 'Menu item created successfully'], 201);
    }

    public function update(Request $request, $id)
    {
        $user = \Illuminate\Support\Facades\Auth::user();
        $store = \App\Models\Store::where('user_id', $user->id)->first();
        
        $menu = MenuItem::findOrFail($id);

        // Security: Ensure the menu item belongs to the seller's store
        if ($store && $menu->store_id !== $store->id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized: You can only update items from your own store'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'string',
            'price' => 'numeric',
            'category' => 'string',
            'description' => 'nullable|string',
            'estimated_time' => 'nullable|integer',
            'image_url' => 'nullable|string',
            'is_available' => 'boolean',
            'stock' => 'nullable|integer',
        ]);

        $menu->update($validated);
        return response()->json(['data' => $menu, 'message' => 'Menu item updated successfully']);
    }

    public function destroy($id)
    {
        try {
            $user = \Illuminate\Support\Facades\Auth::user();
            $store = \App\Models\Store::where('user_id', $user->id)->first();
            
            $menu = MenuItem::findOrFail($id);

            // Security: Ensure the menu item belongs to the seller's store
            if ($store && $menu->store_id !== $store->id) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unauthorized: You can only delete items from your own store'
                ], 403);
            }

            $menu->delete(); // This will now soft delete
            
            return response()->json([
                'status' => 'success',
                'message' => 'Menu item deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal menghapus menu: ' . $e->getMessage()
            ], 500);
        }
    }
}
