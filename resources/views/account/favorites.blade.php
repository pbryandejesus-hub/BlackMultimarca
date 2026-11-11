<x-layouts.store title="Mis favoritos">
    <div class="mx-auto max-w-7xl px-4 py-10 md:px-6">
        <nav aria-label="Ruta" class="mb-6 flex flex-wrap gap-2 text-xs text-muted">
            <a href="{{ route('home') }}" class="hover:text-bronze">Inicio</a>
            <span aria-hidden="true">/</span>
            <a href="{{ route('account.index') }}" class="hover:text-bronze">Mi cuenta</a>
            <span aria-hidden="true">/</span>
            <span>Favoritos</span>
        </nav>

        <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="eyebrow">Mi cuenta</span>
                <h1 class="mt-2 font-display text-3xl font-medium md:text-4xl">Mis favoritos</h1>
            </div>
            <p class="text-sm text-muted">{{ $products->count() }} {{ $products->count() === 1 ? 'producto' : 'productos' }}</p>
        </div>

        @if ($products->isEmpty())
            <div class="card flex flex-col items-center gap-4 px-6 py-16 text-center">
                <p class="font-display text-xl">Todavía no guardaste favoritos</p>
                <p class="text-sm text-muted">Tocá el corazón de un producto para tenerlo a mano acá.</p>
                <a href="{{ route('catalog') }}" class="btn-dark">Ir a la tienda</a>
            </div>
        @else
            <div class="grid grid-cols-2 gap-4 md:gap-6 lg:grid-cols-4">
                @foreach ($products as $product)
                    <x-product-card :product="$product" />
                @endforeach
            </div>
        @endif
    </div>
</x-layouts.store>
