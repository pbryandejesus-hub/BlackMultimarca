<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_an_order_returns_its_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 1]);
        $order = Order::factory()->has(OrderItem::factory()->state([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]), 'items')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.update', $order), ['status' => OrderStatus::Cancelled->value, 'reason' => 'Cancelación autorizada por el cliente.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(4, $variant->fresh()->stock);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.update', $order), ['status' => OrderStatus::Cancelled->value, 'reason' => 'Cancelación repetida.'])
            ->assertSessionHasNoErrors();

        $this->assertSame(4, $variant->fresh()->stock);
        $this->assertDatabaseCount('inventory_movements', 1);
        $this->assertDatabaseCount('order_status_histories', 1);
    }

    public function test_admin_sees_the_order_detail(): void
    {
        $order = Order::factory()->has(OrderItem::factory()->state(['product_name' => 'Toallón de algodón']), 'items')
            ->create(['customer_name' => 'Ana Ramírez']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Ana Ramírez')
            ->assertSee('Toallón de algodón');
    }

    public function test_a_cancelled_order_cannot_be_reopened(): void
    {
        $order = Order::factory()->status(OrderStatus::Cancelled)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.update', $order), ['status' => OrderStatus::Paid->value, 'reason' => 'Reapertura no permitida.'])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
    }

    public function test_a_paid_order_cannot_be_cancelled_by_the_ordinary_admin_flow(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 1]);
        $order = Order::factory()->status(OrderStatus::Paid)->has(OrderItem::factory()->state([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 2,
        ]), 'items')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.update', $order), ['status' => OrderStatus::Cancelled->value, 'reason' => 'No se permite cancelar pagos confirmados.'])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
        $this->assertSame(1, $variant->fresh()->stock);
        $this->assertDatabaseCount('inventory_movements', 0);
    }

    public function test_shipped_order_cannot_be_cancelled(): void
    {
        $order = Order::factory()->status(OrderStatus::Shipped)->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.update', $order), ['status' => OrderStatus::Cancelled->value, 'reason' => 'No se permite cancelar pedidos enviados.'])
            ->assertSessionHasErrors('status');

        $this->assertSame(OrderStatus::Shipped, $order->fresh()->status);
    }

    public function test_an_order_under_review_can_be_cancelled_and_its_stock_is_restored(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 2]);
        $order = Order::factory()->status(OrderStatus::UnderReview)->has(OrderItem::factory()->state([
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'quantity' => 3,
        ]), 'items')->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.orders.update', $order), [
                'status' => OrderStatus::Cancelled->value,
                'reason' => 'Comprobante rechazado; liberar la reserva.',
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame(5, $variant->fresh()->stock);
        $this->assertDatabaseHas('inventory_movements', [
            'order_id' => $order->id,
            'type' => 'cancellation_return',
            'quantity' => 3,
        ]);
    }

    public function test_admin_can_open_the_receipt_but_customers_cannot(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('receipts/comprobante.pdf', 'pdf');
        $order = Order::factory()->create(['receipt_path' => 'receipts/comprobante.pdf']);

        $this->actingAs(User::factory()->admin()->create())->get(route('admin.orders.receipt', $order))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('admin.orders.receipt', $order))->assertForbidden();
    }

    public function test_settings_are_saved_and_shown_in_the_order_page(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.settings.update'), [
            ...Setting::DEFAULTS,
            'bank_name' => 'Banco Ejemplo',
            'bank_alias' => '4567890',
            'shipping_cost' => '30.000',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Banco Ejemplo', Setting::get('bank_name'));
        $this->assertSame(30000, Setting::integer('shipping_cost'));

        $order = Order::factory()->create();
        $this->actingAs($admin)->get(route('orders.show', $order))->assertSee('Banco Ejemplo')->assertSee('4567890');
    }
}
