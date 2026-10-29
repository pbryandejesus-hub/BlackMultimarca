<?php

namespace App\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\ProductVariant;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrderStatus
{
    public function attachReceipt(Order $order, string $receiptPath, ?User $actor): ?string
    {
        return DB::transaction(function () use ($order, $receiptPath, $actor): ?string {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();
            $oldStatus = $lockedOrder->status;

            if (! $oldStatus->acceptsReceipt()) {
                throw ValidationException::withMessages(['receipt' => 'Este pedido ya no admite cambios de comprobante.']);
            }

            if (
                $oldStatus !== OrderStatus::UnderReview
                && ! in_array(OrderStatus::UnderReview, $oldStatus->allowedTransitions(), true)
            ) {
                throw ValidationException::withMessages(['receipt' => 'La transición solicitada no está permitida para este pedido.']);
            }

            $previousPath = $lockedOrder->receipt_path;
            $lockedOrder->update([
                'receipt_path' => $receiptPath,
                'receipt_uploaded_at' => now(),
                'status' => OrderStatus::UnderReview,
            ]);

            if ($oldStatus !== OrderStatus::UnderReview) {
                DB::table('order_status_histories')->insert([
                    'order_id' => $lockedOrder->id,
                    'actor_user_id' => $actor?->id,
                    'from_status' => $oldStatus->value,
                    'to_status' => OrderStatus::UnderReview->value,
                    'reason' => 'Comprobante cargado por el cliente',
                    'created_at' => now(),
                ]);
            }

            return $previousPath;
        }, 3);
    }

    public function handle(Order $order, OrderStatus $newStatus, ?User $actor, ?string $reason = null, ?string $adminNotes = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $actor, $reason, $adminNotes): Order {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            return $this->transitionLocked($lockedOrder, $newStatus, $actor, $reason, $adminNotes);
        }, 3);
    }

    public function expireUnpaidReservation(Order $order, CarbonInterface $expiresAt): bool
    {
        return DB::transaction(function () use ($order, $expiresAt): bool {
            $lockedOrder = Order::query()->whereKey($order->id)->lockForUpdate()->firstOrFail();

            if ($lockedOrder->status !== OrderStatus::PendingPayment || $lockedOrder->created_at->gt($expiresAt)) {
                return false;
            }

            $this->transitionLocked(
                $lockedOrder,
                OrderStatus::Cancelled,
                null,
                'Reserva vencida por falta de pago',
                $lockedOrder->admin_notes,
            );

            return true;
        }, 3);
    }

    private function transitionLocked(
        Order $lockedOrder,
        OrderStatus $newStatus,
        ?User $actor,
        ?string $reason,
        ?string $adminNotes,
    ): Order {
        $oldStatus = $lockedOrder->status;

        if ($oldStatus === $newStatus) {
            $lockedOrder->admin_notes = $adminNotes;
            $lockedOrder->save();

            return $lockedOrder;
        }

        if (! in_array($newStatus, $oldStatus->allowedTransitions(), true)) {
            throw ValidationException::withMessages([
                'status' => 'La transición solicitada no está permitida para el estado actual del pedido.',
            ]);
        }

        if ($newStatus === OrderStatus::Cancelled) {
            $this->restoreStock($lockedOrder);
        }

        $lockedOrder->status = $newStatus;
        $lockedOrder->admin_notes = $adminNotes;
        $lockedOrder->save();

        DB::table('order_status_histories')->insert([
            'order_id' => $lockedOrder->id,
            'actor_user_id' => $actor?->id,
            'from_status' => $oldStatus->value,
            'to_status' => $newStatus->value,
            'reason' => $reason,
            'created_at' => now(),
        ]);

        return $lockedOrder;
    }

    private function restoreStock(Order $lockedOrder): void
    {
        if ($lockedOrder->stock_restored_at !== null) {
            throw ValidationException::withMessages([
                'status' => 'El stock de este pedido ya fue devuelto y no se puede volver a cancelar.',
            ]);
        }

        $items = $lockedOrder->items()->orderBy('product_variant_id')->orderBy('id')->get();
        $variantIds = $items->pluck('product_variant_id')->filter()->unique()->sort()->values();
        $variants = $variantIds->isEmpty()
            ? collect()
            : ProductVariant::query()->whereIn('id', $variantIds)->orderBy('id')->lockForUpdate()->get()->keyBy('id');

        foreach ($items as $item) {
            if (! $item->product_variant_id || ! $variants->has($item->product_variant_id)) {
                continue;
            }

            DB::table('inventory_movements')->insert([
                'order_id' => $lockedOrder->id,
                'order_item_id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'type' => 'cancellation_return',
                'quantity' => $item->quantity,
                'source_key' => 'order-item:'.$item->id.':cancellation',
                'created_at' => now(),
            ]);

            $variants->get($item->product_variant_id)->increment('stock', $item->quantity);
        }

        $lockedOrder->stock_restored_at = now();
    }
}
