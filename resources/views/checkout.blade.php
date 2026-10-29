<x-layouts.store title="Finalizar compra">
    <div class="mx-auto max-w-7xl px-4 py-10 md:px-6">
        <h1 class="mb-8 font-display text-3xl font-medium md:text-4xl">Finalizar compra</h1>

        @if ($errors->has('cart'))
            <p role="alert" class="mb-6 border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-950">{{ $errors->first('cart') }}</p>
        @endif

        <form method="POST" action="{{ route('checkout.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-start gap-10" novalidate>
            @csrf

            <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-7">
                <fieldset class="card flex flex-col gap-5 p-6 md:p-7">
                    <legend class="px-2 font-display text-lg font-semibold">1 · Tus datos</legend>
                    @guest
                        <p class="text-sm text-muted">¿Ya tenés cuenta? <a href="{{ route('login') }}" class="text-bronze underline">Ingresá</a>. Si no, <a href="{{ route('register') }}" class="text-bronze underline">creá una cuenta</a> para guardar tus datos y seguir tus pedidos. También podés comprar sin registrarte.</p>
                    @endguest
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="field-label">Nombre y apellido
                            <input type="text" name="customer_name" value="{{ old('customer_name', $user?->name) }}" class="field-input" autocomplete="name" required>
                            @error('customer_name') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="field-label">Cédula de Identidad
                            <input type="text" name="cedula" value="{{ old('cedula', $user?->cedula) }}" class="field-input" inputmode="numeric" required>
                            @error('cedula') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="field-label">Correo electrónico
                            <input type="email" name="email" value="{{ old('email', $user?->email) }}" class="field-input" autocomplete="email" required>
                            @error('email') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="field-label">Teléfono / WhatsApp
                            <input type="tel" name="phone" value="{{ old('phone', $user?->phone) }}" class="field-input" autocomplete="tel" placeholder="09XX XXX XXX" required>
                            @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                    </div>
                </fieldset>

                <fieldset class="card flex flex-col gap-5 p-6 md:p-7">
                    <legend class="px-2 font-display text-lg font-semibold">2 · Entrega</legend>
                    <div class="grid gap-3 sm:grid-cols-2">
                        @foreach ([
                            \App\Models\Order::DELIVERY_PICKUP => ['Retiro en el local', \App\Models\Setting::get('store_address') ?: 'Sin costo'],
                            \App\Models\Order::DELIVERY_SHIPPING => ['Envío a domicilio', \App\Models\Setting::integer('shipping_cost') > 0 ? 'Costo: ₲ '.number_format(\App\Models\Setting::integer('shipping_cost'), 0, ',', '.') : 'Costo a coordinar por WhatsApp'],
                        ] as $method => [$methodTitle, $methodDetail])
                            <label class="cursor-pointer">
                                <input type="radio" name="delivery_method" value="{{ $method }}" class="peer sr-only" @checked($deliveryMethod === $method)>
                                <span class="flex flex-col gap-1 border border-line-strong p-4 peer-checked:border-2 peer-checked:border-bronze peer-checked:bg-[#fbf6ec] peer-focus-visible:outline-2 peer-focus-visible:outline-bronze">
                                    <span class="text-sm font-semibold">{{ $methodTitle }}</span>
                                    <span class="text-xs text-muted">{{ $methodDetail }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                    @error('delivery_method') <span class="field-error">{{ $message }}</span> @enderror

                    <div data-shipping-fields class="grid gap-4 sm:grid-cols-2" @if ($deliveryMethod !== \App\Models\Order::DELIVERY_SHIPPING) hidden @endif>
                        <label class="field-label sm:col-span-2">Dirección
                            <input type="text" name="address" value="{{ old('address') }}" class="field-input" autocomplete="street-address">
                            @error('address') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                        <label class="field-label">Ciudad
                            <input type="text" name="city" value="{{ old('city') }}" class="field-input" autocomplete="address-level2">
                            @error('city') <span class="field-error">{{ $message }}</span> @enderror
                        </label>
                    </div>

                    <label class="field-label">Notas para la tienda (opcional)
                        <textarea name="notes" rows="2" class="field-input py-2.5">{{ old('notes') }}</textarea>
                    </label>
                </fieldset>

                <fieldset class="card flex flex-col gap-5 p-6 md:p-7">
                    <legend class="px-2 font-display text-lg font-semibold">3 · Pago por transferencia</legend>
                    <p class="text-sm leading-relaxed text-coal-soft">
                        Al confirmar te mostramos los datos bancarios para transferir el total.
                        Reservamos tu pedido por <strong>{{ \App\Models\Setting::integer('reserve_hours') }} horas</strong>.
                    </p>
                    <label class="field-label">¿Ya transferiste? Subí el comprobante (opcional, imagen o PDF)
                        <input type="file" name="receipt" accept="image/*,application/pdf" class="border border-dashed border-[#b7a584] bg-ivory-dark p-3.5 text-sm font-normal">
                        @error('receipt') <span class="field-error">{{ $message }}</span> @enderror
                    </label>
                    <span class="text-xs text-muted">También podés subirlo después desde la página de tu pedido.</span>
                </fieldset>
            </div>

            <aside class="card flex flex-[1_1_340px] flex-col gap-5 p-6 md:p-7">
                <h2 class="font-display text-xl font-semibold">Tu pedido</h2>
                <ul class="flex flex-col gap-4">
                    @foreach ($summary['lines'] as $line)
                        <li class="flex items-center gap-3.5">
                            <div class="h-20 w-16 shrink-0 overflow-hidden bg-sand"><x-product-image :product="$line['variant']->product" /></div>
                            <div class="flex min-w-0 grow flex-col gap-0.5">
                                <span class="text-sm font-medium">{{ $line['variant']->product->name }}</span>
                                <span class="text-xs text-muted">{{ $line['variant']->label() }} · x{{ $line['quantity'] }}</span>
                            </div>
                            <x-price :amount="$line['line_total']" class="text-sm font-semibold" />
                        </li>
                    @endforeach
                </ul>

                @foreach ([\App\Models\Order::DELIVERY_PICKUP => $pickupSummary, \App\Models\Order::DELIVERY_SHIPPING => $shippingSummary] as $method => $methodSummary)
                    <div data-summary-for="{{ $method }}" @if ($deliveryMethod !== $method) hidden @endif class="flex flex-col gap-4">
                        <dl class="flex flex-col gap-2.5 border-t border-line pt-4 text-sm">
                            <div class="flex justify-between"><dt>Subtotal</dt><dd><x-price :amount="$methodSummary['subtotal']" /></dd></div>
                            @if ($methodSummary['discount'] > 0)
                                <div class="flex justify-between text-bronze"><dt>Descuento por cantidad ({{ $methodSummary['discount_percent'] }}%)</dt><dd>− <x-price :amount="$methodSummary['discount']" /></dd></div>
                            @endif
                            <div class="flex justify-between">
                                <dt>Envío</dt>
                                <dd>
                                    @if ($method === \App\Models\Order::DELIVERY_PICKUP)
                                        Gratis
                                    @elseif ($methodSummary['shipping'] > 0)
                                        <x-price :amount="$methodSummary['shipping']" />
                                    @else
                                        A coordinar
                                    @endif
                                </dd>
                            </div>
                        </dl>
                        <div class="flex items-baseline justify-between border-t border-coal pt-4">
                            <span class="text-xs font-semibold tracking-[0.14em] uppercase">Total</span>
                            <x-price :amount="$methodSummary['total']" class="font-display text-2xl font-semibold" />
                        </div>
                    </div>
                @endforeach

                <button type="submit" class="btn-dark h-13 text-[13px]">Confirmar pedido</button>
                <a href="{{ route('cart.index') }}" class="text-center text-xs text-bronze hover:underline">← Volver al carrito</a>
            </aside>
        </form>
    </div>
</x-layouts.store>
