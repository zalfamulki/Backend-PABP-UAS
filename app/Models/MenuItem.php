<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MenuItem extends Model
{
    use SoftDeletes;
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
    public function store()
    {
        return $this->belongsTo(Store::class);
    }
}
