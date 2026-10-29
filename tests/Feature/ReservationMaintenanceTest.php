<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReservationMaintenanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_unpaid_reservations_are_cancelled_and_release_stock(): void
    {
        Setting::store(['reserve_hours' => 48]);
        $variant = ProductVariant::factory()->create(['stock' => 0]);
        $expiredOrder = Order::factory()->has(OrderItem::factory()->state([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]), 'items')->create([
            'created_at' => now()->subHours(49),
            'updated_at' => now()->subHours(49),
        ]);
        $recentOrder = Order::factory()->create();
        $reviewOrder = Order::factory()->status(OrderStatus::UnderReview)->create([
            'created_at' => now()->subHours(72),
            'updated_at' => now()->subHours(72),
        ]);

        $this->artisan('orders:expire-reservations')->assertSuccessful();

        $this->assertSame(OrderStatus::Cancelled, $expiredOrder->fresh()->status);
        $this->assertSame(2, $variant->fresh()->stock);
        $this->assertSame(OrderStatus::PendingPayment, $recentOrder->fresh()->status);
        $this->assertSame(OrderStatus::UnderReview, $reviewOrder->fresh()->status);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $expiredOrder->id,
            'from_status' => OrderStatus::PendingPayment->value,
            'to_status' => OrderStatus::Cancelled->value,
            'reason' => 'Reserva vencida por falta de pago',
        ]);
    }

    public function test_old_orphaned_receipts_are_removed_without_deleting_referenced_or_recent_files(): void
    {
        Storage::fake('local');
        config(['services.receipts.orphan_retention_hours' => 48]);

        $disk = Storage::disk('local');
        $disk->put('receipts/orphan.pdf', 'orphan');
        $disk->put('receipts/referenced.pdf', 'referenced');
        $disk->put('receipts/recent.pdf', 'recent');
        Order::factory()->create(['receipt_path' => 'receipts/referenced.pdf']);
        $oldTimestamp = now()->subHours(49)->getTimestamp();
        touch($disk->path('receipts/orphan.pdf'), $oldTimestamp);
        touch($disk->path('receipts/referenced.pdf'), $oldTimestamp);

        $this->artisan('receipts:prune-orphans')->assertSuccessful();

        $disk->assertMissing('receipts/orphan.pdf');
        $disk->assertExists('receipts/referenced.pdf');
        $disk->assertExists('receipts/recent.pdf');
    }
}
