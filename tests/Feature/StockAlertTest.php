<?php

namespace Tests\Feature;

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

    public function test_customer_leaves_whatsapp_or_email_for_a_sold_out_variant(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 0, 'size' => 'M']);

        $this->from(route('products.show', $variant->product))
            ->post(route('stock-alerts.store'), ['variant_id' => $variant->id, 'contact' => '0981 123 456'])
            ->assertRedirect(route('products.show', $variant->product))
            ->assertSessionHas('status');

        $this->post(route('stock-alerts.store'), ['variant_id' => $variant->id, 'contact' => 'Ana@Example.com']);

        $this->assertDatabaseHas('stock_alerts', ['product_variant_id' => $variant->id, 'contact' => '0981 123 456', 'notified_at' => null]);
        $this->assertDatabaseHas('stock_alerts', ['product_variant_id' => $variant->id, 'contact' => 'ana@example.com']);
    }

    public function test_contact_must_be_a_phone_or_an_email(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 0]);

        $this->post(route('stock-alerts.store'), ['variant_id' => $variant->id, 'contact' => 'avisame porfa'])
            ->assertSessionHasErrors('contact');

        $this->assertDatabaseCount('stock_alerts', 0);
    }

    public function test_no_alert_is_created_for_a_variant_with_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 4]);

        $this->post(route('stock-alerts.store'), ['variant_id' => $variant->id, 'contact' => 'ana@example.com'])
            ->assertSessionHas('status', 'Esa combinación ya tiene stock: podés agregarla al carrito.');

        $this->assertDatabaseCount('stock_alerts', 0);
    }

    public function test_admin_sees_pending_interested_customers_and_marks_them_as_notified(): void
    {
        Mail::fake();
        $variant = ProductVariant::factory()->create(['stock' => 0, 'size' => 'S']);
        $phoneAlert = StockAlert::factory()->for($variant, 'variant')->create(['contact' => '0981 123456']);
        StockAlert::factory()->for($variant, 'variant')->notified()->create(['contact' => 'ya-avisado@example.com']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $variant->product))
            ->assertSee('Interesados en reposición')
            ->assertSee('data-alert-variant-id="'.$variant->id.'"', false)
            ->assertSee('0981 123456')
            ->assertSee('https://wa.me/595981123456', false)
            ->assertDontSee('ya-avisado@example.com');

        $this->actingAs($admin)->get(route('admin.dashboard'))
            ->assertSee('1 interesado')
            ->assertSee(route('admin.products.edit', $variant->product).'#interesados-'.$variant->id, false);

        $this->actingAs($admin)
            ->patch(route('admin.stock-alerts.update', $phoneAlert))
            ->assertSessionHas('status');

        $this->assertNotNull($phoneAlert->fresh()->notified_at);
        Mail::assertNothingOutgoing();
    }

    public function test_admin_removes_an_interested_customer(): void
    {
        $alert = StockAlert::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.stock-alerts.destroy', $alert))
            ->assertSessionHas('status');

        $this->assertModelMissing($alert);
    }

    public function test_customers_cannot_manage_interested_lists(): void
    {
        $alert = StockAlert::factory()->create();

        $this->actingAs(User::factory()->create())
            ->patch(route('admin.stock-alerts.update', $alert))
            ->assertForbidden();
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
