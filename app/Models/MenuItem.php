<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MenuItem extends Model
{
    protected $table = 'menu_items';
    public $timestamps = true;

    protected $fillable = [
        'store_id',
        'name',
        'price',
        'estimated_time',
        'category',
        'description',
        'image_url',
        'is_available',
        'stock',
    ];
}
