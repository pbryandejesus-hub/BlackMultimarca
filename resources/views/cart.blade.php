<x-layouts.store title="Carrito">
    <div class="mx-auto max-w-7xl px-4 py-10 md:px-6">
        <h1 class="mb-8 font-display text-3xl font-medium md:text-4xl">Tu carrito</h1>

        @if ($summary['lines']->isEmpty())
            <div class="card flex flex-col items-center gap-4 px-6 py-16 text-center">
                <p class="font-display text-xl">Tu carrito está vacío</p>
                <p class="text-sm text-muted">Recorré la tienda y agregá lo que te guste.</p>
                <a href="{{ route('catalog') }}" class="btn-dark">Ir a la tienda</a>
            </div>
        @else
            <div class="flex flex-wrap items-start gap-10">
                <div class="min-w-0 flex-[999_1_560px]">
                    @if ($summary['missing_for_discount'] > 0)
                        <p class="mb-4 border border-gold/60 bg-white px-4 py-3 text-sm">
                            Sumá <strong>{{ $summary['missing_for_discount'] }} {{ $summary['missing_for_discount'] === 1 ? 'producto más' : 'productos más' }}</strong>
                            y obtené <strong>{{ \App\Models\Setting::integer('discount_percent') }}% de descuento</strong> en toda tu compra.
                        </p>
                    @elseif ($summary['discount_percent'] > 0)
                        <p class="mb-4 border border-gold/60 bg-ink px-4 py-3 text-sm text-gold-light">¡Tenés {{ $summary['discount_percent'] }}% de descuento por llevar {{ $summary['items_count'] }} productos!</p>
                    @endif

                    <ul class="card divide-y divide-line">
                        @foreach ($summary['lines'] as $line)
                            @php($variant = $line['variant'])
                            <li class="flex flex-wrap items-center gap-4 p-4">
                                <a href="{{ route('products.show', $variant->product) }}" class="h-24 w-20 shrink-0 overflow-hidden bg-sand">
                                    <x-product-image :product="$variant->product" />
                                </a>
                                <div class="flex min-w-40 grow flex-col gap-1">
                                    <a href="{{ route('products.show', $variant->product) }}" class="font-medium hover:text-bronze">{{ $variant->product->name }}</a>
                                    <span class="text-xs text-muted">{{ $variant->label() }}</span>
                                    <x-price :amount="$line['unit_price']" class="text-sm text-muted" />
                                </div>
                                <form method="POST" action="{{ route('cart.update', $variant) }}" class="flex items-center" data-quantity>
                                    @csrf
                                    @method('PATCH')
                                    <button type="button" data-quantity-dec class="h-10 w-10 border border-line-strong bg-white" aria-label="Restar uno">−</button>
                                    <label for="qty-{{ $variant->id }}" class="sr-only">Cantidad de {{ $variant->product->name }}</label>
                                    <input id="qty-{{ $variant->id }}" type="number" name="quantity" value="{{ $line['quantity'] }}" min="1" max="{{ $variant->stock }}" data-autosubmit class="h-10 w-12 border-y border-line-strong bg-white text-center text-sm font-semibold [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none">
                                    <button type="button" data-quantity-inc class="h-10 w-10 border border-line-strong bg-white" aria-label="Sumar uno">+</button>
                                    <noscript><button type="submit" class="ml-2 text-xs underline">Actualizar</button></noscript>
                                </form>
                                <x-price :amount="$line['line_total']" class="w-28 text-right font-semibold" />
                                <form method="POST" action="{{ route('cart.destroy', $variant) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="flex h-10 w-10 items-center justify-center text-muted hover:text-red-700" aria-label="Quitar {{ $variant->product->name }}">
                                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 7h14M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/></svg>
                                    </button>
                                </form>
                            </li>
                        @endforeach
                    </ul>
                    <a href="{{ route('catalog') }}" class="mt-5 inline-block text-sm text-bronze hover:underline">← Seguir comprando</a>
                </div>

                <aside class="card flex flex-[1_1_320px] flex-col gap-4 p-6">
                    <h2 class="font-display text-xl font-semibold">Resumen</h2>
                    <dl class="flex flex-col gap-2.5 text-sm">
                        <div class="flex justify-between"><dt>Subtotal ({{ $summary['items_count'] }} {{ $summary['items_count'] === 1 ? 'producto' : 'productos' }})</dt><dd><x-price :amount="$summary['subtotal']" /></dd></div>
                        @if ($summary['discount'] > 0)
                            <div class="flex justify-between text-bronze"><dt>Descuento por cantidad ({{ $summary['discount_percent'] }}%)</dt><dd>− <x-price :amount="$summary['discount']" /></dd></div>
                        @endif
                        <div class="flex justify-between text-muted"><dt>Envío</dt><dd>Se elige al finalizar</dd></div>
                    </dl>
                    <div class="flex items-baseline justify-between border-t border-coal pt-4">
                        <span class="text-xs font-semibold tracking-[0.14em] uppercase">Total</span>
                        <x-price :amount="$summary['total']" class="font-display text-2xl font-semibold" />
                    </div>
                    <a href="{{ route('checkout.create') }}" class="btn-dark h-13 text-[13px]">Finalizar compra</a>
                    <p class="text-center text-xs text-muted">Pagás por transferencia bancaria.</p>
                </aside>
            </div>
        @endif
    </div>
</x-layouts.store>
