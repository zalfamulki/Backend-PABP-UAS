<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $table = 'order_items';
    public $timestamps = false;

    protected $fillable = [
        'order_id',
        'menu_id',
        'quantity',
        'subtotal'
    ];

    public function menu()
    {
        return $this->belongsTo(MenuItem::class, 'menu_id');
    }
}
