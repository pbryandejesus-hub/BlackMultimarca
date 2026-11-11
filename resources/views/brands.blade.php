<x-layouts.store title="Marcas">
    <div class="mx-auto max-w-7xl px-4 py-10 md:px-6">
        <nav aria-label="Ruta" class="mb-6 flex flex-wrap gap-2 text-xs text-muted">
            <a href="{{ route('home') }}" class="hover:text-bronze">Inicio</a>
            <span aria-hidden="true">/</span>
            <span>Marcas</span>
        </nav>

        <div class="mb-10 text-center">
            <span class="eyebrow">Multimarca</span>
            <h1 class="mt-2 font-display text-3xl font-medium md:text-5xl">Nuestras marcas</h1>
            <div class="mx-auto mt-4 h-px w-16 bg-gold" aria-hidden="true"></div>
        </div>

        @if ($brands->isEmpty())
            <p class="text-center text-muted">Pronto vas a encontrar acá nuestras marcas.</p>
        @else
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @foreach ($brands as $brand)
                    <a href="{{ route('brands.show', $brand) }}" class="group flex flex-col items-center gap-4 border border-line bg-white px-4 py-8 text-center hover:border-bronze">
                        <span class="flex h-20 items-center justify-center font-display text-lg tracking-[0.14em] text-coal group-hover:text-bronze">
                            @if ($brand->logoUrl())
                                <img src="{{ $brand->logoUrl() }}" alt="{{ $brand->name }}" class="max-h-20 object-contain" loading="lazy">
                            @else
                                {{ $brand->name }}
                            @endif
                        </span>
                        <span class="text-xs text-muted">{{ $brand->products_count }} {{ $brand->products_count === 1 ? 'producto' : 'productos' }}</span>
                    </a>
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.store>
