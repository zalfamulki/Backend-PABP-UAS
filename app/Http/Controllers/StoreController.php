<?php

namespace App\Http\Controllers;

use App\Models\Store;
use Illuminate\Http\Request;

class StoreController extends Controller
{
    public function index()
    {
        $stores = Store::all();
        return response()->json(['data' => $stores]);
    }

    public function show($id)
    {
        $store = Store::find($id);
        if (!$store) {
            return response()->json(['message' => 'Store not found'], 404);
        }
        return response()->json(['data' => $store]);
    }

    public function update(Request $request, $id)
    {
        $store = Store::findOrFail($id);
        
        $validated = $request->validate([
            'store_name' => 'string|max:255',
            'location' => 'string|max:255',
            'is_open' => 'boolean',
        ]);

        $store->update($validated);
        return response()->json(['data' => $store, 'message' => 'Store updated successfully']);
    }
}
