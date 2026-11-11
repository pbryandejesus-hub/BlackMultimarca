<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_suggests_matching_active_products_with_price_and_link(): void
    {
        $product = Product::factory()->create(['name' => 'Calza deportiva', 'price' => 119000]);
        Product::factory()->inactive()->create(['name' => 'Calza discontinuada']);
        Product::factory()->create(['name' => 'Mantel de lino']);

        $this->getJson(route('search.suggestions', ['q' => 'calza']))
            ->assertOk()
            ->assertJsonCount(1, 'products')
            ->assertJsonPath('products.0.name', 'Calza deportiva')
            ->assertJsonPath('products.0.price', '₲ 119.000')
            ->assertJsonPath('products.0.url', route('products.show', $product));
    }

    public function test_products_matching_by_name_come_first(): void
    {
        Product::factory()->create(['name' => 'Almohada', 'description' => 'Excelente calidad']);
        Product::factory()->create(['name' => 'Calza tiro alto', 'description' => 'Deportiva']);

        $this->getJson(route('search.suggestions', ['q' => 'cal']))
            ->assertJsonPath('products.0.name', 'Calza tiro alto')
            ->assertJsonPath('products.1.name', 'Almohada');
    }

    public function test_needs_at_least_two_letters(): void
    {
        Product::factory()->create(['name' => 'Remera']);

        $this->getJson(route('search.suggestions', ['q' => 'r']))->assertExactJson(['products' => []]);
    }

    public function test_returns_at_most_six_products(): void
    {
        Product::factory()->count(8)->sequence(fn ($sequence) => ['name' => "Remera {$sequence->index}"])->create();

        $this->getJson(route('search.suggestions', ['q' => 'remera']))->assertJsonCount(6, 'products');
    }
}
