<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{
    protected $table = 'stores';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'store_name',
        'location',
        'phone',
        'is_open'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function menuItems()
    {
        return $this->hasMany(MenuItem::class);
    }
}
