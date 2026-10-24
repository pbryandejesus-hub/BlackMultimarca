<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.dashboard', [
            'pendingPaymentCount' => Order::where('status', OrderStatus::PendingPayment)->count(),
            'underReviewCount' => Order::where('status', OrderStatus::UnderReview)->count(),
            'monthSales' => (int) Order::whereIn('status', OrderStatus::confirmed())
                ->where('created_at', '>=', now()->startOfMonth())
                ->sum('total'),
            'activeProductsCount' => Product::active()->count(),
            'lowStockVariants' => ProductVariant::with('product')
                ->where('stock', '<=', 2)
                ->whereHas('product', fn ($query) => $query->active())
                ->orderBy('stock')
                ->take(10)
                ->get(),
            'latestOrders' => Order::latest()->take(8)->get(),
        ]);
    }
}
