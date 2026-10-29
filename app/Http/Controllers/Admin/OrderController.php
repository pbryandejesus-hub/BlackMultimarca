<?php

namespace App\Http\Controllers\Admin;

use App\Actions\UpdateOrderStatus;
use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Mail\OrderStatusChanged;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

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

    public function update(Request $request, Order $order, UpdateOrderStatus $updateOrderStatus): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::enum(OrderStatus::class)],
            'reason' => [Rule::requiredIf(fn (): bool => $request->string('status')->toString() !== $order->status->value), 'nullable', 'string', 'max:500'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $newStatus = OrderStatus::from($validated['status']);

        $statusChanged = $order->status !== $newStatus;

        $order = $updateOrderStatus->handle(
            $order,
            $newStatus,
            $request->user(),
            $validated['reason'] ?? null,
            $validated['admin_notes'] ?? null,
        );

        if ($statusChanged && filled($order->email)) {
            $this->notifyCustomer($order);
        }

        return back()->with('status', "Pedido {$order->number} actualizado: {$newStatus->label()}.");
    }

    public function receipt(Order $order): StreamedResponse
    {
        abort_unless($order->receipt_path && Storage::disk('local')->exists($order->receipt_path), 404);

        $extension = pathinfo($order->receipt_path, PATHINFO_EXTENSION);

        return Storage::disk('local')->response($order->receipt_path, "comprobante-{$order->number}.{$extension}");
    }

    /**
     * Avisa al cliente por email. Un fallo del servidor de correo no debe impedir guardar el cambio de estado.
     */
    private function notifyCustomer(Order $order): void
    {
        try {
            Mail::to($order->email)->send(new OrderStatusChanged($order->load('items')));
        } catch (Throwable $exception) {
            report($exception);
        }
    }
}
