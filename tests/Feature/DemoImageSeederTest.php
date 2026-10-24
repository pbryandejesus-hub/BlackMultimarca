<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Database\Seeders\DemoImageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DemoImageSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_photos_are_attached_to_matching_products_and_categories(): void
    {
        Storage::fake('public');
        $category = Category::factory()->create(['slug' => 'deportiva']);
        $product = Product::factory()->for($category)->create(['slug' => 'calza-deportiva-tiro-alto']);
        $productWithoutPhoto = Product::factory()->create(['slug' => 'producto-sin-foto-de-ejemplo']);

        $this->seed(DemoImageSeeder::class);

        $this->assertCount(2, $product->images);
        Storage::disk('public')->assertExists($product->images->pluck('path')->all());
        $this->assertCount(0, $productWithoutPhoto->images);
        Storage::disk('public')->assertExists($category->fresh()->image_path);
    }

    public function test_products_that_already_have_photos_are_left_alone(): void
    {
        Storage::fake('public');
        $product = Product::factory()->create(['slug' => 'camisa-de-lino']);
        ProductImage::factory()->for($product)->create(['path' => 'products/foto-real.jpg']);

        $this->seed(DemoImageSeeder::class);

        $this->assertSame(['products/foto-real.jpg'], $product->images()->pluck('path')->all());
    }
}
