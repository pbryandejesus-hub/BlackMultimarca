<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class BrandController extends Controller
{
    public function index(): View
    {
        return view('brands', [
            'brands' => Brand::orderBy('name')
                ->withCount(['products' => fn (Builder $query) => $query->active()])
                ->get(),
        ]);
    }
}
