<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplyPurchaseDetail extends Model
{
    protected $table = 'supply_purchase_details';

    protected $fillable = [
        'compra_insumo_id',
        'status_id',
        'tipo',
        'item_id',
        'cantidad',
        'precio_unitario'
    ];

    public function supplyPurchase()
    {
        return $this->belongsTo(SupplyPurchase::class, 'compra_insumo_id');
    }

    public function status()
    {
        return $this->belongsTo(PurchaseDetailStatus::class, 'status_id');
    }
}
