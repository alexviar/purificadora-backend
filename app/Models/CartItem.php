<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    protected $fillable = ['cart_id', 'item_type', 'item_id', 'cantidad', 'precio_unitario'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function supply()
    {
        return $this->item_type == 'supply'
            ? $this->belongsTo(Supply::class, 'item_id')
            : null;
    }
}
