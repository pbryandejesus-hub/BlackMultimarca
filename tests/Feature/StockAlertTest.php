<?php

namespace Tests\Feature;

use App\Mail\BackInStock;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StockAlert;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StockAlertTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_asks_to_be_notified_for_a_sold_out_variant(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 0, 'size' => 'M']);

        $this->from(route('products.show', $variant->product))
            ->post(route('stock-alerts.store'), ['variant_id' => $variant->id, 'email' => 'Ana@Example.com'])
            ->assertRedirect(route('products.show', $variant->product))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('stock_alerts', ['product_variant_id' => $variant->id, 'email' => 'ana@example.com', 'notified_at' => null]);
    }

    public function test_no_alert_is_created_for_a_variant_with_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 4]);

        $this->post(route('stock-alerts.store'), ['variant_id' => $variant->id, 'email' => 'ana@example.com'])
            ->assertSessionHas('status', 'Esa combinación ya tiene stock: podés agregarla al carrito.');

        $this->assertDatabaseCount('stock_alerts', 0);
    }

    public function test_restocking_from_the_panel_emails_pending_alerts_once(): void
    {
        Mail::fake();
        $variant = ProductVariant::factory()->create(['stock' => 0, 'size' => 'S']);
        $pending = StockAlert::factory()->for($variant, 'variant')->create(['email' => 'ana@example.com']);
        StockAlert::factory()->for($variant, 'variant')->notified()->create(['email' => 'ya-avisado@example.com']);
        $admin = User::factory()->admin()->create();
        $update = fn (int $stock) => $this->actingAs($admin)->put(route('admin.products.update', $variant->product), [
            'category_id' => $variant->product->category_id,
            'name' => $variant->product->name,
            'price' => $variant->product->price,
            'variants' => [['id' => $variant->id, 'size' => 'S', 'stock' => $stock]],
        ]);

        $update(5)->assertSessionHasNoErrors();
        $update(8)->assertSessionHasNoErrors();

        Mail::assertQueuedCount(1);
        Mail::assertQueued(BackInStock::class, fn (BackInStock $mail) => $mail->hasTo('ana@example.com'));
        $this->assertNotNull($pending->fresh()->notified_at);
    }

    public function test_product_page_offers_the_alert_form_for_sold_out_combinations(): void
    {
        $product = Product::factory()->create();
        ProductVariant::factory()->for($product)->create(['size' => 'S', 'stock' => 3]);
        ProductVariant::factory()->for($product)->create(['size' => 'M', 'stock' => 0]);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('data-stock-alert', false)
            ->assertSee('Avisarme cuando vuelva');
    }

    public function test_size_guide_is_shown_only_when_the_category_has_one(): void
    {
        $withGuide = Product::factory()->for(Category::factory()->state([
            'size_guide' => "Talle | Pecho | Cintura\nS | 88-92 | 72-76\nM | 96-100 | 80-84",
        ]))->withStock()->create();
        $withoutGuide = Product::factory()->withStock()->create();

        $this->get(route('products.show', $withGuide))
            ->assertSee('Guía de talles')
            ->assertSeeInOrder(['Pecho', 'Cintura', '88-92', '96-100']);

        $this->get(route('products.show', $withoutGuide))->assertDontSee('Guía de talles');
    }
}
