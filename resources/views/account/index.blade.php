<x-layouts.store title="Mi cuenta">
    <div class="mx-auto max-w-5xl px-4 py-10 md:px-6">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="eyebrow">Mi cuenta</span>
                <h1 class="mt-2 font-display text-3xl font-medium">Hola, {{ auth()->user()->name }}</h1>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-outline">Cerrar sesión</button>
            </form>
        </div>

        <h2 class="mb-4 font-display text-xl font-semibold">Mis pedidos</h2>
        @if ($orders->isEmpty())
            <div class="card flex flex-col items-center gap-4 px-6 py-12 text-center">
                <p class="text-muted">Todavía no hiciste pedidos.</p>
                <a href="{{ route('catalog') }}" class="btn-dark">Ir a la tienda</a>
            </div>
        @else
            <div class="card overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="border-b border-line text-xs tracking-wide text-muted uppercase">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Pedido</th>
                            <th class="px-4 py-3 font-semibold">Fecha</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 text-right font-semibold">Total</th>
                            <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach ($orders as $order)
                            <tr>
                                <td class="px-4 py-3 font-semibold">{{ $order->number }}</td>
                                <td class="px-4 py-3">{{ $order->created_at->format('d/m/Y') }}</td>
                                <td class="px-4 py-3"><span class="px-2.5 py-1 text-xs font-semibold {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span></td>
                                <td class="px-4 py-3 text-right"><x-price :amount="$order->total" /></td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('orders.show', $order) }}" class="text-bronze underline">Ver</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-6">{{ $orders->links() }}</div>
        @endif
    </div>
</x-layouts.store>
