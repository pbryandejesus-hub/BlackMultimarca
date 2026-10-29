<?php

namespace Tests\Feature;

use App\Actions\PlaceOrder;
use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Support\Cart;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderConcurrencyTest extends TestCase
{
    use DatabaseMigrations;

    public function test_competing_mysql_checkouts_cannot_sell_the_last_unit_twice(): void
    {
        $this->requireMySqlConcurrencySupport();
        Setting::store(['discount_min_items' => 3, 'discount_percent' => 10, 'shipping_cost' => 0]);
        $variant = ProductVariant::factory()->for(Product::factory()->state(['price' => 85000]))
            ->create(['stock' => 1]);
        $variantId = $variant->id;

        $results = Concurrency::driver('process')->run([
            static fn (): string => self::placeOrder($variantId, 85000),
            static fn (): string => self::placeOrder($variantId, 85000),
        ]);

        sort($results);
        $this->assertSame(['out-of-stock', 'placed'], $results);
        $this->assertSame(0, $variant->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_items', 1);
    }

    public function test_competing_mysql_cancellations_return_stock_once(): void
    {
        $this->requireMySqlConcurrencySupport();
        $variant = ProductVariant::factory()->create(['stock' => 1]);
        $order = Order::factory()->has(OrderItem::factory()->state([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]), 'items')->create();
        $orderId = $order->id;

        $results = Concurrency::driver('process')->run([
            static fn (): string => self::cancelOrder($orderId),
            static fn (): string => self::cancelOrder($orderId),
        ]);

        $this->assertSame(['cancelled', 'cancelled'], $results);
        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('order_status_histories', 1);
    }

    private static function placeOrder(int $variantId, int $unitPrice): string
    {
        $session = app('session')->driver('array');
        $session->start();
        $session->put('cart', [$variantId => 1]);
        $session->put('checkout_snapshot', [
            'prices' => [$variantId => $unitPrice],
            'discount_min_items' => 3,
            'discount_percent' => 10,
            'shipping_cost' => 0,
        ]);

        try {
            (new PlaceOrder(new Cart($session)))->handle([
                'customer_name' => 'Concurrency Test',
                'cedula' => '1234567',
                'email' => 'concurrency@example.test',
                'phone' => '0981123456',
                'delivery_method' => Order::DELIVERY_PICKUP,
            ], null, (string) Str::uuid());

            return 'placed';
        } catch (ValidationException) {
            return 'out-of-stock';
        }
    }

    private static function cancelOrder(int $orderId): string
    {
        $order = Order::query()->findOrFail($orderId);
        app(UpdateOrderStatus::class)->handle($order, OrderStatus::Cancelled, null, 'Concurrent cancellation');

        return 'cancelled';
    }

    private function requireMySqlConcurrencySupport(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Esta prueba requiere MySQL para verificar bloqueos de filas reales.');
        }
    }
}
