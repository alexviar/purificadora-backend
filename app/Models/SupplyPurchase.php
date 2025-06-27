<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyPurchase extends Model
{
    protected $table = 'supply_purchases';

    protected $fillable = [
        'cliente_id',
        'status_id',
        'precio_total',
        'metodo_pago',
        'payment_intent_id'
    ];

    public function cliente()
    {
        return $this->belongsTo(User::class, 'cliente_id');
    }

    public function detalles()
    {
        return $this->hasMany(SupplyPurchaseDetail::class, 'compra_insumo_id');
    }

    public function status()
    {
        return $this->belongsTo(PurchaseStatus::class, 'status_id');
    }
}
