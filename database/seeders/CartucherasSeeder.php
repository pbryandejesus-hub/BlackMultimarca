<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Categoría Cartucheras con cartucheras de tela de muestra. Solo crea lo que falta:
 * los productos ya cargados (y lo que se haya editado en el panel) no se tocan.
 */
class CartucherasSeeder extends Seeder
{
    public function run(): void
    {
        $category = Category::firstOrCreate(
            ['slug' => 'cartucheras'],
            [
                'name' => 'Cartucheras',
                'subtitle' => 'De tela · Escolares y oficina',
                'position' => (int) Category::max('position') + 1,
                'is_active' => true,
                'size_guide' => "Modelo | Largo | Alto | Ancho\nCon cierre | 21 cm | 9 cm | 4 cm\nDoble cierre | 22 cm | 11 cm | 7 cm\nCanopla | 20 cm | 8 cm | 6 cm\nGrande | 24 cm | 13 cm | 8 cm",
            ],
        );

        $colors = [
            'Negro' => '#151515',
            'Beige' => '#d8c7a8',
            'Bordó' => '#6b1e2a',
            'Gris topo' => '#8a8178',
            'Azul marino' => '#26324d',
            'Rosa viejo' => '#c99a9a',
            'Verde oliva' => '#6b6b3a',
        ];

        $products = [
            ['Cartuchera de tela con cierre', 35000, null, ['Negro', 'Beige', 'Bordó'], true, 'Cartuchera simple de tela resistente con cierre metálico. Entra lápices, birome, regla corta y goma. Medidas: 21 × 9 × 4 cm.'],
            ['Cartuchera de tela doble cierre', 49000, null, ['Negro', 'Gris topo', 'Azul marino'], true, 'Dos compartimentos con cierre independiente para separar lápices de colores y útiles. Tela gruesa lavable a mano. Medidas: 22 × 11 × 7 cm.'],
            ['Cartuchera canopla de tela', 42000, 50000, ['Rosa viejo', 'Verde oliva', 'Beige'], false, 'Formato canopla con base plana que se mantiene parada sobre el escritorio. Tela tipo lona con forro interior. Medidas: 20 × 8 × 6 cm.'],
            ['Cartuchera grande de tela', 55000, null, ['Negro', 'Bordó'], false, 'Cartuchera amplia con bolsillo interno y elástico para marcadores. También sirve como neceser de viaje. Medidas: 24 × 13 × 8 cm.'],
        ];

        foreach ($products as $index => [$name, $price, $comparePrice, $productColors, $featured, $description]) {
            $product = Product::firstOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => $category->id,
                    'name' => $name,
                    'description' => $description,
                    'price' => $price,
                    'compare_price' => $comparePrice,
                    'is_active' => true,
                    'is_featured' => $featured,
                ],
            );

            if ($product->variants()->exists()) {
                continue;
            }

            foreach ($productColors as $colorIndex => $color) {
                $product->variants()->create([
                    'size' => null,
                    'color' => $color,
                    'color_hex' => $colors[$color],
                    'stock' => ($index + $colorIndex) % 4 === 3 ? 0 : 4 + (($index + $colorIndex) % 5),
                ]);
            }
        }
    }
}
