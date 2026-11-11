<?php

namespace App\Models;

use Database\Factories\StockAlertFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cliente interesado en una variante sin stock. Deja su WhatsApp o su email y la tienda le avisa a mano
 * cuando vuelve a haber; "notified_at" marca que ya se le avisó.
 */
#[Fillable(['product_variant_id', 'contact', 'notified_at'])]
class StockAlert extends Model
{
    /** @use HasFactory<StockAlertFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'notified_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<ProductVariant, $this>
     */
    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->whereNull('notified_at');
    }

    public function isEmail(): bool
    {
        return str_contains($this->contact, '@');
    }

    /**
     * Enlace de WhatsApp con el mensaje listo. Los números locales (0981…) se pasan al formato de Paraguay (595981…).
     */
    public function whatsappUrl(string $message): ?string
    {
        if ($this->isEmail()) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $this->contact);
        $number = str_starts_with($digits, '0') ? '595'.substr($digits, 1) : $digits;

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }
}
