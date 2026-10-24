<x-layouts.store :title="'Pedido '.$order->number">
    <div class="mx-auto max-w-5xl px-4 py-10 md:px-6">
        @if (session('order_placed'))
            <div class="mb-10 flex flex-col items-center gap-4 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full border border-bronze text-bronze">
                    <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>
                </div>
                <h1 class="font-display text-3xl font-medium md:text-4xl">¡Pedido recibido!</h1>
                <p class="max-w-xl leading-relaxed text-coal-soft">Tu pedido <strong>{{ $order->number }}</strong> quedó registrado. Transferí el total y subí el comprobante para que lo preparemos.</p>
            </div>
        @else
            <div class="mb-8 flex flex-wrap items-center justify-between gap-4">
                <h1 class="font-display text-3xl font-medium">Pedido {{ $order->number }}</h1>
                <span class="px-3 py-1.5 text-xs font-semibold {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
            </div>
        @endif

        <div class="flex flex-wrap items-start gap-8">
            <div class="flex min-w-0 flex-[999_1_480px] flex-col gap-7">
                @if ($order->status->acceptsReceipt())
                    <section class="card flex flex-col gap-5 p-6 md:p-7">
                        <h2 class="font-display text-lg font-semibold">Datos para la transferencia</h2>
                        <p class="text-sm text-coal-soft">Transferí exactamente <x-price :amount="$order->total" class="font-semibold" /> e indicá el número de pedido <strong>{{ $order->number }}</strong> en el concepto.</p>

                        <dl class="flex flex-col gap-3 bg-ink px-6 py-5 text-cream">
                            @foreach ([
                                'Banco' => $settings['bank_name'],
                                'Titular' => $settings['bank_holder'],
                                'C.I. / RUC del titular' => $settings['bank_holder_document'],
                                'Número de cuenta' => $settings['bank_account'],
                                'Alias' => $settings['bank_alias'],
                                'Monto' => '₲ '.number_format($order->total, 0, ',', '.'),
                            ] as $bankLabel => $bankValue)
                                @if ($bankValue)
                                    <div class="flex flex-wrap items-center justify-between gap-3 border-b border-[#2a2418] pb-3 last:border-0 last:pb-0">
                                        <div class="flex flex-col gap-0.5">
                                            <dt class="text-[11px] tracking-[0.2em] text-gold uppercase">{{ $bankLabel }}</dt>
                                            <dd class="text-[15px] font-medium">{{ $bankValue }}</dd>
                                        </div>
                                        @if (in_array($bankLabel, ['Número de cuenta', 'Alias', 'C.I. / RUC del titular'], true))
                                            <button type="button" data-copy="{{ $bankValue }}" class="h-9 border border-gold px-3.5 text-[11px] font-semibold tracking-[0.14em] text-gold-light uppercase hover:bg-gold hover:text-ink">Copiar</button>
                                        @endif
                                    </div>
                                @endif
                            @endforeach
                            @if (! $settings['bank_account'] && ! $settings['bank_alias'])
                                <p class="text-sm text-cream-muted">Te enviamos los datos bancarios por WhatsApp o correo.</p>
                            @endif
                        </dl>

                        <form method="POST" action="{{ route('orders.receipt.store', $order) }}" enctype="multipart/form-data" class="flex flex-col gap-3">
                            @csrf
                            <label class="field-label">{{ $order->receipt_path ? 'Reemplazar comprobante' : 'Subir comprobante' }} (imagen o PDF)
                                <input type="file" name="receipt" accept="image/*,application/pdf" required class="border border-dashed border-[#b7a584] bg-ivory-dark p-3.5 text-sm font-normal">
                            </label>
                            @error('receipt') <span class="field-error">{{ $message }}</span> @enderror
                            @if ($order->receipt_uploaded_at)
                                <p class="text-xs text-muted">Comprobante recibido el {{ $order->receipt_uploaded_at->format('d/m/Y H:i') }}. Lo estamos revisando.</p>
                            @endif
                            <button type="submit" class="btn-dark self-start">Enviar comprobante</button>
                        </form>
                    </section>
                @endif

                <section class="card p-6 md:p-7">
                    <h2 class="mb-4 font-display text-lg font-semibold">Productos</h2>
                    <ul class="divide-y divide-line">
                        @foreach ($order->items as $item)
                            <li class="flex items-center gap-4 py-3">
                                <div class="h-16 w-14 shrink-0 overflow-hidden bg-sand">
                                    @if ($item->product)
                                        <x-product-image :product="$item->product" />
                                    @endif
                                </div>
                                <div class="flex grow flex-col">
                                    <span class="text-sm font-medium">{{ $item->product_name }}</span>
                                    <span class="text-xs text-muted">{{ $item->variant_label }} · x{{ $item->quantity }}</span>
                                </div>
                                <x-price :amount="$item->line_total" class="text-sm font-semibold" />
                            </li>
                        @endforeach
                    </ul>
                </section>
            </div>

            <aside class="card flex flex-[1_1_300px] flex-col gap-4 p-6">
                <div class="flex items-center justify-between">
                    <h2 class="font-display text-lg font-semibold">Resumen</h2>
                    <span class="px-2.5 py-1 text-[11px] font-semibold {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
                </div>
                <dl class="flex flex-col gap-2 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd><x-price :amount="$order->subtotal" /></dd></div>
                    @if ($order->discount > 0)
                        <div class="flex justify-between text-bronze"><dt>Descuento por cantidad ({{ $order->discount_percent }}%)</dt><dd>− <x-price :amount="$order->discount" /></dd></div>
                    @endif
                    <div class="flex justify-between"><dt>Envío</dt><dd>@if ($order->shipping > 0)<x-price :amount="$order->shipping" />@elseif ($order->isShipping()) A coordinar @else Gratis @endif</dd></div>
                </dl>
                <div class="flex items-baseline justify-between border-t border-coal pt-3">
                    <span class="text-xs font-semibold tracking-[0.14em] uppercase">Total</span>
                    <x-price :amount="$order->total" class="font-display text-2xl font-semibold" />
                </div>
                <dl class="flex flex-col gap-1 border-t border-line pt-4 text-sm text-coal-soft">
                    <dt class="text-xs font-semibold tracking-wide text-coal uppercase">{{ $order->deliveryLabel() }}</dt>
                    @if ($order->isShipping())
                        <dd>{{ $order->address }}, {{ $order->city }}</dd>
                    @elseif ($settings['store_address'])
                        <dd>{{ $settings['store_address'] }}</dd>
                    @endif
                    <dd class="mt-2">{{ $order->customer_name }} · C.I. {{ $order->cedula }}</dd>
                    <dd>{{ $order->phone }} · {{ $order->email }}</dd>
                </dl>
                <a href="{{ route('catalog') }}" class="btn-outline mt-2">Seguir comprando</a>
            </aside>
        </div>
    </div>
</x-layouts.store>
