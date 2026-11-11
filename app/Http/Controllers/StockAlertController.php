<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'email' => ['required', 'email', 'max:255'],
        ], [
            'variant_id.required' => 'Elegí el talle y color que querés que te avisemos.',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($validated['variant_id']);

        abort_unless($variant->product->is_active, 404);

        if ($variant->stock > 0) {
            return back()->with('status', 'Esa combinación ya tiene stock: podés agregarla al carrito.');
        }

        $variant->stockAlerts()->updateOrCreate(
            ['email' => strtolower($validated['email'])],
            ['notified_at' => null],
        );

        return back()->with('status', "Listo, te avisamos a {$validated['email']} cuando vuelva {$variant->product->name} ({$variant->label()}).");
    }
}
