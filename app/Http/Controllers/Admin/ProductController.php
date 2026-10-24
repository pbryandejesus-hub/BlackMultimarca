<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ProductRequest;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim($request->string('q')->toString());

        $products = Product::query()
            ->with(['category', 'brand', 'images'])
            ->withSum('variants', 'stock')
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")))
            ->when($request->filled('categoria'), fn (Builder $query) => $query->where('category_id', $request->integer('categoria')))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.products.index', [
            'products' => $products,
            'categories' => Category::orderBy('name')->get(),
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.products.form', [
            'product' => new Product(['is_active' => true]),
            ...$this->formOptions(),
        ]);
    }

    public function store(ProductRequest $request): RedirectResponse
    {
        $product = DB::transaction(function () use ($request): Product {
            $product = Product::create($request->safe()->except(['images', 'variants']));
            $this->syncVariants($product, $request->validated('variants'));

            return $product;
        });

        $this->storeImages($product, $request->file('images', []));

        return redirect()->route('admin.products.edit', $product)->with('status', 'Producto creado.');
    }

    public function edit(Product $product): View
    {
        return view('admin.products.form', [
            'product' => $product->load(['images', 'variants']),
            ...$this->formOptions(),
        ]);
    }

    public function update(ProductRequest $request, Product $product): RedirectResponse
    {
        DB::transaction(function () use ($request, $product): void {
            $product->update($request->safe()->except(['images', 'variants']));
            $this->syncVariants($product, $request->validated('variants'));
        });

        $this->storeImages($product, $request->file('images', []));

        return redirect()->route('admin.products.edit', $product)->with('status', 'Producto actualizado.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $paths = $product->images()->pluck('path')->all();

        $product->delete();
        Storage::disk('public')->delete($paths);

        return redirect()->route('admin.products.index')->with('status', 'Producto eliminado.');
    }

    /**
     * @return array{categories: Collection<int, Category>, brands: Collection<int, Brand>}
     */
    private function formOptions(): array
    {
        return [
            'categories' => Category::orderBy('position')->orderBy('name')->get(),
            'brands' => Brand::orderBy('name')->get(),
        ];
    }

    /**
     * Actualiza las variantes existentes, crea las nuevas y borra las que se quitaron del formulario.
     *
     * @param  array<int, array{id?: ?int, size?: ?string, color?: ?string, color_hex?: ?string, stock: int}>  $rows
     */
    private function syncVariants(Product $product, array $rows): void
    {
        $existing = $product->variants()->get()->keyBy('id');
        $keptIds = [];

        foreach ($rows as $row) {
            $attributes = [
                'size' => $row['size'] ?? null,
                'color' => $row['color'] ?? null,
                'color_hex' => filled($row['color'] ?? null) ? ($row['color_hex'] ?? null) : null,
                'stock' => $row['stock'],
            ];

            $variant = isset($row['id']) ? $existing->get((int) $row['id']) : null;

            if ($variant) {
                $variant->update($attributes);
            } else {
                $variant = $product->variants()->create($attributes);
            }

            $keptIds[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @param  array<int, UploadedFile>  $files
     */
    private function storeImages(Product $product, array $files): void
    {
        $position = (int) $product->images()->max('position');

        foreach ($files as $file) {
            $product->images()->create([
                'path' => $file->store('products', 'public'),
                'position' => ++$position,
            ]);
        }
    }
}
