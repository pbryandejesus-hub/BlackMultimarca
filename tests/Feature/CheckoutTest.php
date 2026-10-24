<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, string>
     */
    private function customerData(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'María González',
            'cedula' => '4.567.890',
            'email' => 'maria@example.com',
            'phone' => '0981 123 456',
            'delivery_method' => Order::DELIVERY_PICKUP,
        ], $overrides);
    }

    public function test_guest_places_an_order_with_quantity_discount_and_stock_is_reserved(): void
    {
        Setting::store(['discount_min_items' => 3, 'discount_percent' => 10]);
        $variant = ProductVariant::factory()
            ->for(Product::factory()->state(['name' => 'Calza deportiva', 'price' => 120000]))
            ->create(['size' => 'M', 'color' => 'Negro', 'stock' => 5]);

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 3]);
        $response = $this->post(route('checkout.store'), $this->customerData());

        $order = Order::sole();
        $response->assertRedirect(route('orders.show', $order));

        $this->assertSame('BM-000001', $order->number);
        $this->assertSame(OrderStatus::PendingPayment, $order->status);
        $this->assertSame(360000, $order->subtotal);
        $this->assertSame(36000, $order->discount);
        $this->assertSame(324000, $order->total);
        $this->assertDatabaseHas('order_items', [
            'order_id' => $order->id,
            'product_name' => 'Calza deportiva',
            'variant_label' => 'Negro · Talle M',
            'quantity' => 3,
        ]);
        $this->assertSame(2, $variant->fresh()->stock);
        $this->assertNull(session('cart'));

        $this->get(route('orders.show', $order))->assertOk()->assertSee('BM-000001')->assertSee('₲ 324.000');
    }

    public function test_checkout_page_asks_for_cedula_and_shows_the_total(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory()->state(['price' => 85000]))->create();
        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->get(route('checkout.create'))
            ->assertOk()
            ->assertSee('Cédula de Identidad')
            ->assertSee('₲ 85.000');
    }

    public function test_shipping_adds_its_cost_and_requires_an_address(): void
    {
        Setting::store(['shipping_cost' => 25000]);
        $variant = ProductVariant::factory()->for(Product::factory()->state(['price' => 100000]))->create();
        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->post(route('checkout.store'), $this->customerData(['delivery_method' => Order::DELIVERY_SHIPPING]))
            ->assertSessionHasErrors(['address', 'city']);
        $this->assertDatabaseCount('orders', 0);

        $this->post(route('checkout.store'), $this->customerData([
            'delivery_method' => Order::DELIVERY_SHIPPING,
            'address' => 'Av. Mariscal López 1234',
            'city' => 'Asunción',
        ]))->assertRedirect();

        $this->assertSame(125000, Order::sole()->total);
    }

    public function test_cedula_only_accepts_numbers_and_dots(): void
    {
        $variant = ProductVariant::factory()->create();
        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->post(route('checkout.store'), $this->customerData(['cedula' => 'abc123']))
            ->assertSessionHasErrors('cedula');
    }

    public function test_an_empty_cart_cannot_be_checked_out(): void
    {
        $this->get(route('checkout.create'))->assertRedirect(route('cart.index'));

        $this->post(route('checkout.store'), $this->customerData())->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_logged_in_customer_order_is_linked_to_their_account(): void
    {
        $user = User::factory()->create();
        $variant = ProductVariant::factory()->create();

        $this->actingAs($user)->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->actingAs($user)->post(route('checkout.store'), $this->customerData());

        $order = Order::sole();
        $this->assertTrue($order->user->is($user));
        $this->actingAs($user)->get(route('account.index'))->assertSee($order->number);
    }

    public function test_an_order_is_not_visible_to_other_visitors(): void
    {
        $order = Order::factory()->create();

        $this->get(route('orders.show', $order))->assertNotFound();
        $this->actingAs(User::factory()->create())->get(route('orders.show', $order))->assertNotFound();
    }

    public function test_customer_uploads_the_transfer_receipt(): void
    {
        Storage::fake('local');
        $variant = ProductVariant::factory()->create();
        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->customerData());
        $order = Order::sole();

        $this->post(route('orders.receipt.store', $order), [
            'receipt' => UploadedFile::fake()->create('comprobante.pdf', 200, 'application/pdf'),
        ])->assertRedirect(route('orders.show', $order));

        $order->refresh();
        $this->assertSame(OrderStatus::UnderReview, $order->status);
        Storage::disk('local')->assertExists($order->receipt_path);
    }

    public function test_receipt_cannot_be_uploaded_to_someone_elses_order(): void
    {
        Storage::fake('local');
        $order = Order::factory()->create();

        $this->post(route('orders.receipt.store', $order), [
            'receipt' => UploadedFile::fake()->create('comprobante.pdf', 200, 'application/pdf'),
        ])->assertNotFound();

        $this->assertNull($order->fresh()->receipt_path);
    }
}
