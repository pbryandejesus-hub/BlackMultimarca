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
 * Pedido de un cliente para que le avisemos por email cuando una variante sin stock vuelva a tener.
 */
#[Fillable(['product_variant_id', 'email', 'notified_at'])]
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
}
