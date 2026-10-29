<?php

namespace App\Support;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Contracts\Session\Session;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
        $this->invalidateCheckoutKey();
        $quantities = $this->quantities();
        $quantities[$variant->id] = min(($quantities[$variant->id] ?? 0) + $quantity, $variant->stock);

        $this->save($quantities);

        return $quantities[$variant->id];
    }

    public function update(ProductVariant $variant, int $quantity): void
    {
        $this->invalidateCheckoutKey();
        $quantities = $this->quantities();
        $quantities[$variant->id] = min($quantity, $variant->stock);

        $this->save($quantities);
    }

    public function remove(int $variantId): void
    {
        $this->invalidateCheckoutKey();
        $quantities = $this->quantities();
        unset($quantities[$variantId]);

        $this->save($quantities);
    }

    public function clear(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    public function checkoutKey(): string
    {
        if (! $this->session->has('checkout_key')) {
            $this->session->put('checkout_key', (string) Str::uuid());
        }

        return (string) $this->session->get('checkout_key');
    }

    /**
     * @param  array<int, int>  $prices
     */
    public function setCheckoutSnapshot(array $prices): void
    {
        $this->session->put('checkout_snapshot', [
            'prices' => $prices,
            'discount_min_items' => Setting::integer('discount_min_items'),
            'discount_percent' => Setting::integer('discount_percent'),
            'shipping_cost' => Setting::integer('shipping_cost'),
        ]);
    }

    /**
     * @return array{prices: array<int, int>, discount_min_items: int, discount_percent: int, shipping_cost: int}
     */
    public function checkoutSnapshot(): array
    {
        return $this->session->get('checkout_snapshot', [
            'prices' => [],
            'discount_min_items' => -1,
            'discount_percent' => -1,
            'shipping_cost' => -1,
        ]);
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
            $unitPrice = $this->unitPrice($variant);
            $lines->push([
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $this->lineTotal($unitPrice, $quantity),
            ]);
        }

        if ($adjusted !== $quantities) {
            $this->invalidateCheckoutKey();
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

        return $this->summarizeLines($lines, $deliveryMethod);
    }

    /**
     * Rebuilds a checkout summary from variants loaded and locked in the current transaction.
     * Missing, inactive, and out-of-stock cart entries are rejected instead of silently dropped.
     *
     * @param  Collection<int, ProductVariant>  $variants
     * @param  array{prices: array<int, int>, discount_min_items: int, discount_percent: int, shipping_cost: int}  $snapshot
     * @return CartSummary
     */
    public function summaryForCheckout(string $deliveryMethod, Collection $variants, array $snapshot): array
    {
        $quantities = $this->quantities();
        $lines = collect();

        $currentSettings = Setting::query()
            ->whereIn('key', ['discount_min_items', 'discount_percent', 'shipping_cost'])
            ->orderBy('key')
            ->lockForUpdate()
            ->pluck('value', 'key');
        $discountMinItems = (int) ($currentSettings['discount_min_items'] ?? Setting::DEFAULTS['discount_min_items']);
        $discountPercent = (int) ($currentSettings['discount_percent'] ?? Setting::DEFAULTS['discount_percent']);
        $shippingCost = (int) ($currentSettings['shipping_cost'] ?? Setting::DEFAULTS['shipping_cost']);

        if (
            $snapshot['discount_min_items'] !== $discountMinItems
            || $snapshot['discount_percent'] !== $discountPercent
            || $snapshot['shipping_cost'] !== $shippingCost
        ) {
            throw ValidationException::withMessages([
                'cart' => 'Los precios o condiciones de compra cambiaron. Revisá el resumen actualizado antes de confirmar.',
            ]);
        }

        foreach ($quantities as $variantId => $quantity) {
            $variant = $variants->get($variantId);

            if (! $variant || ! $variant->product?->is_active || $quantity < 1 || $quantity > 99) {
                throw ValidationException::withMessages([
                    'cart' => 'Un producto del carrito ya no está disponible. Revisá tu carrito e intentá nuevamente.',
                ]);
            }

            if ($variant->stock < $quantity) {
                throw ValidationException::withMessages([
                    'cart' => "No hay stock suficiente de {$variant->product->name} ({$variant->label()}).",
                ]);
            }

            $unitPrice = $this->unitPrice($variant);

            if (($snapshot['prices'][$variant->id] ?? null) !== $unitPrice) {
                throw ValidationException::withMessages([
                    'cart' => 'El precio de un producto cambió. Revisá el resumen actualizado antes de confirmar.',
                ]);
            }
            $lines->push([
                'variant' => $variant,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $this->lineTotal($unitPrice, $quantity),
            ]);
        }

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Tu carrito está vacío.']);
        }

        return $this->summarizeLines($lines, $deliveryMethod, [
            'discount_min_items' => $discountMinItems,
            'discount_percent' => $discountPercent,
            'shipping_cost' => $shippingCost,
        ]);
    }

    /**
     * @param  Collection<int, CartLine>  $lines
     * @param  array{discount_min_items: int, discount_percent: int, shipping_cost: int}|null  $settings
     * @return CartSummary
     */
    private function summarizeLines(Collection $lines, string $deliveryMethod, ?array $settings = null): array
    {
        $itemsCount = (int) $lines->sum('quantity');
        $subtotal = 0;

        foreach ($lines as $line) {
            if ($line['line_total'] > PHP_INT_MAX - $subtotal) {
                throw ValidationException::withMessages(['cart' => 'El total del carrito excede el importe admitido. Contactá a la tienda.']);
            }

            $subtotal += $line['line_total'];
        }

        $minItems = $settings['discount_min_items'] ?? Setting::integer('discount_min_items');
        $percent = $settings['discount_percent'] ?? Setting::integer('discount_percent');
        $qualifies = $minItems > 0 && $percent > 0 && $itemsCount >= $minItems;
        $discountPercent = $qualifies ? $percent : 0;
        $discount = intdiv($subtotal, 100) * $discountPercent
            + intdiv(($subtotal % 100) * $discountPercent, 100);

        $shippingCost = $settings['shipping_cost'] ?? Setting::integer('shipping_cost');
        $shipping = $deliveryMethod === Order::DELIVERY_SHIPPING ? $shippingCost : 0;
        $discountedSubtotal = $subtotal - $discount;

        if ($shipping > PHP_INT_MAX - $discountedSubtotal) {
            throw ValidationException::withMessages(['cart' => 'El total del carrito excede el importe admitido. Contactá a la tienda.']);
        }

        return [
            'lines' => $lines,
            'items_count' => $itemsCount,
            'subtotal' => $subtotal,
            'discount_percent' => $discountPercent,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => $discountedSubtotal + $shipping,
            'discount_min_items' => $percent > 0 ? $minItems : 0,
            'missing_for_discount' => $percent > 0 && $minItems > 0 ? max(0, $minItems - $itemsCount) : 0,
        ];
    }

    private function invalidateCheckoutKey(): void
    {
        $this->session->forget('checkout_key');
        $this->session->forget('checkout_snapshot');
    }

    private function unitPrice(ProductVariant $variant): int
    {
        $rawPrice = (string) $variant->product->getRawOriginal('price');
        $maxPrice = (string) PHP_INT_MAX;

        if (
            ! ctype_digit($rawPrice)
            || strlen($rawPrice) > strlen($maxPrice)
            || (strlen($rawPrice) === strlen($maxPrice) && strcmp($rawPrice, $maxPrice) > 0)
        ) {
            throw ValidationException::withMessages(['cart' => 'El precio de un producto no se puede procesar. Contactá a la tienda.']);
        }

        return (int) $rawPrice;
    }

    private function lineTotal(int $unitPrice, int $quantity): int
    {
        if ($quantity > 0 && $unitPrice > intdiv(PHP_INT_MAX, $quantity)) {
            throw ValidationException::withMessages(['cart' => 'El total del carrito excede el importe admitido. Contactá a la tienda.']);
        }

        return $unitPrice * $quantity;
    }

    /**
     * @param  array<int, int>  $quantities
     */
    private function save(array $quantities): void
    {
        $this->session->put(self::SESSION_KEY, array_filter($quantities, fn (int $quantity): bool => $quantity > 0));
    }
}
