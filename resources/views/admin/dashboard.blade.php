<x-layouts.admin title="Resumen">
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <a href="{{ route('admin.orders.index', ['estado' => 'en_revision']) }}" class="card flex flex-col gap-1 p-5 hover:border-bronze">
            <span class="text-xs tracking-wide text-muted uppercase">Comprobantes por revisar</span>
            <span class="font-display text-3xl font-semibold">{{ $underReviewCount }}</span>
        </a>
        <a href="{{ route('admin.orders.index', ['estado' => 'pendiente_pago']) }}" class="card flex flex-col gap-1 p-5 hover:border-bronze">
            <span class="text-xs tracking-wide text-muted uppercase">Esperando transferencia</span>
            <span class="font-display text-3xl font-semibold">{{ $pendingPaymentCount }}</span>
        </a>
        <div class="card flex flex-col gap-1 p-5">
            <span class="text-xs tracking-wide text-muted uppercase">Ventas confirmadas del mes</span>
            <x-price :amount="$monthSales" class="font-display text-3xl font-semibold" />
        </div>
        <a href="{{ route('admin.products.index') }}" class="card flex flex-col gap-1 p-5 hover:border-bronze">
            <span class="text-xs tracking-wide text-muted uppercase">Productos activos</span>
            <span class="font-display text-3xl font-semibold">{{ $activeProductsCount }}</span>
        </a>
        <div class="card flex flex-col gap-1 p-5">
            <span class="text-xs tracking-wide text-muted uppercase">Pedidos confirmados del mes</span>
            <span class="font-display text-3xl font-semibold">{{ $monthOrdersCount }}</span>
        </div>
        <div class="card flex flex-col gap-1 p-5">
            <span class="text-xs tracking-wide text-muted uppercase">Ticket promedio del mes</span>
            <x-price :amount="$monthAverageTicket" class="font-display text-3xl font-semibold" />
        </div>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-2">
        <section class="card min-w-0 p-5 lg:col-span-2">
            <h2 class="mb-4 font-display text-lg font-semibold">Ventas de los últimos 30 días</h2>
            <div class="relative h-72">
                <canvas data-chart='@json($charts['dailySales'])' aria-label="Gráfico de ventas por día" role="img"></canvas>
            </div>
        </section>
        <section class="card min-w-0 p-5">
            <h2 class="mb-4 font-display text-lg font-semibold">Ventas por mes</h2>
            <div class="relative h-72">
                <canvas data-chart='@json($charts['monthlySales'])' aria-label="Gráfico de ventas por mes" role="img"></canvas>
            </div>
        </section>
        <section class="card min-w-0 p-5">
            <h2 class="mb-4 font-display text-lg font-semibold">Pedidos por estado</h2>
            <div class="relative h-72">
                <canvas data-chart='@json($charts['ordersByStatus'])' aria-label="Gráfico de pedidos por estado" role="img"></canvas>
            </div>
        </section>
        <section class="card min-w-0 p-5 lg:col-span-2">
            <h2 class="mb-4 font-display text-lg font-semibold">Productos más vendidos</h2>
            @if ($charts['topProducts']['values'] === [])
                <p class="py-6 text-sm text-muted">Todavía no hay ventas confirmadas.</p>
            @else
                <div class="relative h-64">
                    <canvas data-chart='@json($charts['topProducts'])' aria-label="Gráfico de productos más vendidos" role="img"></canvas>
                </div>
            @endif
        </section>
    </div>

    <div class="mt-8 flex flex-wrap gap-6">
        <section class="card min-w-0 flex-[2_1_480px]">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 class="font-display text-lg font-semibold">Últimos pedidos</h2>
                <a href="{{ route('admin.orders.index') }}" class="text-sm text-bronze underline">Ver todos</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full min-w-[520px] text-left text-sm">
                    <tbody class="divide-y divide-line">
                        @forelse ($latestOrders as $order)
                            <tr>
                                <td class="px-5 py-3"><a href="{{ route('admin.orders.show', $order) }}" class="font-semibold text-bronze underline">{{ $order->number }}</a></td>
                                <td class="px-5 py-3">{{ $order->customer_name }}</td>
                                <td class="px-5 py-3"><span class="px-2 py-1 text-xs font-semibold {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span></td>
                                <td class="px-5 py-3 text-right"><x-price :amount="$order->total" /></td>
                            </tr>
                        @empty
                            <tr><td class="px-5 py-6 text-muted">Todavía no hay pedidos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="card min-w-0 flex-[1_1_300px]">
            <h2 class="border-b border-line px-5 py-4 font-display text-lg font-semibold">Poco stock</h2>
            <ul class="divide-y divide-line text-sm">
                @forelse ($lowStockVariants as $variant)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <a href="{{ route('admin.products.edit', $variant->product) }}#variante-{{ $variant->id }}" class="hover:text-bronze">
                            {{ $variant->product->name }} <span class="text-muted">· {{ $variant->label() }}</span>
                        </a>
                        @if ($variant->pending_alerts_count > 0)
                            <a href="{{ route('admin.products.edit', $variant->product) }}#interesados-{{ $variant->id }}" class="ml-auto shrink-0 bg-gold/25 px-2 py-0.5 text-xs font-semibold text-bronze-dark hover:bg-gold/40">{{ $variant->pending_alerts_count }} {{ $variant->pending_alerts_count === 1 ? 'interesado' : 'interesados' }}</a>
                        @endif
                        <span @class(['font-semibold', 'text-red-700' => $variant->stock === 0])>{{ $variant->stock }}</span>
                    </li>
                @empty
                    <li class="px-5 py-6 text-muted">Todo el stock está bien.</li>
                @endforelse
            </ul>
        </section>
    </div>
</x-layouts.admin>
