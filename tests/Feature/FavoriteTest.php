<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_toggles_a_favorite_with_json(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $this->actingAs($user)
            ->postJson(route('favorites.toggle', $product))
            ->assertOk()
            ->assertExactJson(['favorite' => true]);

        $this->assertTrue($user->favoriteProducts()->whereKey($product->id)->exists());

        $this->actingAs($user)
            ->postJson(route('favorites.toggle', $product))
            ->assertExactJson(['favorite' => false]);

        $this->assertFalse($user->favoriteProducts()->exists());
    }

    public function test_toggling_without_javascript_redirects_back(): void
    {
        $product = Product::factory()->create(['name' => 'Pantuflas']);

        $this->actingAs(User::factory()->create())
            ->from(route('products.show', $product))
            ->post(route('favorites.toggle', $product))
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('status', 'Guardaste Pantuflas en favoritos.');
    }

    public function test_guest_is_sent_to_login_and_returns_to_the_page(): void
    {
        $product = Product::factory()->create();

        $this->from(route('products.show', $product))
            ->post(route('favorites.toggle', $product))
            ->assertRedirect(route('login'));

        $this->assertSame(route('products.show', $product), session('url.intended'));

        $this->postJson(route('favorites.toggle', $product))
            ->assertUnauthorized()
            ->assertJson(['redirect' => route('login')]);
    }

    public function test_hidden_products_cannot_be_favorited(): void
    {
        $product = Product::factory()->inactive()->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('favorites.toggle', $product))
            ->assertNotFound();
    }

    public function test_favorites_page_lists_only_the_customers_active_favorites(): void
    {
        $user = User::factory()->create();
        $user->favoriteProducts()->attach([
            Product::factory()->create(['name' => 'Bata de toalla'])->id,
            Product::factory()->inactive()->create(['name' => 'Producto oculto'])->id,
        ]);
        User::factory()->create()->favoriteProducts()->attach(Product::factory()->create(['name' => 'Favorito ajeno']));

        $this->actingAs($user)
            ->get(route('favorites.index'))
            ->assertOk()
            ->assertSee('Bata de toalla')
            ->assertSee('aria-pressed="true"', false)
            ->assertDontSee('Producto oculto')
            ->assertDontSee('Favorito ajeno');
    }

    public function test_favorites_page_requires_login(): void
    {
        $this->get(route('favorites.index'))->assertRedirect(route('login'));
    }
}
