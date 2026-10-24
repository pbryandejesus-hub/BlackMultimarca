<x-layouts.admin title="Pedidos">
    <form method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <label class="field-label">Buscar
            <input type="search" name="q" value="{{ $search }}" class="field-input w-64" placeholder="N.º, nombre, cédula o teléfono">
        </label>
        <label class="field-label">Estado
            <select name="estado" class="field-input w-56">
                <option value="">Todos</option>
                @foreach (\App\Enums\OrderStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected($currentStatus === $status)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </label>
        <button type="submit" class="btn-outline">Filtrar</button>
    </form>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-line text-xs tracking-wide text-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Pedido</th>
                    <th class="px-4 py-3 font-semibold">Fecha</th>
                    <th class="px-4 py-3 font-semibold">Cliente</th>
                    <th class="px-4 py-3 font-semibold">Entrega</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3 text-right font-semibold">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($orders as $order)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-bronze underline">{{ $order->number }}</a>
                            @if ($order->receipt_path)
                                <span class="ml-1 bg-sky-100 px-1.5 py-0.5 text-[10px] font-semibold text-sky-900">Comprobante</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-3">{{ $order->customer_name }}<span class="block text-xs text-muted">C.I. {{ $order->cedula }}</span></td>
                        <td class="px-4 py-3">{{ $order->deliveryLabel() }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs font-semibold {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span></td>
                        <td class="px-4 py-3 text-right"><x-price :amount="$order->total" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-muted">No hay pedidos con estos filtros.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $orders->links() }}</div>
</x-layouts.admin>
