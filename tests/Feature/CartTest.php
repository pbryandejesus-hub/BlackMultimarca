<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_more_units_than_available_caps_the_quantity_at_the_stock(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 2]);

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 5])
            ->assertSessionHas('status', fn (string $message) => str_contains($message, 'Solo hay 2 unidades'));

        $this->assertSame([$variant->id => 2], session('cart'));
    }

    public function test_a_variant_without_stock_cannot_be_added(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 0]);

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertSessionHasErrors('variant_id');

        $this->assertNull(session('cart'));
    }

    public function test_a_hidden_product_cannot_be_added(): void
    {
        $variant = ProductVariant::factory()->for(Product::factory()->inactive())->create();

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1])->assertNotFound();
    }

    public function test_quantity_discount_applies_when_reaching_the_minimum_items(): void
    {
        Setting::store(['discount_min_items' => 3, 'discount_percent' => 10]);
        $variant = ProductVariant::factory()->for(Product::factory()->state(['price' => 100000]))->create(['stock' => 10]);

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 3]);

        $this->get(route('cart.index'))
            ->assertOk()
            ->assertSee('Descuento por cantidad (10%)')
            ->assertSee('₲ 270.000');
    }

    public function test_cart_tells_how_many_items_are_missing_for_the_discount(): void
    {
        Setting::store(['discount_min_items' => 3, 'discount_percent' => 10]);
        $variant = ProductVariant::factory()->for(Product::factory()->state(['price' => 100000]))->create(['stock' => 10]);

        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 2]);

        $this->get(route('cart.index'))
            ->assertSee('1 producto más')
            ->assertDontSee('Descuento por cantidad (10%)')
            ->assertSee('₲ 200.000');
    }

    public function test_items_can_be_updated_and_removed(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 10]);
        $this->post(route('cart.store'), ['variant_id' => $variant->id, 'quantity' => 1]);

        $this->patch(route('cart.update', $variant), ['quantity' => 4])->assertRedirect(route('cart.index'));
        $this->assertSame([$variant->id => 4], session('cart'));

        $this->delete(route('cart.destroy', $variant))->assertRedirect(route('cart.index'));
        $this->assertSame([], session('cart'));
    }
}
