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
        $category = $this->requestedCategory($request);

        $breadcrumbs = [['Inicio', route('home')], ['Tienda', $category ? route('catalog') : null]];
        if ($category) {
            $breadcrumbs[] = [$category->name, null];
        }

        return $this->renderCatalog($request, $category, $this->requestedBrand($request), [
            'breadcrumbs' => $breadcrumbs,
            'filterAction' => route('catalog'),
        ]);
    }

    public function category(Request $request, Category $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->renderCatalog($request, $category, $this->requestedBrand($request), [
            'breadcrumbs' => [['Inicio', route('home')], ['Categorías', route('categories.index')], [$category->name, null]],
            'filterAction' => route('categories.show', $category),
            'lockedCategory' => true,
        ]);
    }

    public function brand(Request $request, Brand $brand): View
    {
        return $this->renderCatalog($request, $this->requestedCategory($request), $brand, [
            'title' => $brand->name,
            'eyebrow' => 'Marca',
            'breadcrumbs' => [['Inicio', route('home')], ['Marcas', route('brands.index')], [$brand->name, null]],
            'filterAction' => route('brands.show', $brand),
            'lockedBrand' => true,
        ]);
    }

    public function newArrivals(Request $request): View
    {
        return $this->renderCatalog($request, $this->requestedCategory($request), $this->requestedBrand($request), [
            'title' => 'Novedades',
            'eyebrow' => 'Recién llegados',
            'breadcrumbs' => [['Inicio', route('home')], ['Novedades', null]],
            'filterAction' => route('new-arrivals'),
        ]);
    }

    private function requestedCategory(Request $request): ?Category
    {
        return $request->filled('categoria') ? Category::where('slug', $request->string('categoria'))->first() : null;
    }

    private function requestedBrand(Request $request): ?Brand
    {
        return $request->filled('marca') ? Brand::where('slug', $request->string('marca'))->first() : null;
    }

    /**
     * Lista los productos de la categoría y marca recibidas aplicando búsqueda, talle y orden del pedido.
     *
     * @param  array{title?: string, eyebrow?: string, breadcrumbs: list<array{0: string, 1: ?string}>, filterAction: string, lockedCategory?: bool, lockedBrand?: bool}  $page
     */
    private function renderCatalog(Request $request, ?Category $category, ?Brand $brand, array $page): View
    {
        $search = trim($request->string('q')->toString());
        $size = $request->string('talle')->toString();
        $sort = array_key_exists($request->string('orden')->toString(), self::SORTS) ? $request->string('orden')->toString() : 'recientes';
        [$sortColumn, $sortDirection] = self::SORTS[$sort];
        $lockedCategory = $page['lockedCategory'] ?? false;
        $lockedBrand = $page['lockedBrand'] ?? false;

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
            ->whereHas('product', fn (Builder $query) => $query->active()
                ->when($category, fn (Builder $query) => $query->whereBelongsTo($category))
                ->when($brand, fn (Builder $query) => $query->whereBelongsTo($brand)))
            ->distinct()
            ->orderBy('size')
            ->pluck('size');

        $activeFilters = array_values(array_filter([
            $search !== '' ? ['“'.$search.'”', $request->fullUrlWithoutQuery(['q', 'page'])] : null,
            $category && ! $lockedCategory ? [$category->name, $request->fullUrlWithoutQuery(['categoria', 'page'])] : null,
            $brand && ! $lockedBrand ? [$brand->name, $request->fullUrlWithoutQuery(['marca', 'page'])] : null,
            $size !== '' ? ['Talle '.$size, $request->fullUrlWithoutQuery(['talle', 'page'])] : null,
        ]));

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
            'pageTitle' => $page['title'] ?? $category?->name ?? ($search !== '' ? 'Resultados para “'.$search.'”' : 'Todos los productos'),
            'pageEyebrow' => $page['eyebrow'] ?? null,
            'bannerCategory' => isset($page['title']) ? null : $category,
            'breadcrumbs' => $page['breadcrumbs'],
            'filterAction' => $page['filterAction'],
            'lockedCategory' => $lockedCategory,
            'lockedBrand' => $lockedBrand,
            'activeFilters' => $activeFilters,
        ]);
    }
}
