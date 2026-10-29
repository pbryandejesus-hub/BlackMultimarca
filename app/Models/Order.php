<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property OrderStatus $status
 */
#[Fillable([
    'user_id', 'status', 'customer_name', 'cedula', 'email', 'phone',
    'delivery_method', 'address', 'city', 'notes',
    'items_count', 'subtotal', 'discount', 'discount_percent', 'shipping', 'total',
    'receipt_path', 'receipt_uploaded_at', 'admin_notes',
    'checkout_key', 'stock_restored_at',
])]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    public const DELIVERY_PICKUP = 'retiro';

    public const DELIVERY_SHIPPING = 'envio';

    protected $attributes = [
        'status' => 'pendiente_pago',
    ];

    protected static function booted(): void
    {
        static::created(function (Order $order): void {
            $order->forceFill(['number' => sprintf('BM-%06d', $order->id)])->saveQuietly();
        });
    }

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'receipt_uploaded_at' => 'datetime',
            'stock_restored_at' => 'datetime',
            'items_count' => 'integer',
            'subtotal' => 'integer',
            'discount' => 'integer',
            'discount_percent' => 'integer',
            'shipping' => 'integer',
            'total' => 'integer',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'number';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function isShipping(): bool
    {
        return $this->delivery_method === self::DELIVERY_SHIPPING;
    }

    public function deliveryLabel(): string
    {
        return $this->isShipping() ? 'Envío a domicilio' : 'Retiro en el local';
    }

    /**
     * Un pedido lo ve su dueño, un administrador o quien lo hizo en esta misma sesión (compra como invitado).
     *
     * @param  array<int, int>  $sessionOrderIds
     */
    public function isVisibleTo(?User $user, array $sessionOrderIds): bool
    {
        if ($user?->is_admin) {
            return true;
        }

        if ($user && $this->user_id === $user->id) {
            return true;
        }

        return in_array($this->id, $sessionOrderIds, true);
    }
}
