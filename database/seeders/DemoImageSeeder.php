<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Carga fotos de ejemplo (Unsplash, licencia libre) a los productos y categorías que no tienen.
 * Las fotos están en database/seeders/demo-images y se nombran por el slug:
 * "{slug}.jpg" es la principal y "{slug}-2.jpg", "{slug}-3.jpg"… las siguientes.
 */
class DemoImageSeeder extends Seeder
{
    public function run(): void
    {
        $source = database_path('seeders/demo-images');
        $disk = Storage::disk('public');

        foreach (Product::doesntHave('images')->get() as $product) {
            $files = array_filter(
                [
                    "{$source}/products/{$product->slug}.jpg",
                    ...glob("{$source}/products/{$product->slug}-[0-9].jpg") ?: [],
                ],
                'is_file',
            );

            foreach (array_values($files) as $position => $file) {
                $path = 'products/demo-'.basename($file);
                $disk->put($path, file_get_contents($file));
                $product->images()->create(['path' => $path, 'position' => $position]);
            }
        }

        foreach (Category::whereNull('image_path')->get() as $category) {
            $file = "{$source}/categories/{$category->slug}.jpg";

            if (is_file($file)) {
                $path = 'categories/demo-'.basename($file);
                $disk->put($path, file_get_contents($file));
                $category->update(['image_path' => $path]);
            }
        }
    }
}
