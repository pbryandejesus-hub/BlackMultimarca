<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CatalogController extends Controller
{
    /**
     * Opciones de orden permitidas => [columna, dirección].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    private const SORTS = [
        'recientes' => ['created_at', 'desc'],
        'precio_menor' => ['price', 'asc'],
        'precio_mayor' => ['price', 'desc'],
        'nombre' => ['name', 'asc'],
    ];

    public function index(Request $request): View
    {
        $category = $request->filled('categoria') ? Category::where('slug', $request->string('categoria'))->first() : null;
        $brand = $request->filled('marca') ? Brand::where('slug', $request->string('marca'))->first() : null;
        $search = trim($request->string('q')->toString());
        $size = $request->string('talle')->toString();
        $sort = array_key_exists($request->string('orden')->toString(), self::SORTS) ? $request->string('orden')->toString() : 'recientes';
        [$sortColumn, $sortDirection] = self::SORTS[$sort];

        $products = Product::active()
            ->with(['images', 'brand', 'category'])
            ->when($category, fn (Builder $query) => $query->whereBelongsTo($category))
            ->when($brand, fn (Builder $query) => $query->whereBelongsTo($brand))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            }))
            ->when($size !== '', fn (Builder $query) => $query->whereHas('variants', fn (Builder $query) => $query->where('size', $size)->where('stock', '>', 0)))
            ->orderBy($sortColumn, $sortDirection)
            ->paginate(12)
            ->withQueryString();

        $sizes = ProductVariant::query()
            ->whereNotNull('size')
            ->where('stock', '>', 0)
            ->whereHas('product', fn (Builder $query) => $query->active()->when($category, fn (Builder $query) => $query->whereBelongsTo($category)))
            ->distinct()
            ->orderBy('size')
            ->pluck('size');

        return view('catalog', [
            'products' => $products,
            'categories' => Category::active()->get(),
            'brands' => Brand::orderBy('name')->get(),
            'sizes' => $sizes,
            'currentCategory' => $category,
            'currentBrand' => $brand,
            'search' => $search,
            'currentSize' => $size,
            'currentSort' => $sort,
            'sortOptions' => [
                'recientes' => 'Más recientes',
                'precio_menor' => 'Menor precio',
                'precio_mayor' => 'Mayor precio',
                'nombre' => 'Nombre A-Z',
            ],
        ]);
    }
}
