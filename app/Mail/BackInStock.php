<?php

namespace App\Mail;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aviso a quien pidió "Avisarme cuando vuelva" de que la variante tiene stock otra vez.
 */
class BackInStock extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public ProductVariant $variant) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "¡Volvió {$this->variant->product->name}!",
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'mail.products.back-in-stock');
    }
}
