<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Queue extends Model
{
    protected $table = 'queue';
    public $timestamps = true;

    protected $fillable = [
        'order_id',
        'store_id',
        'queue_position',
        'status',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
