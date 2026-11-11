<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        return view('categories', [
            'categories' => Category::active()
                ->withCount(['products' => fn (Builder $query) => $query->active()])
                ->get(),
        ]);
    }
}
