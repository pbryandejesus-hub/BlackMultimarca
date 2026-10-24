<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BrandRequest;
use App\Models\Brand;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('admin.brands.index', [
            'brands' => Brand::withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.brands.form', ['brand' => new Brand]);
    }

    public function store(BrandRequest $request): RedirectResponse
    {
        $brand = new Brand($request->safe()->except('logo'));
        $brand->logo_path = $request->file('logo')?->store('brands', 'public');
        $brand->save();

        return redirect()->route('admin.brands.index')->with('status', 'Marca creada.');
    }

    public function edit(Brand $brand): View
    {
        return view('admin.brands.form', ['brand' => $brand]);
    }

    public function update(BrandRequest $request, Brand $brand): RedirectResponse
    {
        $brand->fill($request->safe()->except('logo'));

        if ($request->hasFile('logo')) {
            if ($brand->logo_path) {
                Storage::disk('public')->delete($brand->logo_path);
            }

            $brand->logo_path = $request->file('logo')->store('brands', 'public');
        }

        $brand->save();

        return redirect()->route('admin.brands.index')->with('status', 'Marca actualizada.');
    }

    public function destroy(Brand $brand): RedirectResponse
    {
        if ($brand->logo_path) {
            Storage::disk('public')->delete($brand->logo_path);
        }

        $brand->delete();

        return redirect()->route('admin.brands.index')->with('status', 'Marca eliminada. Sus productos quedaron sin marca.');
    }
}
