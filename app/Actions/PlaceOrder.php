<?php

namespace App\Actions;

use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Convierte el carrito en un pedido, descontando el stock dentro de una transacción.
 */
class PlaceOrder
{
    public function __construct(private Cart $cart, private AttachReceipt $attachReceipt) {}

    /**
     * @param  array{customer_name: string, cedula: string, email: string, phone: string, delivery_method: string, address?: ?string, city?: ?string, notes?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data, ?User $user, ?UploadedFile $receipt = null): Order
    {
        $summary = $this->cart->summary($data['delivery_method']);

        if ($summary['lines']->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Tu carrito está vacío.']);
        }

        $order = DB::transaction(function () use ($data, $user, $summary): Order {
            $variants = ProductVariant::query()
                ->whereIn('id', $summary['lines']->pluck('variant.id'))
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            foreach ($summary['lines'] as $line) {
                $variant = $variants->get($line['variant']->id);

                if (! $variant || $variant->stock < $line['quantity']) {
                    throw ValidationException::withMessages([
                        'cart' => "No hay stock suficiente de {$line['variant']->product->name} ({$line['variant']->label()}).",
                    ]);
                }
            }

            $isShipping = $data['delivery_method'] === Order::DELIVERY_SHIPPING;

            $order = Order::create([
                'user_id' => $user?->id,
                'customer_name' => $data['customer_name'],
                'cedula' => $data['cedula'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'delivery_method' => $data['delivery_method'],
                'address' => $isShipping ? ($data['address'] ?? null) : null,
                'city' => $isShipping ? ($data['city'] ?? null) : null,
                'notes' => $data['notes'] ?? null,
                'items_count' => $summary['items_count'],
                'subtotal' => $summary['subtotal'],
                'discount' => $summary['discount'],
                'discount_percent' => $summary['discount_percent'],
                'shipping' => $summary['shipping'],
                'total' => $summary['total'],
            ]);

            foreach ($summary['lines'] as $line) {
                $order->items()->create([
                    'product_id' => $line['variant']->product_id,
                    'product_variant_id' => $line['variant']->id,
                    'product_name' => $line['variant']->product->name,
                    'variant_label' => $line['variant']->label(),
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);

                $variants->get($line['variant']->id)->decrement('stock', $line['quantity']);
            }

            return $order;
        });

        if ($receipt) {
            $this->attachReceipt->handle($order, $receipt);
        }

        $this->cart->clear();

        return $order->refresh();
    }
}
