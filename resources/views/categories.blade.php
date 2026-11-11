<x-layouts.store title="Categorías">
    <div class="mx-auto max-w-7xl px-4 py-10 md:px-6">
        <nav aria-label="Ruta" class="mb-6 flex flex-wrap gap-2 text-xs text-muted">
            <a href="{{ route('home') }}" class="hover:text-bronze">Inicio</a>
            <span aria-hidden="true">/</span>
            <span>Categorías</span>
        </nav>

        <div class="mb-10 text-center">
            <span class="eyebrow">Comprá por</span>
            <h1 class="mt-2 font-display text-3xl font-medium md:text-5xl">Categorías</h1>
            <div class="mx-auto mt-4 h-px w-16 bg-gold" aria-hidden="true"></div>
        </div>

        @if ($categories->isEmpty())
            <p class="text-center text-muted">Pronto vas a encontrar acá nuestras categorías.</p>
        @else
            <div class="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-3">
                @foreach ($categories as $category)
                    <a href="{{ route('categories.show', $category) }}" class="group relative isolate flex aspect-[4/5] flex-col justify-end overflow-hidden border border-line bg-sand p-5 md:p-7">
                        @if ($category->imageUrl())
                            <img src="{{ $category->imageUrl() }}" alt="" class="absolute inset-0 -z-10 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" loading="lazy">
                            <div class="absolute inset-x-0 bottom-0 -z-10 h-2/3 bg-ink/75 [mask-image:linear-gradient(to_top,black_35%,transparent)]" aria-hidden="true"></div>
                        @else
                            <div class="absolute inset-0 -z-10 flex items-center justify-center font-display text-6xl text-bronze/40" aria-hidden="true">{{ mb_substr($category->name, 0, 1) }}</div>
                            <div class="absolute inset-x-0 bottom-0 -z-10 h-1/2 bg-ink/80 [mask-image:linear-gradient(to_top,black_35%,transparent)]" aria-hidden="true"></div>
                        @endif
                        <span class="font-display text-xl font-medium text-cream md:text-3xl">{{ $category->name }}</span>
                        @if ($category->subtitle)
                            <span class="mt-1 text-xs text-cream md:text-sm">{{ $category->subtitle }}</span>
                        @endif
                        <span class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] font-semibold tracking-[0.16em] uppercase">
                            <span class="text-gold-light">{{ $category->products_count }} {{ $category->products_count === 1 ? 'producto' : 'productos' }}</span>
                            <span class="border-b border-gold pb-0.5 text-gold-light">Ver</span>
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        <div class="mt-12 text-center">
            <a href="{{ route('catalog') }}" class="btn-outline">Ver todos los productos</a>
        </div>
    </div>
</x-layouts.store>
