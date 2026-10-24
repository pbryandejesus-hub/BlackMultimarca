<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Datos de ejemplo para ver la tienda funcionando. Los productos y marcas son de muestra:
 * se reemplazan desde el panel de administración.
 */
class StoreSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrNew(['email' => 'admin@blackmultimarca.com'])
            ->forceFill(['name' => 'Administración', 'password' => 'password', 'is_admin' => true])
            ->save();

        $categories = collect([
            ['Ropa', 'Mujer · Hombre'],
            ['Deportiva', 'Training · Running'],
            ['Lencería', 'Conjuntos · Bodies'],
            ['Medias', 'Sueltas · Packs'],
            ['Blanquería', 'Sábanas · Almohadas'],
            ['Hogar', 'Manteles · Toallas'],
        ])->mapWithKeys(fn (array $category, int $index) => [
            $category[0] => Category::updateOrCreate(
                ['slug' => Str::slug($category[0])],
                ['name' => $category[0], 'subtitle' => $category[1], 'position' => $index + 1, 'is_active' => true],
            ),
        ]);

        $brands = collect(['Marca Ejemplo A', 'Marca Ejemplo B', 'Marca Ejemplo C'])
            ->map(fn (string $name) => Brand::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name]));

        $clothingSizes = ['S', 'M', 'L', 'XL'];
        $colors = ['Negro' => '#151515', 'Blanco' => '#f4f1ea', 'Bordó' => '#6b1e2a', 'Gris topo' => '#8a8178', 'Beige' => '#d8c7a8'];

        $products = [
            ['Ropa', 'Remera básica de algodón', 65000, null, $clothingSizes, ['Negro', 'Blanco'], true],
            ['Ropa', 'Jean mom tiro alto', 189000, 220000, ['36', '38', '40', '42'], ['Azul' => '#3b4a6b'], false],
            ['Ropa', 'Camisa de lino', 159000, null, $clothingSizes, ['Blanco', 'Beige'], false],
            ['Deportiva', 'Calza deportiva tiro alto', 119000, null, ['XS', 'S', 'M', 'L', 'XL'], ['Negro', 'Bordó', 'Gris topo'], true],
            ['Deportiva', 'Top deportivo', 79000, null, $clothingSizes, ['Negro', 'Gris topo'], false],
            ['Deportiva', 'Campera rompeviento', 249000, 289000, $clothingSizes, ['Negro'], true],
            ['Lencería', 'Conjunto de encaje', 135000, null, ['S', 'M', 'L'], ['Negro', 'Bordó'], true],
            ['Lencería', 'Pijama de satén', 149000, null, ['S', 'M', 'L'], ['Gris perla' => '#c9c6d6', 'Negro' => '#151515'], true],
            ['Medias', 'Pack de medias deportivas x3', 45000, null, ['35-38', '39-42'], ['Blanco', 'Negro'], false],
            ['Medias', 'Medias de algodón (par)', 15000, null, ['35-38', '39-42'], ['Negro', 'Gris topo', 'Blanco'], false],
            ['Blanquería', 'Juego de sábanas 2 plazas', 210000, null, ['1 plaza', '2 plazas', 'Queen'], ['Blanco', 'Gris topo'], false],
            ['Blanquería', 'Almohada de fibra siliconada', 69000, null, [], [], false],
            ['Hogar', 'Mantel rectangular antimanchas', 98000, null, ['1,50 × 2,00 m', '1,50 × 2,50 m'], ['Beige', 'Blanco'], true],
            ['Hogar', 'Toallón de algodón', 85000, null, [], ['Blanco', 'Gris topo', 'Beige'], false],
        ];

        foreach ($products as $index => [$categoryName, $name, $price, $comparePrice, $sizes, $productColors, $featured]) {
            $product = Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => $categories[$categoryName]->id,
                    'brand_id' => $brands[$index % $brands->count()]->id,
                    'name' => $name,
                    'description' => 'Producto de ejemplo. Reemplazá esta descripción desde el panel con materiales, calce y cuidados.',
                    'price' => $price,
                    'compare_price' => $comparePrice,
                    'is_active' => true,
                    'is_featured' => $featured,
                ],
            );

            if ($product->variants()->exists()) {
                continue;
            }

            $colorOptions = collect($productColors)->mapWithKeys(fn ($hex, $color) => is_int($color) ? [$hex => $colors[$hex]] : [$color => $hex]);

            foreach ($colorOptions->isEmpty() ? [null => null] : $colorOptions as $color => $hex) {
                foreach ($sizes === [] ? [null] : $sizes as $sizeIndex => $size) {
                    $product->variants()->create([
                        'size' => $size,
                        'color' => $color ?: null,
                        'color_hex' => $hex,
                        'stock' => ($index + $sizeIndex) % 5 === 0 ? 0 : 3 + (($index + $sizeIndex) % 6),
                    ]);
                }
            }
        }
    }
}
