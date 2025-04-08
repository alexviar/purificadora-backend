<?php

namespace App\Events;

use App\Models\SuplyPurchase; // O SupplyPurchase, según tu modelo
use Illuminate\Queue\SerializesModels;

class PurchaseCompleted
{
    use SerializesModels;

    public $compra;

    /**
     * Crea una nueva instancia del evento.
     *
     * @param  \App\Models\SuplyPurchase  $compra
     */
    public function __construct(SuplyPurchase $compra)
    {
        $this->compra = $compra;
    }
}
