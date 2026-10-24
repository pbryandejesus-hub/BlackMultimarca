<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ProductVariant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OrderController extends Controller
{
    public function index(Request $request): View
    {
        $status = OrderStatus::tryFrom($request->string('estado')->toString());
        $search = trim($request->string('q')->toString());

        $orders = Order::query()
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($search !== '', fn (Builder $query) => $query->where(fn (Builder $query) => $query
                ->where('number', 'like', "%{$search}%")
                ->orWhere('customer_name', 'like', "%{$search}%")
                ->orWhere('cedula', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'currentStatus' => $status,
            'search' => $search,
        ]);
    }

    public function show(Order $order): View
    {
        return view('admin.orders.show', ['order' => $order->load(['items.product', 'user'])]);
    }

    public function update(Request $request, Order $order): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = OrderStatus::from($validated['status']);

        if ($order->status === OrderStatus::Cancelled && $newStatus !== OrderStatus::Cancelled) {
            return back()->withErrors(['status' => 'Un pedido cancelado no se puede reabrir: su stock ya fue devuelto.']);
        }

        DB::transaction(function () use ($order, $newStatus, $validated): void {
            if ($newStatus === OrderStatus::Cancelled && $order->status !== OrderStatus::Cancelled) {
                foreach ($order->items as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::whereKey($item->product_variant_id)->increment('stock', $item->quantity);
                    }
                }
            }

            $order->update([
                'status' => $newStatus,
                'admin_notes' => $validated['admin_notes'] ?? null,
            ]);
        });

        return back()->with('status', "Pedido {$order->number} actualizado: {$newStatus->label()}.");
    }

    public function receipt(Order $order): StreamedResponse
    {
        abort_unless($order->receipt_path && Storage::disk('local')->exists($order->receipt_path), 404);

        $extension = pathinfo($order->receipt_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->response($order->receipt_path, "comprobante-{$order->number}.{$extension}");
    }
}
