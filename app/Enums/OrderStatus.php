<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pendiente_pago';
    case UnderReview = 'en_revision';
    case Paid = 'pagado';
    case Shipped = 'enviado';
    case Delivered = 'entregado';
    case Cancelled = 'cancelado';

    public function label(): string
    {
        return match ($this) {
            self::PendingPayment => 'Esperando transferencia',
            self::UnderReview => 'Comprobante en revisión',
            self::Paid => 'Pago confirmado',
            self::Shipped => 'Enviado / listo para retirar',
            self::Delivered => 'Entregado',
            self::Cancelled => 'Cancelado',
        };
    }

    /**
     * Clases de Tailwind para la etiqueta del estado.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PendingPayment => 'bg-amber-100 text-amber-900',
            self::UnderReview => 'bg-sky-100 text-sky-900',
            self::Paid => 'bg-emerald-100 text-emerald-900',
            self::Shipped => 'bg-indigo-100 text-indigo-900',
            self::Delivered => 'bg-stone-200 text-stone-800',
            self::Cancelled => 'bg-red-100 text-red-900',
        };
    }

    /**
     * Indica si el cliente todavía puede subir o reemplazar el comprobante.
     */
    public function acceptsReceipt(): bool
    {
        return in_array($this, [self::PendingPayment, self::UnderReview], true);
    }

    /**
     * Estados que cuentan como venta concretada.
     *
     * @return list<self>
     */
    public static function confirmed(): array
    {
        return [self::Paid, self::Shipped, self::Delivered];
    }
}
