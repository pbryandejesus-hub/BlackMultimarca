<x-layouts.store>
    <section class="relative isolate overflow-hidden bg-ink text-cream">
        <img src="{{ asset('images/hero.jpg') }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-45">
        <div class="absolute inset-0 -z-10 bg-ink/40" aria-hidden="true"></div>
        <div class="mx-auto flex max-w-7xl flex-wrap items-center gap-12 px-4 py-20 md:px-6 md:py-28">
            <div class="flex flex-[1_1_420px] flex-col gap-6">
                <span class="text-xs tracking-[0.3em] text-gold uppercase">Nueva temporada</span>
                <h1 class="font-display text-4xl leading-tight font-medium md:text-6xl">Todo lo que buscás,<br>en un solo lugar</h1>
                <p class="max-w-md text-lg leading-relaxed text-cream">Ropa, deportiva, lencería, medias y blanquería para tu casa. Las mejores marcas reunidas en Black Multimarca.</p>
                <div class="mt-2 flex flex-wrap gap-3">
                    <a href="#novedades" class="btn-gold">Ver novedades</a>
                    <a href="#categorias" class="btn-outline-gold bg-ink/40">Explorar categorías</a>
                </div>
            </div>
            <div class="hidden flex-[0_1_300px] justify-center lg:flex">
                <img src="{{ asset('images/logo.jpg') }}" alt="Logo de Black Multimarca" class="aspect-square w-full max-w-xs border border-gold/40 object-cover shadow-2xl">
            </div>
        </div>
    </section>

    <section class="border-b border-line">
        <div class="mx-auto grid max-w-7xl gap-6 px-4 py-7 sm:grid-cols-2 md:px-6 lg:grid-cols-4">
            @foreach ([
                ['Transferencia bancaria', 'Pagá seguro desde tu banco', 'M3 10l9-6 9 6M5 10v8M9.5 10v8M14.5 10v8M19 10v8M3 20h18'],
                ['Envíos o retiro', 'A domicilio o en el local', 'M2 7h11v9H2zM13 10h4l3 3v3h-7M5 19h2M16 19h2'],
                ['Descuento por cantidad', 'Cuantos más llevás, menos pagás', 'M7 7h.01M3 3h8l10 10-8 8L3 11z'],
                ['Atención por WhatsApp', 'Te asesoramos en tu compra', 'M4 20l1.3-4A8 8 0 1 1 8 18.7z'],
            ] as [$benefitTitle, $benefitText, $benefitIcon])
                <div class="flex items-center gap-4">
                    <svg aria-hidden="true" class="h-8 w-8 shrink-0 text-bronze" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4"><path d="{{ $benefitIcon }}"/></svg>
                    <div>
                        <p class="text-[13px] font-semibold tracking-wider uppercase">{{ $benefitTitle }}</p>
                        <p class="text-[13px] text-muted">{{ $benefitText }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($categories->isNotEmpty())
        <section id="categorias" class="mx-auto max-w-7xl px-4 pt-16 md:px-6">
            <x-section-heading eyebrow="Comprá por" title="Categorías" class="mb-10" />
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($categories as $category)
                    <a href="{{ route('catalog', ['categoria' => $category->slug]) }}" class="group flex flex-col gap-3">
                        <div class="aspect-[3/4] overflow-hidden border border-line bg-sand">
                            @if ($category->imageUrl())
                                <img src="{{ $category->imageUrl() }}" alt="" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                            @else
                                <div class="flex h-full items-center justify-center font-display text-2xl text-bronze/60">{{ mb_substr($category->name, 0, 1) }}</div>
                            @endif
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="font-display text-[17px] font-semibold group-hover:text-bronze">{{ $category->name }}</span>
                            @if ($category->subtitle)
                                <span class="text-xs text-muted">{{ $category->subtitle }}</span>
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    @endif

    <section class="mx-auto grid max-w-7xl gap-4 px-4 pt-16 md:grid-cols-2 md:px-6">
        @foreach ([
            ['images/banner-ropa.jpg', 'Ropa', 'Básicos que combinan con todo', 'Remeras, jeans, camisas y más para todos los días.', 'ropa'],
            ['images/banner-hogar.jpg', 'Blanquería y hogar', 'Tu casa también se viste', 'Sábanas, almohadas, manteles y toallas de calidad.', 'blanqueria'],
        ] as [$bannerImage, $bannerEyebrow, $bannerTitle, $bannerText, $bannerCategory])
            <a href="{{ route('catalog', ['categoria' => $bannerCategory]) }}" class="group relative isolate flex min-h-80 flex-col justify-end overflow-hidden bg-ink p-7 text-cream md:min-h-96 md:p-9">
                <img src="{{ asset($bannerImage) }}" alt="" loading="lazy" class="absolute inset-0 -z-10 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                <div class="absolute inset-x-0 bottom-0 -z-10 h-2/3 bg-ink/70 [mask-image:linear-gradient(to_top,black_40%,transparent)]" aria-hidden="true"></div>
                <span class="text-[11px] tracking-[0.3em] text-gold uppercase">{{ $bannerEyebrow }}</span>
                <span class="mt-2 font-display text-2xl font-medium md:text-3xl">{{ $bannerTitle }}</span>
                <span class="mt-2 max-w-sm text-sm text-cream">{{ $bannerText }}</span>
                <span class="mt-5 self-start border-b border-gold pb-1 text-xs font-semibold tracking-[0.16em] text-gold-light uppercase">Ver productos</span>
            </a>
        @endforeach
    </section>

    @if ($featuredProducts->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 pt-16 md:px-6">
            <x-section-heading eyebrow="Selección" title="Destacados" class="mb-10" />
            <div class="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
                @foreach ($featuredProducts as $product)
                    <x-product-card :product="$product" badge="Destacado" />
                @endforeach
            </div>
        </section>
    @endif

    <section id="novedades" class="mx-auto max-w-7xl px-4 py-16 md:px-6">
        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <x-section-heading eyebrow="Recién llegados" title="Novedades" :center="false" />
            <a href="{{ route('catalog') }}" class="border-b border-bronze pb-1 text-xs font-semibold tracking-[0.16em] text-bronze uppercase">Ver todo</a>
        </div>
        @if ($newProducts->isEmpty())
            <p class="text-muted">Pronto vas a encontrar acá los nuevos ingresos.</p>
        @else
            <div class="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
                @foreach ($newProducts as $product)
                    <x-product-card :product="$product" badge="Nuevo" />
                @endforeach
            </div>
        @endif
    </section>

    @php
        $discountPercent = \App\Models\Setting::integer('discount_percent');
        $discountMinItems = \App\Models\Setting::integer('discount_min_items');
    @endphp
    @if ($discountPercent > 0 && $discountMinItems > 0)
        <section class="bg-ink text-cream">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-7 px-4 py-14 md:px-6">
                <div class="flex max-w-2xl flex-col gap-3">
                    <span class="text-[11px] tracking-[0.3em] text-gold uppercase">Beneficio exclusivo</span>
                    <h2 class="font-display text-3xl font-medium text-gold-light md:text-4xl">Llevando {{ $discountMinItems }} productos tenés {{ $discountPercent }}% de descuento</h2>
                    <p class="leading-relaxed text-cream-muted">El descuento se aplica solo en el carrito al sumar la cantidad. Combiná productos de todas las categorías.</p>
                </div>
                <a href="{{ route('catalog') }}" class="btn-outline-gold">Ver productos</a>
            </div>
        </section>
    @endif

    <section class="border-b border-line bg-white">
        <div class="mx-auto max-w-7xl px-4 py-16 md:px-6">
            <x-section-heading eyebrow="Simple y seguro" title="Cómo comprar" class="mb-12" />
            <ol class="grid gap-10 md:grid-cols-3">
                @foreach ([
                    ['Elegí tus productos', 'Agregá al carrito lo que te guste. Si llevás varios, el descuento se aplica solo.'],
                    ['Transferí el total', 'Confirmá el pedido y te mostramos los datos bancarios para pagar desde tu banco.'],
                    ['Recibí o retirá', 'Subí el comprobante y preparamos tu pedido para envío o retiro en el local.'],
                ] as $stepIndex => [$stepTitle, $stepText])
                    <li class="flex flex-col items-center gap-3 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-full border border-gold font-display text-xl text-bronze">{{ $stepIndex + 1 }}</span>
                        <span class="font-display text-lg font-semibold">{{ $stepTitle }}</span>
                        <span class="max-w-xs text-sm leading-relaxed text-muted">{{ $stepText }}</span>
                    </li>
                @endforeach
            </ol>
            <div class="mt-10 text-center">
                <a href="{{ route('pages.show', 'como-comprar') }}" class="btn-outline">Ver más detalles</a>
            </div>
        </div>
    </section>

    @if ($brands->isNotEmpty())
        <section class="mx-auto max-w-7xl px-4 py-16 md:px-6">
            <x-section-heading eyebrow="Multimarca" title="Nuestras marcas" class="mb-10" />
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                @foreach ($brands as $brand)
                    <a href="{{ route('catalog', ['marca' => $brand->slug]) }}" class="flex h-24 items-center justify-center border border-line bg-white px-4 font-display text-sm tracking-[0.14em] text-muted hover:border-bronze hover:text-bronze">
                        @if ($brand->logoUrl())
                            <img src="{{ $brand->logoUrl() }}" alt="{{ $brand->name }}" class="max-h-14 object-contain" loading="lazy">
                        @else
                            {{ $brand->name }}
                        @endif
                    </a>
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.store>
