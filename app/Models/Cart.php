<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    protected $fillable = ['user_id', 'status_id'];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function status()
    {
        return $this->belongsTo(CartStatus::class, 'status_id');
    }
}
