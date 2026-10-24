<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(private Cart $cart) {}

    public function index(): View
    {
        return view('cart', ['summary' => $this->cart->summary()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ], [
            'variant_id.required' => 'Elegí un talle y color antes de agregar al carrito.',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($validated['variant_id']);

        abort_unless($variant->product->is_active, 404);

        if ($variant->stock < 1) {
            return back()->withErrors(['variant_id' => 'Esta variante no tiene stock disponible.']);
        }

        $finalQuantity = $this->cart->add($variant, $validated['quantity']);

        $message = $finalQuantity < $validated['quantity']
            ? "Agregaste {$variant->product->name}. Solo hay {$variant->stock} unidades disponibles."
            : "Agregaste {$variant->product->name} al carrito.";

        return back()->with('status', $message)->with('cart_added', true);
    }

    public function update(Request $request, ProductVariant $variant): RedirectResponse
    {
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:99'],
        ]);

        if ($validated['quantity'] === 0) {
            $this->cart->remove($variant->id);
        } else {
            $this->cart->update($variant, $validated['quantity']);
        }

        return redirect()->route('cart.index');
    }

    public function destroy(ProductVariant $variant): RedirectResponse
    {
        $this->cart->remove($variant->id);

        return redirect()->route('cart.index')->with('status', 'Producto quitado del carrito.');
    }
}
