<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\Cart;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Convierte el carrito en un pedido, descontando el stock dentro de una transacción.
 */
class PlaceOrder
{
    public function __construct(private Cart $cart) {}

    /**
     * @param  array{customer_name: string, cedula: string, email: string, phone: string, delivery_method: string, address?: ?string, city?: ?string, notes?: ?string}  $data
     *
     * @throws ValidationException
     */
    public function handle(array $data, ?User $user, string $checkoutKey, ?UploadedFile $receipt = null): Order
    {
        $existingOrder = Order::query()->where('checkout_key', $checkoutKey)->first();

        if ($existingOrder) {
            $this->cart->clear();

            return $existingOrder;
        }

        $storedReceiptPath = $receipt?->store('receipts', 'local');

        if ($receipt && ! $storedReceiptPath) {
            throw ValidationException::withMessages(['receipt' => 'No se pudo guardar el comprobante. Intentá nuevamente.']);
        }

        try {
            $order = DB::transaction(function () use ($data, $user, $checkoutKey, $storedReceiptPath): Order {
                $quantities = $this->cart->quantities();
                $variantIds = array_keys($quantities);
                $productIds = ProductVariant::query()->whereIn('id', $variantIds)->pluck('product_id')->unique()->sort()->values();

                if ($productIds->isNotEmpty()) {
                    Product::query()->select('id')->whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();
                }

                $variants = ProductVariant::query()
                    ->with('product')
                    ->whereIn('id', $variantIds)
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $existingOrder = Order::query()->where('checkout_key', $checkoutKey)->lockForUpdate()->first();

                if ($existingOrder) {
                    return $existingOrder;
                }

                $summary = $this->cart->summaryForCheckout(
                    $data['delivery_method'],
                    $variants,
                    $this->cart->checkoutSnapshot(),
                );

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
                    'checkout_key' => $checkoutKey,
                    'receipt_path' => $storedReceiptPath,
                    'receipt_uploaded_at' => $storedReceiptPath ? now() : null,
                    'status' => $storedReceiptPath ? OrderStatus::UnderReview : OrderStatus::PendingPayment,
                ]);

                DB::table('order_status_histories')->insert([
                    'order_id' => $order->id,
                    'actor_user_id' => $user?->id,
                    'from_status' => null,
                    'to_status' => $order->status->value,
                    'reason' => $storedReceiptPath
                        ? 'Pedido creado con comprobante adjuntado'
                        : 'Pedido creado en checkout',
                    'created_at' => now(),
                ]);

                foreach ($summary['lines'] as $line) {
                    $order->items()->create([
                        'product_id' => $line['variant']->product_id,
                        'product_variant_id' => $line['variant']->id,
                        'product_name' => $line['variant']->product->name,
                        'product_sku' => $line['variant']->product->sku,
                        'variant_label' => $line['variant']->label(),
                        'unit_price' => $line['unit_price'],
                        'quantity' => $line['quantity'],
                        'line_total' => $line['line_total'],
                    ]);

                    $variants->get($line['variant']->id)->decrement('stock', $line['quantity']);
                }

                return $order;
            }, 3);
        } catch (QueryException $exception) {
            if ($storedReceiptPath) {
                Storage::disk('local')->delete($storedReceiptPath);
            }

            $existingOrder = Order::query()->where('checkout_key', $checkoutKey)->first();

            if (! $existingOrder) {
                throw $exception;
            }

            $this->cart->clear();

            return $existingOrder;
        } catch (\Throwable $exception) {
            if ($storedReceiptPath) {
                Storage::disk('local')->delete($storedReceiptPath);
            }

            throw $exception;
        }

        if ($storedReceiptPath && $order->receipt_path !== $storedReceiptPath) {
            Storage::disk('local')->delete($storedReceiptPath);
        }

        $this->cart->clear();

        return $order->refresh();
    }
}
