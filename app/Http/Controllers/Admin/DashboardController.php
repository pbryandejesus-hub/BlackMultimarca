<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\CarbonInterface;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $monthOrders = Order::whereIn('status', OrderStatus::confirmed())
            ->where('created_at', '>=', now()->startOfMonth())
            ->get(['total']);

        return view('admin.dashboard', [
            'pendingPaymentCount' => Order::where('status', OrderStatus::PendingPayment)->count(),
            'underReviewCount' => Order::where('status', OrderStatus::UnderReview)->count(),
            'monthSales' => (int) $monthOrders->sum('total'),
            'monthOrdersCount' => $monthOrders->count(),
            'monthAverageTicket' => (int) round($monthOrders->avg('total') ?? 0),
            'activeProductsCount' => Product::active()->count(),
            'lowStockVariants' => ProductVariant::with('product')
                ->where('stock', '<=', 2)
                ->whereHas('product', fn ($query) => $query->active())
                ->orderBy('stock')
                ->take(10)
                ->get(),
            'latestOrders' => Order::latest()->take(8)->get(),
            'charts' => [
                'dailySales' => ['type' => 'line', 'money' => true, ...$this->dailySales()],
                'monthlySales' => ['type' => 'bar', 'money' => true, ...$this->monthlySales()],
                'topProducts' => ['type' => 'bar', 'horizontal' => true, 'unit' => 'unidades', ...$this->topProducts()],
                'ordersByStatus' => ['type' => 'doughnut', ...$this->ordersByStatus()],
            ],
        ]);
    }

    /**
     * Ventas confirmadas de los últimos 30 días, incluyendo los días sin ventas.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function dailySales(): array
    {
        $totals = $this->confirmedTotalsSince(now()->subDays(29)->startOfDay(), 'Y-m-d');
        $days = collect(CarbonPeriod::create(now()->subDays(29), now()));

        return [
            'labels' => $days->map(fn ($day) => $day->format('d/m'))->all(),
            'values' => $days->map(fn ($day) => $totals->get($day->format('Y-m-d'), 0))->all(),
        ];
    }

    /**
     * Ventas confirmadas de los últimos 12 meses, incluyendo los meses sin ventas.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function monthlySales(): array
    {
        $totals = $this->confirmedTotalsSince(now()->subMonths(11)->startOfMonth(), 'Y-m');
        $months = collect(CarbonPeriod::create(now()->subMonths(11)->startOfMonth(), '1 month', now()));

        return [
            'labels' => $months->map(fn ($month) => ucfirst($month->translatedFormat('M y')))->all(),
            'values' => $months->map(fn ($month) => $totals->get($month->format('Y-m'), 0))->all(),
        ];
    }

    /**
     * Suma de ventas confirmadas agrupadas por fecha. Se agrupa en PHP para funcionar igual en SQLite y MySQL.
     *
     * @return Collection<string, int>
     */
    private function confirmedTotalsSince(CarbonInterface $since, string $groupFormat): Collection
    {
        return Order::whereIn('status', OrderStatus::confirmed())
            ->where('created_at', '>=', $since)
            ->get(['total', 'created_at'])
            ->groupBy(fn (Order $order) => $order->created_at->format($groupFormat))
            ->map(fn (Collection $orders) => (int) $orders->sum('total'));
    }

    /**
     * Los 5 productos con más unidades vendidas en pedidos confirmados.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function topProducts(): array
    {
        $rows = OrderItem::query()
            ->whereHas('order', fn (Builder $query) => $query->whereIn('status', OrderStatus::confirmed()))
            ->selectRaw('product_name, SUM(quantity) as units')
            ->groupBy('product_name')
            ->orderByDesc('units')
            ->take(5)
            ->toBase()
            ->get();

        return [
            'labels' => $rows->pluck('product_name')->all(),
            'values' => $rows->pluck('units')->map(fn ($units) => (int) $units)->all(),
        ];
    }

    /**
     * Cantidad de pedidos en cada estado.
     *
     * @return array{labels: list<string>, values: list<int>}
     */
    private function ordersByStatus(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as orders_count')
            ->groupBy('status')
            ->toBase()
            ->pluck('orders_count', 'status');

        return [
            'labels' => array_map(fn (OrderStatus $status) => $status->label(), OrderStatus::cases()),
            'values' => array_map(fn (OrderStatus $status) => (int) ($counts[$status->value] ?? 0), OrderStatus::cases()),
        ];
    }
}
