<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'store_id',
        'status',
        'total_price',
        'estimated_finish_time',
        'notes',
    ];

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function queue()
    {
        return $this->hasOne(Queue::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
