<?php

namespace App\Mail;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso al cliente cuando el administrador cambia el estado de su pedido.
 */
class OrderStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Tu pedido {$this->order->number}: {$this->order->status->label()}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.orders.status-changed',
            with: [
                'items' => $this->order->items,
                'statusMessage' => $this->messageForStatus($this->order->status),
            ],
        );
    }

    private function messageForStatus(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::PendingPayment => 'Tu pedido está esperando la transferencia. Cuando la hagas, subí el comprobante desde la página del pedido.',
            OrderStatus::UnderReview => 'Recibimos tu comprobante y lo estamos revisando. Te avisamos apenas confirmemos el pago.',
            OrderStatus::Paid => '¡Confirmamos tu pago! Ya estamos preparando tu pedido.',
            OrderStatus::Shipped => $this->order->isShipping()
                ? 'Tu pedido ya está en camino. Pronto lo vas a recibir en la dirección que nos indicaste.'
                : 'Tu pedido ya está listo para retirar en el local.',
            OrderStatus::Delivered => 'Tu pedido fue entregado. ¡Gracias por comprar en Black Multimarca!',
            OrderStatus::Cancelled => 'Tu pedido fue cancelado. Si tenés dudas, escribinos y lo resolvemos.',
        };
    }
}
