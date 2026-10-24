<x-layouts.store :title="$currentCategory?->name ?? 'Tienda'">
    <div class="mx-auto max-w-7xl px-4 py-10 md:px-6">
        <nav aria-label="Ruta" class="mb-6 flex flex-wrap gap-2 text-xs text-muted">
            <a href="{{ route('home') }}" class="hover:text-bronze">Inicio</a>
            <span aria-hidden="true">/</span>
            @if ($currentCategory)
                <a href="{{ route('catalog') }}" class="hover:text-bronze">Tienda</a>
                <span aria-hidden="true">/</span>
                <span>{{ $currentCategory->name }}</span>
            @else
                <span>Tienda</span>
            @endif
        </nav>

        @if ($currentCategory?->imageUrl())
            <div class="relative isolate mb-8 flex min-h-48 flex-col justify-end overflow-hidden bg-ink p-7 text-cream md:min-h-56 md:p-9">
                <img src="{{ $currentCategory->imageUrl() }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover opacity-55">
                <div class="absolute inset-0 -z-10 bg-ink/35" aria-hidden="true"></div>
                <h1 class="font-display text-3xl font-medium md:text-5xl">{{ $currentCategory->name }}</h1>
                <div class="mt-2 flex flex-wrap items-baseline justify-between gap-3 text-sm">
                    <span class="text-cream">{{ $currentCategory->subtitle }}</span>
                    <span class="text-gold-light">{{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}</span>
                </div>
            </div>
        @else
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="font-display text-3xl font-medium md:text-4xl">{{ $currentCategory?->name ?? ($search !== '' ? 'Resultados para “'.$search.'”' : 'Todos los productos') }}</h1>
                    @if ($currentCategory?->subtitle)
                        <p class="mt-2 text-sm text-muted">{{ $currentCategory->subtitle }}</p>
                    @endif
                </div>
                <p class="text-sm text-muted">{{ $products->total() }} {{ $products->total() === 1 ? 'producto' : 'productos' }}</p>
            </div>
        @endif

        <div class="flex flex-wrap gap-10">
            <aside class="w-full lg:w-60" aria-label="Filtros">
                <form method="GET" action="{{ route('catalog') }}" class="flex flex-col gap-6">
                    <label class="field-label">Buscar
                        <input type="search" name="q" value="{{ $search }}" class="field-input" placeholder="Nombre o código">
                    </label>

                    <label class="field-label">Categoría
                        <select name="categoria" class="field-input">
                            <option value="">Todas</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->slug }}" @selected($currentCategory?->is($category))>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </label>

                    @if ($brands->isNotEmpty())
                        <label class="field-label">Marca
                            <select name="marca" class="field-input">
                                <option value="">Todas</option>
                                @foreach ($brands as $brand)
                                    <option value="{{ $brand->slug }}" @selected($currentBrand?->is($brand))>{{ $brand->name }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endif

                    @if ($sizes->isNotEmpty())
                        <fieldset class="flex flex-col gap-2">
                            <legend class="mb-2 text-xs font-semibold tracking-wide">Talle</legend>
                            <div class="flex flex-wrap gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" name="talle" value="" class="peer sr-only" @checked($currentSize === '')>
                                    <span class="flex h-10 min-w-12 items-center justify-center border border-line-strong bg-white px-3 text-xs font-semibold peer-checked:border-ink peer-checked:bg-ink peer-checked:text-gold-light peer-focus-visible:outline-2 peer-focus-visible:outline-bronze">Todos</span>
                                </label>
                                @foreach ($sizes as $size)
                                    <label class="cursor-pointer">
                                        <input type="radio" name="talle" value="{{ $size }}" class="peer sr-only" @checked($currentSize === $size)>
                                        <span class="flex h-10 min-w-12 items-center justify-center border border-line-strong bg-white px-3 text-xs font-semibold peer-checked:border-ink peer-checked:bg-ink peer-checked:text-gold-light peer-focus-visible:outline-2 peer-focus-visible:outline-bronze">{{ $size }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>
                    @endif

                    <label class="field-label">Ordenar por
                        <select name="orden" class="field-input">
                            @foreach ($sortOptions as $sortValue => $sortLabel)
                                <option value="{{ $sortValue }}" @selected($currentSort === $sortValue)>{{ $sortLabel }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex gap-2">
                        <button type="submit" class="btn-dark grow">Filtrar</button>
                        <a href="{{ route('catalog') }}" class="btn-outline">Limpiar</a>
                    </div>
                </form>
            </aside>

            <div class="min-w-0 flex-[999_1_560px]">
                @if ($products->isEmpty())
                    <div class="card flex flex-col items-center gap-4 px-6 py-16 text-center">
                        <p class="font-display text-xl">No encontramos productos</p>
                        <p class="text-sm text-muted">Probá con otra búsqueda o quitá algunos filtros.</p>
                        <a href="{{ route('catalog') }}" class="btn-outline">Ver todos los productos</a>
                    </div>
                @else
                    <div class="grid grid-cols-2 gap-4 md:gap-6 xl:grid-cols-3">
                        @foreach ($products as $product)
                            <x-product-card :product="$product" />
                        @endforeach
                    </div>
                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-layouts.store>
