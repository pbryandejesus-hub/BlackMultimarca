<?php

namespace Tests\Feature;

use App\Models\Brand;
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

    public function test_product_with_a_3d_model_offers_the_3d_viewer(): void
    {
        $product = Product::factory()->withStock()->withModel()->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('Ver en 3D')
            ->assertSee($product->modelUrl(), false);
    }

    public function test_product_without_a_3d_model_does_not_offer_the_3d_viewer(): void
    {
        $product = Product::factory()->withStock()->create();

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertDontSee('Ver en 3D')
            ->assertDontSee('<model-viewer', false);
    }

    public function test_hidden_product_page_is_not_found(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->get(route('products.show', $product))->assertNotFound();
    }

    public function test_home_links_to_categories_and_new_arrivals_pages(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(route('categories.index'))
            ->assertSee(route('new-arrivals'))
            ->assertDontSee('href="#categorias"', false);
    }

    public function test_categories_page_lists_only_active_categories(): void
    {
        Category::factory()->create(['name' => 'Lencería']);
        Category::factory()->create(['name' => 'Temporada pasada', 'is_active' => false]);

        $this->get(route('categories.index'))
            ->assertOk()
            ->assertSee('Lencería')
            ->assertDontSee('Temporada pasada');
    }

    public function test_category_page_shows_its_products_and_keeps_filters(): void
    {
        $sport = Category::factory()->create(['name' => 'Deportiva', 'slug' => 'deportiva']);
        Product::factory()->for($sport)->withStock()->create(['name' => 'Calza tiro alto']);
        Product::factory()->for($sport)->withStock()->create(['name' => 'Top deportivo']);
        Product::factory()->withStock()->create(['name' => 'Mantel antimanchas']);

        $this->get(route('categories.show', $sport))
            ->assertOk()
            ->assertSee('Calza tiro alto')
            ->assertSee('Top deportivo')
            ->assertDontSee('Mantel antimanchas');

        $this->get(route('categories.show', ['category' => $sport, 'q' => 'calza']))
            ->assertSee('Calza tiro alto')
            ->assertDontSee('Top deportivo');
    }

    public function test_inactive_category_page_is_not_found(): void
    {
        $category = Category::factory()->create(['is_active' => false]);

        $this->get(route('categories.show', $category))->assertNotFound();
    }

    public function test_brand_pages_show_brands_and_their_products(): void
    {
        $brand = Brand::factory()->create(['name' => 'Marca Norte']);
        Product::factory()->for($brand)->withStock()->create(['name' => 'Campera Norte']);
        Product::factory()->withStock()->create(['name' => 'Toalla de baño']);

        $this->get(route('brands.index'))->assertOk()->assertSee('Marca Norte');

        $this->get(route('brands.show', $brand))
            ->assertOk()
            ->assertSee('Campera Norte')
            ->assertDontSee('Toalla de baño');
    }

    public function test_new_arrivals_page_shows_latest_active_products_first(): void
    {
        Product::factory()->withStock()->create(['name' => 'Remera vieja', 'created_at' => now()->subMonth()]);
        Product::factory()->withStock()->create(['name' => 'Remera nueva']);
        Product::factory()->inactive()->withStock()->create(['name' => 'Remera oculta']);

        $this->get(route('new-arrivals'))
            ->assertOk()
            ->assertSeeInOrder(['Remera nueva', 'Remera vieja'])
            ->assertDontSee('Remera oculta');
    }

    public function test_product_page_fills_related_products_from_other_categories(): void
    {
        $product = Product::factory()->withStock()->create(['name' => 'Sábana king']);
        Product::factory()->withStock()->create(['name' => 'Jean recto']);

        $this->get(route('products.show', $product))
            ->assertOk()
            ->assertSee('También te puede gustar')
            ->assertSee('Jean recto');
    }

    public function test_help_pages_exist_and_unknown_ones_do_not(): void
    {
        $this->get(route('pages.show', 'como-comprar'))->assertOk()->assertSee('Transferí el total');
        $this->get(route('pages.show', 'no-existe'))->assertNotFound();
    }
}
