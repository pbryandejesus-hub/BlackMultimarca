<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $productQuery = fn () => Product::active()->with(['images', 'brand', 'category']);

        return view('home', [
            'categories' => Category::active()->get(),
            'featuredProducts' => $productQuery()->where('is_featured', true)->latest()->take(8)->get(),
            'newProducts' => $productQuery()->latest()->take(8)->get(),
            'brands' => Brand::orderBy('name')->get(),
        ]);
    }
}
