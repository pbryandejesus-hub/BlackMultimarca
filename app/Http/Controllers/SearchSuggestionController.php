<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchSuggestionController extends Controller
{
    /**
     * Hasta 6 productos activos que coinciden con lo que se va escribiendo en el buscador.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $term = trim($request->string('q')->toString());

        if (mb_strlen($term) < 2) {
            return response()->json(['products' => []]);
        }

        $products = Product::active()
            ->matching($term)
            ->with(['images', 'brand'])
            ->orderBy('name')
            ->take(6)
            ->get()
            ->map(fn (Product $product) => [
                'name' => $product->name,
                'brand' => $product->brand?->name,
                'price' => '₲ '.number_format($product->price, 0, ',', '.'),
                'image' => $product->images->first()?->url(),
                'url' => route('products.show', $product),
            ]);

        return response()->json(['products' => $products]);
    }
}
