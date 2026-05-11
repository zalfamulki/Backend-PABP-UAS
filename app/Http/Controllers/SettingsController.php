<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Store;

class SettingsController extends Controller
{
    // Update Operasional Toko (Khusus Seller)
    public function updateOperational(Request $request)
    {
        $request->validate([
            'open_time' => 'required',
            'close_time' => 'required',
            'avg_waiting_time' => 'required|integer',
        ]);

        $store = Store::where('user_id', Auth::id())->firstOrFail();
        
        $store->update([
            'open_time' => $request->open_time,
            'close_time' => $request->close_time,
            'avg_waiting_time' => $request->avg_waiting_time,
        ]);

        return response()->json(['message' => 'Operational settings updated successfully', 'data' => $store]);
    }

    // Update Password (Common)
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        $user = Auth::user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password does not match'], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password updated successfully']);
    }
}
