<?php

namespace App\Listeners;

use App\Events\PurchaseCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendPurchaseConfirmation implements ShouldQueue
{
    use InteractsWithQueue;

    /**
     * Maneja el evento PurchaseCompleted.
     *
     * @param  \App\Events\PurchaseCompleted  $event
     * @return void
     */
    public function handle(PurchaseCompleted $event)
    {
        $compra = $event->compra;
        // Envía una notificación al cliente utilizando el sistema de notificaciones de Laravel.
        $compra->cliente->notify(new \App\Notifications\PurchaseConfirmed($compra));
    }
}
