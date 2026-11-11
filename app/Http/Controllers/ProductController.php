<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function show(Product $product): View
    {
        abort_unless($product->is_active, 404);

        $product->load(['images', 'variants', 'brand', 'category']);

        $relatedProducts = Product::active()
            ->with(['images', 'brand', 'category'])
            ->whereBelongsTo($product->category)
            ->whereKeyNot($product->id)
            ->inRandomOrder()
            ->take(4)
            ->get();

        if ($relatedProducts->count() < 4) {
            $relatedProducts = $relatedProducts->concat(Product::active()
                ->with(['images', 'brand', 'category'])
                ->whereKeyNot([$product->id, ...$relatedProducts->modelKeys()])
                ->latest()
                ->take(4 - $relatedProducts->count())
                ->get());
        }

        return view('product', [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
        ]);
    }
}
