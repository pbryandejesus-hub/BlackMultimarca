@props(['summary', 'message' => null, 'discountPercent' => 0])

<dialog data-cart-drawer aria-labelledby="cart-drawer-title" class="drawer-in m-0 ml-auto h-dvh max-h-none w-full max-w-md bg-ivory p-0 text-coal backdrop:bg-ink/55">
    <div class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b border-line bg-ink px-5 py-4 text-cream">
            <h2 id="cart-drawer-title" class="font-display text-lg font-medium text-gold-light">Tu carrito</h2>
            <form method="dialog">
                <button type="submit" class="flex h-10 w-10 items-center justify-center text-gold-light hover:text-gold" aria-label="Cerrar carrito">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M6 6l12 12M18 6L6 18"/></svg>
                </button>
            </form>
        </div>

        @if ($message)
            <p role="status" class="flex items-center gap-2 border-b border-line bg-white px-5 py-3 text-sm">
                <svg aria-hidden="true" class="h-5 w-5 shrink-0 text-bronze" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12l5 5 9-10"/></svg>
                {{ $message }}
            </p>
        @endif

        <ul class="grow divide-y divide-line overflow-y-auto px-5">
            @foreach ($summary['lines'] as $line)
                @php($variant = $line['variant'])
                <li class="flex gap-4 py-4">
                    <a href="{{ route('products.show', $variant->product) }}" class="h-24 w-20 shrink-0 overflow-hidden bg-sand">
                        <x-product-image :product="$variant->product" />
                    </a>
                    <div class="flex min-w-0 grow flex-col gap-1 text-sm">
                        <a href="{{ route('products.show', $variant->product) }}" class="font-medium hover:text-bronze">{{ $variant->product->name }}</a>
                        <span class="text-xs text-muted">{{ $variant->label() }} · {{ $line['quantity'] }} {{ $line['quantity'] === 1 ? 'unidad' : 'unidades' }}</span>
                        <x-price :amount="$line['line_total']" class="mt-auto font-semibold" />
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="flex flex-col gap-3 border-t border-line bg-white px-5 py-5">
            @if ($summary['missing_for_discount'] > 0)
                <p class="border border-gold/60 bg-ivory px-3 py-2.5 text-xs">
                    Sumá <strong>{{ $summary['missing_for_discount'] }} {{ $summary['missing_for_discount'] === 1 ? 'producto más' : 'productos más' }}</strong>
                    y tenés <strong>{{ $discountPercent }}% de descuento</strong> en toda tu compra.
                </p>
            @elseif ($summary['discount_percent'] > 0)
                <p class="bg-ink px-3 py-2.5 text-xs text-gold-light">¡Tenés {{ $summary['discount_percent'] }}% de descuento por llevar {{ $summary['items_count'] }} productos!</p>
            @endif

            <dl class="flex flex-col gap-1.5 text-sm">
                @if ($summary['discount'] > 0)
                    <div class="flex justify-between text-muted"><dt>Subtotal</dt><dd><x-price :amount="$summary['subtotal']" /></dd></div>
                    <div class="flex justify-between text-bronze"><dt>Descuento {{ $summary['discount_percent'] }}%</dt><dd>− <x-price :amount="$summary['discount']" /></dd></div>
                @endif
                <div class="flex justify-between font-display text-lg font-semibold"><dt>Total</dt><dd><x-price :amount="$summary['total']" /></dd></div>
            </dl>

            <a href="{{ route('checkout.create') }}" class="btn-dark">Finalizar compra</a>
            <div class="flex gap-2">
                <form method="dialog" class="flex grow">
                    <button type="submit" class="btn-outline grow">Seguir comprando</button>
                </form>
                <a href="{{ route('cart.index') }}" class="btn-outline grow">Ver carrito</a>
            </div>
        </div>
    </div>
</dialog>
