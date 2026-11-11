<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class StockAlertController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'contact' => ['required', 'string', 'max:120', function (string $attribute, string $value, Closure $fail): void {
                $isEmail = filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
                $isPhone = preg_match('/^\+?[\d\s\-()]{6,20}$/', $value) === 1;

                if (! $isEmail && ! $isPhone) {
                    $fail('Escribí un número de WhatsApp o un email válido.');
                }
            }],
        ], [
            'variant_id.required' => 'Elegí el talle y color que querés que te avisemos.',
        ]);

        $variant = ProductVariant::with('product')->findOrFail($validated['variant_id']);

        abort_unless($variant->product->is_active, 404);

        if ($variant->stock > 0) {
            return back()->with('status', 'Esa combinación ya tiene stock: podés agregarla al carrito.');
        }

        $contact = trim(mb_strtolower($validated['contact']));

        $variant->stockAlerts()->updateOrCreate(['contact' => $contact], ['notified_at' => null]);

        return back()->with('status', "Listo, te avisamos a {$contact} cuando vuelva {$variant->product->name} ({$variant->label()}).");
    }
}
