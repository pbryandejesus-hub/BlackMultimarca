<?php

namespace App\Http\Controllers;

use App\Actions\AttachReceipt;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class OrderReceiptController extends Controller
{
    public function store(Request $request, Order $order, AttachReceipt $attachReceipt): RedirectResponse
    {
        abort_unless($order->isVisibleTo($request->user(), $request->session()->get('placed_orders', [])), 404);

        if (! $order->status->acceptsReceipt()) {
            return back()->withErrors(['receipt' => 'Este pedido ya no admite cambios de comprobante.']);
        }

        $validated = $request->validate([
            'receipt' => ['required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
        ], [], ['receipt' => 'comprobante']);

        $attachReceipt->handle($order, $validated['receipt']);

        return redirect()->route('orders.show', $order)->with('status', 'Recibimos tu comprobante. Te avisamos cuando confirmemos el pago.');
    }
}
