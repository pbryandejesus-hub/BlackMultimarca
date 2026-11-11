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
            <a href="{{ route('categories.show', $product->category) }}" class="hover:text-bronze">{{ $product->category->name }}</a>
            <span aria-hidden="true">/</span>
            <span>{{ $product->name }}</span>
        </nav>

        <div class="flex flex-wrap gap-10 lg:gap-14">
            <div class="flex min-w-0 flex-[1_1_480px] flex-col-reverse gap-3 sm:flex-row">
                @if ($product->images->count() > 1 || $product->hasModel())
                    <div class="flex gap-2.5 overflow-x-auto sm:flex-col">
                        @foreach ($product->images as $image)
                            <button type="button" data-gallery-thumb="{{ $image->url() }}" aria-pressed="{{ $loop->first ? 'true' : 'false' }}" aria-label="Ver foto {{ $loop->iteration }}" class="h-24 w-20 shrink-0 overflow-hidden border border-line aria-pressed:border-2 aria-pressed:border-bronze">
                                <img src="{{ $image->url() }}" alt="" class="h-full w-full object-cover">
                            </button>
                        @endforeach
                        @if ($product->hasModel())
                            <button type="button" data-model-open aria-label="Ver en 3D" class="flex h-24 w-20 shrink-0 flex-col items-center justify-center gap-1.5 border border-ink bg-ink text-gold-light hover:bg-coal-soft">
                                <x-icon-cube class="h-7 w-7" />
                                <span class="text-[10px] font-semibold tracking-[0.2em]">3D</span>
                            </button>
                        @endif
                    </div>
                @endif
                <div class="relative aspect-[4/5] grow overflow-hidden border border-line bg-sand">
                    @if ($product->images->isNotEmpty())
                        <button type="button" data-gallery-open aria-label="Ver foto en pantalla completa" class="block h-full w-full cursor-zoom-in">
                            <img src="{{ $product->images->first()->url() }}" alt="{{ $product->name }}" data-gallery-main data-gallery-images='@json($product->images->map->url()->values())' class="h-full w-full object-cover transition-transform duration-200 ease-out">
                        </button>
                    @else
                        <x-product-image :product="$product" />
                    @endif
                    @if ($product->hasModel())
                        <button type="button" data-model-open class="btn-dark absolute bottom-4 left-1/2 -translate-x-1/2 shadow-lg">
                            <x-icon-cube class="h-4 w-4" />
                            Ver en 3D
                        </button>
                    @endif
                </div>
            </div>

            <div class="flex min-w-0 flex-[1_1_380px] flex-col gap-6">
                <div class="flex flex-col gap-2.5">
                    <span class="eyebrow">{{ $product->brand?->name ?? 'Black Multimarca' }} · {{ $product->category->name }}</span>
                    <div class="flex items-start justify-between gap-4">
                        <h1 class="font-display text-3xl leading-tight font-medium md:text-4xl">{{ $product->name }}</h1>
                        <x-favorite-button :product="$product" class="shrink-0" />
                    </div>
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

                @if ($product->category->sizeGuideRows())
                    <button type="button" data-dialog-open="size-guide" class="flex items-center gap-2 self-start text-sm text-bronze underline-offset-4 hover:underline">
                        <svg aria-hidden="true" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M3 8h18v8H3zM7 8v3M11 8v4M15 8v3M19 8v4"/></svg>
                        Guía de talles
                    </button>
                @endif

                @if (! $inStock)
                    <p class="border border-line bg-white px-4 py-3 text-sm">Este producto está agotado por el momento.</p>
                    <x-stock-alert-form :variants="$product->variants" />
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

                    @if ($product->variants->contains(fn ($variant) => $variant->stock < 1))
                        <x-stock-alert-form :variants="$product->variants" hidden />
                    @endif
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

    @if ($product->images->isNotEmpty())
        <dialog data-lightbox aria-label="Fotos de {{ $product->name }}" class="m-0 h-dvh max-h-none w-screen max-w-none bg-ink/95 p-0 text-ivory">
            <div class="relative flex h-full flex-col">
                <div class="flex items-center justify-between px-5 py-3">
                    <span data-lightbox-counter class="text-xs tracking-[0.2em] text-cream-muted" aria-live="polite"></span>
                    <button type="button" data-lightbox-close class="h-11 w-11 text-3xl leading-none hover:text-gold" aria-label="Cerrar">×</button>
                </div>
                <div class="flex min-h-0 grow items-center justify-center px-4 pb-6 select-none">
                    <img data-lightbox-image src="" alt="{{ $product->name }}" class="max-h-full max-w-full touch-pan-y object-contain">
                </div>
                @if ($product->images->count() > 1)
                    <button type="button" data-lightbox-prev class="absolute top-1/2 left-2 h-14 w-12 -translate-y-1/2 text-4xl text-gold-light hover:text-gold md:left-6" aria-label="Foto anterior">‹</button>
                    <button type="button" data-lightbox-next class="absolute top-1/2 right-2 h-14 w-12 -translate-y-1/2 text-4xl text-gold-light hover:text-gold md:right-6" aria-label="Foto siguiente">›</button>
                @endif
            </div>
        </dialog>
    @endif

    @if ($product->hasModel())
        <dialog data-model-dialog aria-label="Vista 3D de {{ $product->name }}" class="m-0 h-dvh max-h-none w-screen max-w-none bg-ivory p-0 text-coal">
            <div class="flex h-full flex-col">
                <header class="flex items-center justify-between gap-4 border-b border-line px-5 py-3">
                    <div class="flex min-w-0 flex-col">
                        <span class="eyebrow">Vista 3D</span>
                        <span class="truncate font-display text-lg font-medium">{{ $product->name }}</span>
                    </div>
                    <button type="button" data-model-close class="h-11 w-11 shrink-0 text-3xl leading-none hover:text-bronze" aria-label="Cerrar vista 3D">×</button>
                </header>
                <div class="relative grow bg-[radial-gradient(circle_at_50%_40%,#ffffff_0%,var(--color-sand)_75%)]">
                    <model-viewer
                        src="{{ $product->modelUrl() }}"
                        alt="Modelo 3D de {{ $product->name }}"
                        @if ($product->images->isNotEmpty()) poster="{{ $product->images->first()->url() }}" @endif
                        camera-controls
                        touch-action="pan-y"
                        auto-rotate
                        auto-rotate-delay="1500"
                        interaction-prompt="auto"
                        shadow-intensity="1"
                        environment-image="neutral"
                        ar
                        ar-modes="webxr scene-viewer quick-look"
                        class="h-full w-full"
                        style="--poster-color: transparent;"
                    >
                        <div slot="progress-bar"></div>
                        <button slot="ar-button" type="button" class="btn-dark absolute right-5 bottom-5 shadow-lg">Ver en tu espacio (AR)</button>
                    </model-viewer>
                    <div data-model-progress class="pointer-events-none absolute inset-x-0 top-0 h-1 bg-line">
                        <div class="h-full w-0 bg-gold transition-[width] duration-200"></div>
                    </div>
                    <p data-model-error hidden class="absolute inset-x-0 top-1/2 px-6 text-center text-sm text-muted">No se pudo cargar la vista 3D. Revisá tu conexión e intentá de nuevo.</p>
                </div>
                <p class="border-t border-line px-5 py-3 text-center text-xs text-muted">Arrastrá para girar · Rueda o pellizcá para hacer zoom · En el celular podés verlo en tu espacio con realidad aumentada</p>
            </div>
        </dialog>
    @endif

    @if ($sizeGuideRows = $product->category->sizeGuideRows())
        <dialog id="size-guide" aria-labelledby="size-guide-title" class="m-auto w-[calc(100%-2rem)] max-w-xl bg-ivory p-0 text-coal backdrop:bg-ink/55">
            <div class="flex items-center justify-between border-b border-line px-5 py-4">
                <h2 id="size-guide-title" class="font-display text-lg font-semibold">Guía de talles · {{ $product->category->name }}</h2>
                <button type="button" data-dialog-close class="h-10 w-10 text-3xl leading-none hover:text-bronze" aria-label="Cerrar guía de talles">×</button>
            </div>
            <div class="overflow-x-auto p-5">
                <table class="w-full text-left text-sm">
                    <thead class="border-b border-line-strong text-xs tracking-wide uppercase">
                        <tr>
                            @foreach ($sizeGuideRows[0] as $heading)
                                <th class="px-3 py-2 font-semibold">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line">
                        @foreach (array_slice($sizeGuideRows, 1) as $row)
                            <tr>
                                @foreach ($row as $cell)
                                    <td @class(['px-3 py-2', 'font-semibold' => $loop->first])>{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <p class="mt-4 text-xs text-muted">Medidas en centímetros. Si dudás entre dos talles, escribinos por WhatsApp y te asesoramos.</p>
            </div>
        </dialog>
    @endif
</x-layouts.store>
