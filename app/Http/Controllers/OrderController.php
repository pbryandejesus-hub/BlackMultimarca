<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function show(Request $request, Order $order): View
    {
        abort_unless($order->isVisibleTo($request->user(), $request->session()->get('placed_orders', [])), 404);

        return view('orders.show', [
            'order' => $order->load('items.product.images'),
            'settings' => Setting::allValues(),
        ]);
    }
}
