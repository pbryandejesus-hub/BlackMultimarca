<?php

namespace App\Support;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;

/**
 * Carrito guardado en la sesión: variante => cantidad.
 *
 * @phpstan-type CartLine array{variant: ProductVariant, quantity: int, unit_price: int, line_total: int}
 * @phpstan-type CartSummary array{lines: Collection<int, CartLine>, items_count: int, subtotal: int, discount_percent: int, discount: int, shipping: int, total: int, discount_min_items: int, missing_for_discount: int}
 */
class Cart
{
    private const SESSION_KEY = 'cart';

    public function __construct(private Session $session) {}

    /**
     * @return array<int, int>
     */
    public function quantities(): array
    {
        return $this->session->get(self::SESSION_KEY, []);
    }

    public function count(): int
    {
        return array_sum($this->quantities());
    }

    public function isEmpty(): bool
    {
        return $this->quantities() === [];
    }

    /**
     * Suma unidades de una variante sin superar el stock. Devuelve la cantidad final en el carrito.
     */
    public function add(ProductVariant $variant, int $quantity): int
    {
        $quantities = $this->quantities();
        $quantities[$variant->id] = min(($quantities[$variant->id] ?? 0) + $quantity, $variant->stock);

        $this->save($quantities);

        return $quantities[$variant->id];
    }

    public function update(ProductVariant $variant, int $quantity): void
    {
        $quantities = $this->quantities();
        $quantities[$variant->id] = min($quantity, $variant->stock);

        $this->save($quantities);
    }

    public function remove(int $variantId): void
    {
        $quantities = $this->quantities();
        unset($quantities[$variantId]);

        $this->save($quantities);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    /**
     * Líneas del carrito con sus productos. Descarta lo que ya no está activo y ajusta al stock disponible.
     *
     * @return Collection<int, CartLine>
     */
    public function lines(): Collection
    {
        $quantities = $this->quantities();

        if ($quantities === []) {
            return collect();
        }

        $variants = ProductVariant::query()
            ->with(['product.images', 'product.brand'])
            ->whereIn('id', array_keys($quantities))
            ->whereHas('product', fn ($query) => $query->active())
            ->get()
            ->keyBy('id');

        $lines = collect();
        $adjusted = [];

        foreach ($quantities as $variantId => $quantity) {
            $variant = $variants->get($variantId);
            $quantity = $variant ? min($quantity, $variant->stock) : 0;

            if ($quantity < 1) {
                continue;
            }

            $adjusted[$variantId] = $quantity;
            $lines->push([
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price' => $variant->product->price,
                'line_total' => $variant->product->price * $quantity,
            ]);
        }

        if ($adjusted !== $quantities) {
            $this->save($adjusted);
        }

        return $lines;
    }

    /**
     * Totales del carrito con el descuento por cantidad y el costo de envío.
     *
     * @return CartSummary
     */
    public function summary(string $deliveryMethod = Order::DELIVERY_PICKUP): array
    {
        $lines = $this->lines();
        $itemsCount = (int) $lines->sum('quantity');
        $subtotal = (int) $lines->sum('line_total');

        $minItems = Setting::integer('discount_min_items');
        $percent = Setting::integer('discount_percent');
        $qualifies = $minItems > 0 && $percent > 0 && $itemsCount >= $minItems;
        $discountPercent = $qualifies ? $percent : 0;
        $discount = intdiv($subtotal * $discountPercent, 100);

        $shipping = $deliveryMethod === Order::DELIVERY_SHIPPING ? Setting::integer('shipping_cost') : 0;

        return [
            'lines' => $lines,
            'items_count' => $itemsCount,
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => $subtotal - $discount + $shipping,
            'discount_min_items' => $percent > 0 ? $minItems : 0,
            'missing_for_discount' => $percent > 0 && $minItems > 0 ? max(0, $minItems - $itemsCount) : 0,
        ];
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function save(array $quantities): void
    {
        $this->session->put(self::SESSION_KEY, array_filter($quantities, fn (int $quantity): bool => $quantity > 0));
    }
}
