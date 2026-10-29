<?php

namespace App\Http\Controllers;

use App\Actions\PlaceOrder;
use App\Http\Requests\CheckoutRequest;
use App\Models\Order;
use App\Support\Cart;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function create(Request $request, Cart $cart): View|RedirectResponse
    {
        $deliveryMethod = $request->old('delivery_method', Order::DELIVERY_PICKUP);
        $summary = $cart->summary($deliveryMethod);

        if ($summary['lines']->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $cart->checkoutKey();
        $cart->setCheckoutSnapshot($summary['lines']->mapWithKeys(
            fn (array $line): array => [$line['variant']->id => $line['unit_price']],
        )->all());

        return view('checkout', [
            'summary' => $summary,
            'pickupSummary' => $cart->summary(Order::DELIVERY_PICKUP),
            'shippingSummary' => $cart->summary(Order::DELIVERY_SHIPPING),
            'deliveryMethod' => $deliveryMethod,
            'user' => $request->user(),
        ]);
    }

    public function store(CheckoutRequest $request, PlaceOrder $placeOrder, Cart $cart): RedirectResponse
    {
        $order = $placeOrder->handle(
            $request->safe()->except('receipt'),
            $request->user(),
            $cart->checkoutKey(),
            $request->file('receipt'),
        );

        $request->session()->put(
            'placed_orders',
            collect($request->session()->get('placed_orders', []))->push($order->id)->unique()->values()->all(),
        );

        return redirect()->route('orders.show', $order)->with('order_placed', true);
    }
}
