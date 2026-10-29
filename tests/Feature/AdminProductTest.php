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

    public function test_low_stock_entry_links_to_its_variant_row_in_the_edit_form(): void
    {
        $variant = ProductVariant::factory()->create(['stock' => 0]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertSee(route('admin.products.edit', $variant->product).'#variante-'.$variant->id, false);

        $this->actingAs($admin)
            ->get(route('admin.products.edit', $variant->product))
            ->assertSee('data-variant-id="'.$variant->id.'"', false);
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

    public function test_admin_uploads_a_3d_model_and_replacing_it_deletes_the_old_file(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            ...$this->updatePayload($product),
            'model' => UploadedFile::fake()->create('remera.glb', 500, 'model/gltf-binary'),
        ])->assertSessionHasNoErrors();

        $firstPath = $product->fresh()->model_path;
        $this->assertStringEndsWith('.glb', $firstPath);
        Storage::disk('public')->assertExists($firstPath);

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            ...$this->updatePayload($product),
            'model' => UploadedFile::fake()->create('remera-nueva.glb', 500, 'application/octet-stream'),
        ])->assertSessionHasNoErrors();

        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($product->fresh()->model_path);
    }

    public function test_admin_can_remove_the_3d_model(): void
    {
        Storage::fake('public');
        $product = Product::factory()->withModel()->create();
        Storage::disk('public')->put($product->model_path, 'glb');

        $this->actingAs(User::factory()->admin()->create())->put(route('admin.products.update', $product), [
            ...$this->updatePayload($product),
            'remove_model' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($product->fresh()->model_path);
        Storage::disk('public')->assertMissing($product->model_path);
    }

    public function test_the_3d_model_must_be_a_glb_file_of_at_most_20_mb(): void
    {
        $product = Product::factory()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            ...$this->updatePayload($product),
            'model' => UploadedFile::fake()->create('virus.exe', 100),
        ])->assertSessionHasErrors('model');

        $this->actingAs($admin)->put(route('admin.products.update', $product), [
            ...$this->updatePayload($product),
            'model' => UploadedFile::fake()->create('enorme.glb', 20481),
        ])->assertSessionHasErrors('model');
    }

    public function test_deleting_a_product_deletes_its_3d_model(): void
    {
        Storage::fake('public');
        $product = Product::factory()->withModel()->create();
        Storage::disk('public')->put($product->model_path, 'glb');

        $this->actingAs(User::factory()->admin()->create())->delete(route('admin.products.destroy', $product));

        Storage::disk('public')->assertMissing($product->model_path);
    }

    /**
     * @return array<string, mixed>
     */
    private function updatePayload(Product $product): array
    {
        return [
            'category_id' => $product->category_id,
            'name' => $product->name,
            'price' => $product->price,
            'variants' => [['size' => 'M', 'stock' => 1]],
        ];
    }
}
