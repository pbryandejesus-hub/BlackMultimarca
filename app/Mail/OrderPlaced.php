<?php

namespace App\Mail;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmación de compra con el detalle del pedido y los datos para transferir.
 */
class OrderPlaced extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Recibimos tu pedido {$this->order->number}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.placed',
            with: [
                'items' => $this->order->items,
                'settings' => Setting::allValues(),
            ],
        );
    }
}
