@php
    $colors = $product->variants->whereNotNull('color')->unique('color')->values();
    $sizes = $product->variants->whereNotNull('size')->pluck('size')->unique()->values();
    $variantsJson = $product->variants->map(fn ($variant) => [
        'id' => $variant->id,
        'size' => $variant->size,
        'color' => $variant->color,
        'stock' => $variant->stock,
    ])->values();
    $inStock = $product->variants->sum('stock') > 0;
    $discountPercent = \App\Models\Setting::integer('discount_percent');
    $discountMinItems = \App\Models\Setting::integer('discount_min_items');
    $whatsappNumber = preg_replace('/\D/', '', \App\Models\Setting::get('store_whatsapp'));
@endphp

<x-layouts.store :title="$product->name">
    <div class="mx-auto max-w-7xl px-4 py-8 md:px-6">
        <nav aria-label="Ruta" class="mb-7 flex flex-wrap gap-2 text-xs text-muted">
            <a href="{{ route('home') }}" class="hover:text-bronze">Inicio</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('catalog', ['categoria' => $product->category->slug]) }}" class="hover:text-bronze">{{ $product->category->name }}</a>
            <span aria-hidden="true">/</span>
            <span>{{ $product->name }}</span>
        </nav>

        <div class="flex flex-wrap gap-10 lg:gap-14">
            <div class="flex min-w-0 flex-[1_1_480px] flex-col-reverse gap-3 sm:flex-row">
                @if ($product->images->count() > 1)
                    <div class="flex gap-2.5 sm:flex-col">
                        @foreach ($product->images as $image)
                            <button type="button" data-gallery-thumb="{{ $image->url() }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="Ver foto {{ $loop->iteration }}" class="h-24 w-20 shrink-0 overflow-hidden border border-line aria-pressed:border-2 aria-pressed:border-bronze">
                                <img src="{{ $image->url() }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif
                <div class="aspect-[4/5] grow overflow-hidden border border-line bg-sand">
                    @if ($product->images->isNotEmpty())
                        <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" data-gallery-main class="h-full w-full object-cover">
                    @else
                        <x-product-image :product="$product" />
                    @endif
                </div>
            </div>

            <div class="flex min-w-0 flex-[1_1_380px] flex-col gap-6">
                <div class="flex flex-col gap-2.5">
                    <span class="eyebrow">{{ $product->brand?->name ?? 'Black Multimarca' }} · {{ $product->category->name }}</span>
                    <h1 class="font-display text-3xl leading-tight font-medium md:text-4xl">{{ $product->name }}</h1>
                    @if ($product->sku)
                        <span class="text-xs text-muted">Código {{ $product->sku }}</span>
                    @endif
                </div>

                <div class="flex flex-col gap-1.5 border-y border-line py-5">
                    <div class="flex items-baseline gap-3">
                        <x-price :amount="$product->price" class="font-display text-3xl font-semibold" />
                        @if ($product->hasComparePrice())
                            <x-price :amount="$product->compare_price" class="text-muted line-through" />
                        @endif
                    </div>
                    @if ($discountPercent > 0 && $discountMinItems > 0)
                        <span class="text-sm font-medium text-bronze">Llevando {{ $discountMinItems }} o más: {{ $discountPercent }}% de descuento</span>
                    @endif
                </div>

                @if (! $inStock)
                    <p class="border border-line bg-white px-4 py-3 text-sm">Este producto está agotado por el momento. Consultanos por WhatsApp cuándo vuelve a ingresar.</p>
                @else
                    <form method="POST" action="{{ route('cart.store') }}" class="flex flex-col gap-6">
                        @csrf
                        <div data-variant-picker data-variants='@json($variantsJson)' class="flex flex-col gap-6">
                            <input type="hidden" name="variant_id" data-variant-input value="{{ $product->variants->count() === 1 ? $product->variants->first()->id : '' }}">

                            @if ($product->variants->count() > 1)
                                <label class="field-label" data-variant-fallback>Elegí una opción
                                    <select name="variant_id" class="field-input">
                                        @foreach ($product->variants as $variant)
                                            <option value="{{ $variant->id }}" @disabled($variant->stock < 1)>{{ $variant->label() }} {{ $variant->stock < 1 ? '(sin stock)' : '' }}</option>
                                        @endforeach
                                    </select>
                                </label>
                            @endif

                            @if ($colors->isNotEmpty())
                                <fieldset data-variant-buttons hidden class="flex flex-col gap-3">
                                    <legend class="mb-3 text-xs font-semibold tracking-[0.14em] uppercase">Color: <span data-color-name class="font-normal tracking-normal text-muted normal-case"></span></legend>
                                    <div class="flex flex-wrap gap-3">
                                        @foreach ($colors as $colorVariant)
                                            <button type="button" data-color="{{ $colorVariant->color }}" aria-pressed="false" aria-label="{{ $colorVariant->color }}" title="{{ $colorVariant->color }}" class="h-11 w-11 rounded-full border border-line-strong p-1 ring-bronze ring-offset-2">
                                                <span class="block h-full w-full rounded-full border border-black/10" style="background-color: {{ $colorVariant->color_hex ?: '#d6cbb8' }}"></span>
                                            </button>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif

                            @if ($sizes->isNotEmpty())
                                <fieldset data-variant-buttons hidden class="flex flex-col gap-3">
                                    <legend class="mb-3 text-xs font-semibold tracking-[0.14em] uppercase">Talle</legend>
                                    <div class="flex flex-wrap gap-2.5">
                                        @foreach ($sizes as $size)
                                            <button type="button" data-size="{{ $size }}" aria-pressed="false" class="h-11 min-w-14 border border-line-strong bg-white px-3 text-sm font-semibold disabled:cursor-not-allowed disabled:text-[#9c9384]">{{ $size }}</button>
                                        @endforeach
                                    </div>
                                </fieldset>
                            @endif

                            <p data-stock-message class="text-xs text-muted" aria-live="polite"></p>
                        </div>

                        @error('variant_id')
                            <p class="field-error">{{ $message }}</p>
                        @enderror

                        <div class="flex flex-wrap gap-3">
                            <div data-quantity class="flex h-13 items-center border border-line-strong bg-white">
                                <button type="button" data-quantity-dec class="h-full w-11 text-lg" aria-label="Restar uno">−</button>
                                <label for="quantity" class="sr-only">Cantidad</label>
                                <input id="quantity" type="number" name="quantity" value="1" min="1" max="99" class="h-full w-12 border-0 bg-transparent text-center font-semibold [appearance:textfield] focus:outline-none [&::-webkit-inner-spin-button]:appearance-none">
                                <button type="button" data-quantity-inc class="h-full w-11 text-lg" aria-label="Sumar uno">+</button>
                            </div>
                            <button type="submit" data-add-to-cart class="btn-dark h-13 flex-[1_1_220px] text-[13px]">Agregar al carrito</button>
                        </div>
                    </form>
                @endif

                @if ($whatsappNumber)
                    <a href="https://wa.me/{{ $whatsappNumber }}?text={{ urlencode('Hola! Quería consultar por: '.$product->name.' '.route('products.show', $product)) }}" target="_blank" rel="noopener" class="btn border border-line-strong text-coal normal-case tracking-normal hover:border-bronze">
                        <svg aria-hidden="true" class="h-4 w-4 text-bronze" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/></svg>
                        Consultar por WhatsApp
                    </a>
                @endif

                <div class="flex flex-col divide-y divide-line border-y border-line">
                    <details class="group py-4" open>
                        <summary class="flex cursor-pointer list-none items-center justify-between text-xs font-semibold tracking-[0.12em] uppercase">Descripción <span class="text-bronze group-open:rotate-45" aria-hidden="true">+</span></summary>
                        <div class="mt-3 text-sm leading-relaxed whitespace-pre-line text-coal-soft">{{ $product->description ?: 'Consultanos por más detalles de este producto.' }}</div>
                    </details>
                    <details class="group py-4">
                        <summary class="flex cursor-pointer list-none items-center justify-between text-xs font-semibold tracking-[0.12em] uppercase">Envíos y cambios <span class="text-bronze group-open:rotate-45" aria-hidden="true">+</span></summary>
                        <p class="mt-3 text-sm leading-relaxed text-coal-soft">Retiro sin costo en el local o envío a domicilio. Podés cambiar tu compra con la etiqueta puesta. <a href="{{ route('pages.show', 'cambios') }}" class="text-bronze underline">Ver condiciones</a>.</p>
                    </details>
                </div>
            </div>
        </div>

        @if ($relatedProducts->isNotEmpty())
            <section class="mt-20">
                <x-section-heading title="También te puede gustar" class="mb-10" />
                <div class="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
                    @foreach ($relatedProducts as $related)
                        <x-product-card :product="$related" />
                    @endforeach
                </div>
            </section>
        @endif
    </div>
</x-layouts.store>
