<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        return view('account.favorites', [
            'products' => $request->user()->favoriteProducts()
                ->active()
                ->with(['images', 'brand', 'category'])
                ->latest('favorites.created_at')
                ->get(),
        ]);
    }

    /**
     * Agrega o quita un favorito. A un invitado lo manda a ingresar y después lo devuelve a la página donde estaba.
     */
    public function toggle(Request $request, Product $product): JsonResponse|RedirectResponse
    {
        abort_unless($product->is_active, 404);

        if (! $request->user()) {
            $request->session()->put('url.intended', url()->previous());

            return $request->wantsJson()
                ? response()->json(['redirect' => route('login')], 401)
                : redirect()->route('login')->with('status', 'Ingresá a tu cuenta para guardar favoritos.');
        }

        $isFavorite = $request->user()->toggleFavorite($product);

        return $request->wantsJson()
            ? response()->json(['favorite' => $isFavorite])
            : back()->with('status', $isFavorite ? "Guardaste {$product->name} en favoritos." : "Quitaste {$product->name} de favoritos.");
    }
}
