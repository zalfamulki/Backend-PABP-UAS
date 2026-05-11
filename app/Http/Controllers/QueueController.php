<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function index()
    {
        $queues = Queue::whereNotIn('status', ['completed', 'cancelled'])
                       ->orderBy('queue_position', 'asc')
                       ->get();
        return response()->json(['data' => $queues]);
    }

    public function show($id)
    {
        $queue = Queue::find($id);
        if (!$queue) {
            return response()->json(['message' => 'Queue not found'], 404);
        }
        return response()->json(['data' => $queue]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'store_id' => 'required|integer',
            'queue_position' => 'required|integer',
            'status' => 'in:waiting,processing,completed'
        ]);

        $queue = Queue::create([
            'order_id' => $request->order_id,
            'store_id' => $request->store_id,
            'queue_position' => $request->queue_position,
            'status' => $request->status ?? 'waiting',
            'created_at' => now()
        ]);

        return response()->json(['data' => $queue, 'message' => 'Queue added successfully'], 201);
    }

    public function update(Request $request, $id)
    {
        $queue = Queue::find($id);
        if (!$queue) {
            return response()->json(['message' => 'Queue not found'], 404);
        }

        $queue->update($request->only(['status', 'queue_position']));
        return response()->json(['data' => $queue, 'message' => 'Queue updated successfully']);
    }
}
