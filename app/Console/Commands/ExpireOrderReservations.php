<?php

namespace App\Console\Commands;

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

class ExpireOrderReservations extends Command
{
    protected $signature = 'orders:expire-reservations';

    protected $description = 'Cancela pedidos pendientes vencidos y devuelve el stock reservado';

    public function handle(UpdateOrderStatus $updateOrderStatus): int
    {
        $expiresAt = now()->subHours(max(1, Setting::integer('reserve_hours')));
        $expiredCount = 0;

        Order::query()
            ->where('status', OrderStatus::PendingPayment)
            ->where('created_at', '<=', $expiresAt)
            ->orderBy('id')
            ->chunkById(100, function (Collection $orders) use ($updateOrderStatus, $expiresAt, &$expiredCount): void {
                foreach ($orders as $order) {
                    if ($updateOrderStatus->expireUnpaidReservation($order, $expiresAt)) {
                        $expiredCount++;
                    }
                }
            });

        $this->info("Reservas vencidas canceladas: {$expiredCount}.");

        return self::SUCCESS;
    }
}
