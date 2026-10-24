<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_shows_active_products_only(): void
    {
        Product::factory()->withStock()->create(['name' => 'Remera visible']);
        Product::factory()->inactive()->withStock()->create(['name' => 'Remera oculta']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Remera visible')
            ->assertDontSee('Remera oculta');
    }

    public function test_catalog_filters_by_category_and_search(): void
    {
        $sport = Category::factory()->create(['name' => 'Deportiva', 'slug' => 'deportiva']);
        Product::factory()->for($sport)->withStock()->create(['name' => 'Calza tiro alto']);
        Product::factory()->withStock()->create(['name' => 'Mantel antimanchas']);

        $this->get(route('catalog', ['categoria' => 'deportiva']))
            ->assertOk()
            ->assertSee('Calza tiro alto')
            ->assertDontSee('Mantel antimanchas');

        $this->get(route('catalog', ['q' => 'mantel']))
            ->assertSee('Mantel antimanchas')
            ->assertDontSee('Calza tiro alto');
    }

    public function test_product_page_shows_price_in_guaranies(): void
    {
        $product = Product::factory()->withStock()->create(['price' => 150000]);

        $this->get(route('products.show', $product))->assertOk()->assertSee('₲ 150.000');
    }

    public function test_hidden_product_page_is_not_found(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_help_pages_exist_and_unknown_ones_do_not(): void
    {
        $this->get(route('pages.show', 'como-comprar'))->assertOk()->assertSee('Transferí el total');
        $this->get(route('pages.show', 'no-existe'))->assertNotFound();
    }
}
