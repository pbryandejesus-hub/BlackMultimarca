<?php

namespace App\Models;

use App\Mail\BackInStock;
use Database\Factories\ProductVariantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

#[Fillable(['size', 'color', 'color_hex', 'stock'])]
class ProductVariant extends Model
{
    /** @use HasFactory<ProductVariantFactory> */
    use HasFactory;

    /**
     * Cuando una variante agotada vuelve a tener stock (carga del admin, pedido cancelado o reserva vencida),
     * avisa a quienes pidieron "Avisarme cuando vuelva", después de confirmar la transacción.
     */
    protected static function booted(): void
    {
        static::updated(function (ProductVariant $variant): void {
            if ($variant->wasChanged('stock') && (int) $variant->getOriginal('stock') < 1 && $variant->stock > 0) {
                DB::afterCommit(fn () => $variant->notifyStockAlerts());
            }
        });
    }

    protected function casts(): array
    {
        return [
            'stock' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * @return HasMany<StockAlert, $this>
     */
    public function stockAlerts(): HasMany
    {
        return $this->hasMany(StockAlert::class);
    }

    /**
     * Texto legible de la variante, por ejemplo "Negro · Talle M".
     */
    public function label(): string
    {
        $parts = array_filter([
            $this->color,
            $this->size ? 'Talle '.$this->size : null,
        ]);

        return $parts ? implode(' · ', $parts) : 'Único';
    }

    public function notifyStockAlerts(): void
    {
        $this->stockAlerts()->pending()->each(function (StockAlert $alert): void {
            Mail::to($alert->email)->queue(new BackInStock($this));
            $alert->update(['notified_at' => now()]);
        });
    }
}
