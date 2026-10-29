<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_a_product_with_variants_and_photos(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create();

        $this->actingAs(User::factory()->admin()->create())->post(route('admin.products.store'), [
            'category_id' => $category->id,
            'name' => 'Calza deportiva tiro alto',
            'price' => '119.000',
            'is_active' => '1',
            'variants' => [
                ['size' => 'S', 'color' => 'Negro', 'color_hex' => '#151515', 'stock' => 3],
                ['size' => 'M', 'color' => 'Negro', 'color_hex' => '#151515', 'stock' => 5],
            ],
            'images' => [UploadedFile::fake()->create('foto.jpg', 300, 'image/jpeg')],
        ])->assertRedirect();

        $product = Product::sole();
        $this->assertSame('calza-deportiva-tiro-alto', $product->slug);
        $this->assertSame(119000, $product->price);
        $this->assertSame(8, $product->totalStock());
        $this->assertCount(1, $product->images);
        Storage::disk('public')->assertExists($product->images->first()->path);
    }

    public function test_updating_a_product_removes_the_variants_left_out_of_the_form(): void
    {
        $product = Product::factory()->create();
        $kept = ProductVariant::factory()->for($product)->create(['size' => 'S', 'stock' => 1]);
        $removed = ProductVariant::factory()->for($product)->create(['size' => 'M']);

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'price' => $product->price,
            'variants' => [
                ['id' => $kept->id, 'size' => 'S', 'stock' => 7],
                ['size' => 'XL', 'stock' => 2],
            ],
        ])->assertRedirect(route('admin.products.edit', $product));

        $this->assertSame(7, $kept->fresh()->stock);
        $this->assertModelMissing($removed);
        $this->assertSame(['S', 'XL'], $product->variants()->pluck('size')->all());
    }

    public function test_updating_a_product_rejects_a_variant_owned_by_another_product(): void
    {
        $product = Product::factory()->create();
        $foreignVariant = ProductVariant::factory()->create(['stock' => 6]);
        $originalVariant = ProductVariant::factory()->for($product)->create(['stock' => 4]);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.products.update', $product), [
                'category_id' => $product->category_id,
                'name' => $product->name,
                'price' => $product->price,
                'variants' => [
                    ['id' => $foreignVariant->id, 'size' => 'M', 'stock' => 8],
                ],
            ])
            ->assertSessionHasErrors('variants.0.id');

        $this->assertSame(4, $originalVariant->fresh()->stock);
        $this->assertSame(6, $foreignVariant->fresh()->stock);
        $this->assertSame(1, $product->variants()->count());
    }

    public function test_edit_form_lists_the_existing_variants(): void
    {
        $variant = ProductVariant::factory()->create(['size' => '2 plazas', 'color' => 'Gris topo']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.products.edit', $variant->product))
            ->assertOk()
            ->assertSee('value="2 plazas"', false)
            ->assertSee('value="Gris topo"', false);
    }

    public function test_a_product_needs_at_least_one_variant(): void
    {
        $this->actingAs(User::factory()->admin()->create())->post(route('admin.products.store'), [
            'category_id' => Category::factory()->create()->id,
            'name' => 'Sin variantes',
            'price' => 1000,
        ])->assertSessionHasErrors('variants');
    }

    public function test_a_category_with_products_cannot_be_deleted(): void
    {
        $product = Product::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('admin.categories.destroy', $product->category))
            ->assertSessionHasErrors('category');

        $this->assertModelExists($product->category);
    }
}
