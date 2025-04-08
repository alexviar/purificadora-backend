<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class PurchaseConfirmed extends Notification implements ShouldQueue
{
    use Queueable;

    protected $compra;

    /**
     * Create a new notification instance.
     *
     * @param  mixed  $compra
     */
    public function __construct($compra)
    {
        $this->compra = $compra;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->subject('Confirmación de Compra')
                    ->line('Tu compra ha sido confirmada.')
                    ->line('Total: $' . $this->compra->precio_total)
                    ->action('Ver Compra', url('/compras/' . $this->compra->id))
                    ->line('¡Gracias por tu compra!');
    }
}
