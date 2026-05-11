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
        $menu = MenuItem::findOrFail($id);
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
        $menu = MenuItem::findOrFail($id);
        $menu->delete();
        return response()->json(['message' => 'Menu item deleted successfully']);
    }
}
