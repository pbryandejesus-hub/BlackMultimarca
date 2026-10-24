@props(['title' => null])

@php
    $discountPercent = (int) $storeSettings['discount_percent'];
    $discountMinItems = (int) $storeSettings['discount_min_items'];
    $whatsappNumber = preg_replace('/\D/', '', $storeSettings['store_whatsapp']);
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ? $title.' · ' : '' }}{{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col">
    <a href="#contenido" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-gold focus:px-4 focus:py-2 focus:text-ink">Saltar al contenido</a>

    <div class="bg-black px-4 py-2.5 text-center text-[11px] tracking-[0.18em] text-gold uppercase">
        @if ($discountPercent > 0 && $discountMinItems > 0)
            Llevando {{ $discountMinItems }} o más, {{ $discountPercent }}% de descuento ·
        @endif
        Pagá por transferencia bancaria · Retiro en el local o envío
    </div>

    <header class="border-b border-edge bg-ink">
        <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-between gap-4 px-4 py-4 md:px-6">
            <a href="{{ route('home') }}" aria-label="Black Multimarca, inicio">
                <x-logo size="lg" />
            </a>

            <nav aria-label="Categorías" class="order-last hidden w-full flex-wrap gap-x-6 gap-y-2 text-xs font-medium tracking-[0.16em] uppercase lg:order-none lg:flex lg:w-auto">
                @foreach ($navCategories as $navCategory)
                    <a href="{{ route('catalog', ['categoria' => $navCategory->slug]) }}" class="text-cream hover:text-gold">{{ $navCategory->name }}</a>
                @endforeach
                <a href="{{ route('catalog') }}" class="text-gold hover:text-gold-light">Ver todo</a>
            </nav>

            <div class="flex items-center gap-1">
                <form action="{{ route('catalog') }}" method="GET" role="search" class="hidden h-10 items-center rounded-full border border-edge bg-[#161616] px-3.5 md:flex">
                    <label for="header-search" class="sr-only">Buscar productos</label>
                    <svg aria-hidden="true" class="h-4 w-4 text-gold" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="11" cy="11" r="7"/><path d="M20 20l-3.5-3.5"/></svg>
                    <input id="header-search" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar productos" class="ml-2 w-40 border-0 bg-transparent text-sm text-cream placeholder:text-cream-muted/60 focus:outline-none">
                </form>

                @auth
                    @if (auth()->user()->is_admin)
                        <a href="{{ route('admin.dashboard') }}" class="hidden px-2 text-[11px] font-semibold tracking-[0.16em] text-gold uppercase hover:text-gold-light sm:inline">Panel</a>
                    @endif
                    <a href="{{ route('account.index') }}" class="flex h-11 w-11 items-center justify-center text-gold-light hover:text-gold" aria-label="Mi cuenta">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
                    </a>
                @else
                    <a href="{{ route('login') }}" class="flex h-11 w-11 items-center justify-center text-gold-light hover:text-gold" aria-label="Ingresar">
                        <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="8" r="4"/><path d="M4 21c1.5-4 4.5-6 8-6s6.5 2 8 6"/></svg>
                    </a>
                @endauth

                <a href="{{ route('cart.index') }}" class="relative flex h-11 w-11 items-center justify-center text-gold-light hover:text-gold" aria-label="Carrito, {{ $cartCount }} productos">
                    <svg aria-hidden="true" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 8h14l-1.2 12H6.2L5 8z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
                    @if ($cartCount > 0)
                        <span class="absolute top-1 right-0.5 flex h-[18px] min-w-[18px] items-center justify-center rounded-full bg-gold px-1 text-[10px] font-semibold text-ink">{{ $cartCount }}</span>
                    @endif
                </a>

                <button type="button" data-toggle="mobile-menu" aria-expanded="false" aria-controls="mobile-menu" class="flex h-11 w-11 items-center justify-center text-gold-light lg:hidden" aria-label="Abrir menú">
                    <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
        </div>

        <div id="mobile-menu" class="hidden border-t border-edge px-4 pb-5 lg:hidden">
            <form action="{{ route('catalog') }}" method="GET" role="search" class="mt-4 flex h-11 items-center rounded-full border border-edge bg-[#161616] px-4">
                <label for="mobile-search" class="sr-only">Buscar productos</label>
                <input id="mobile-search" type="search" name="q" value="{{ request('q') }}" placeholder="Buscar productos" class="w-full border-0 bg-transparent text-sm text-cream placeholder:text-cream-muted/60 focus:outline-none">
            </form>
            <nav aria-label="Categorías (móvil)" class="mt-4 flex flex-col text-sm font-medium tracking-[0.14em] uppercase">
                @foreach ($navCategories as $navCategory)
                    <a href="{{ route('catalog', ['categoria' => $navCategory->slug]) }}" class="border-b border-edge py-3 text-cream">{{ $navCategory->name }}</a>
                @endforeach
                <a href="{{ route('catalog') }}" class="py-3 text-gold">Ver todo</a>
            </nav>
        </div>
    </header>

    <main id="contenido" class="grow">
        @if (session('status') || $errors->hasAny(['cart', 'variant_id']))
            <div class="mx-auto max-w-7xl px-4 pt-6 md:px-6">
                <x-flash />
            </div>
        @endif

        {{ $slot }}
    </main>

    @if ($whatsappNumber)
        <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noopener" class="fixed right-5 bottom-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-ink text-gold shadow-lg ring-1 ring-gold/60 hover:bg-coal-soft" aria-label="Escribinos por WhatsApp">
            <svg aria-hidden="true" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 20l1.3-4A8 8 0 1 1 8 18.7z"/><path d="M9 9.5c.3 2 2.3 4.2 4.5 4.8l1.2-1.2 1.8.8-.4 1.6c-3.5.3-7.2-3.4-7-7l1.6-.4.8 1.8z"/></svg>
        </a>
    @endif

    <footer class="border-t border-edge bg-ink text-cream-muted">
        <div class="mx-auto grid max-w-7xl gap-10 px-4 py-14 sm:grid-cols-2 md:px-6 lg:grid-cols-4">
            <div class="flex flex-col gap-3">
                <x-logo />
                @if ($storeSettings['store_address'])
                    <p class="mt-2 text-sm leading-relaxed">{{ $storeSettings['store_address'] }}</p>
                @endif
                @if ($storeSettings['store_hours'])
                    <p class="text-sm leading-relaxed">{{ $storeSettings['store_hours'] }}</p>
                @endif
            </div>
            <div class="flex flex-col gap-2.5 text-sm">
                <span class="text-[11px] tracking-[0.24em] text-gold uppercase">Tienda</span>
                @foreach ($navCategories->take(6) as $navCategory)
                    <a href="{{ route('catalog', ['categoria' => $navCategory->slug]) }}" class="hover:text-gold-light">{{ $navCategory->name }}</a>
                @endforeach
            </div>
            <div class="flex flex-col gap-2.5 text-sm">
                <span class="text-[11px] tracking-[0.24em] text-gold uppercase">Ayuda</span>
                <a href="{{ route('pages.show', 'como-comprar') }}" class="hover:text-gold-light">Cómo comprar</a>
                <a href="{{ route('pages.show', 'envios') }}" class="hover:text-gold-light">Envíos y retiros</a>
                <a href="{{ route('pages.show', 'cambios') }}" class="hover:text-gold-light">Cambios y devoluciones</a>
                <a href="{{ route('pages.show', 'preguntas-frecuentes') }}" class="hover:text-gold-light">Preguntas frecuentes</a>
            </div>
            <div class="flex flex-col gap-2.5 text-sm">
                <span class="text-[11px] tracking-[0.24em] text-gold uppercase">Contacto</span>
                @if ($whatsappNumber)
                    <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank" rel="noopener" class="hover:text-gold-light">WhatsApp {{ $storeSettings['store_whatsapp'] }}</a>
                @endif
                @if ($storeSettings['store_instagram'])
                    <a href="https://instagram.com/{{ ltrim($storeSettings['store_instagram'], '@') }}" target="_blank" rel="noopener" class="hover:text-gold-light">Instagram {{ $storeSettings['store_instagram'] }}</a>
                @endif
                @if ($storeSettings['store_email'])
                    <a href="mailto:{{ $storeSettings['store_email'] }}" class="hover:text-gold-light">{{ $storeSettings['store_email'] }}</a>
                @endif
                @auth
                    <form method="POST" action="{{ route('logout') }}" class="mt-2">
                        @csrf
                        <button type="submit" class="text-left hover:text-gold-light">Cerrar sesión</button>
                    </form>
                @endauth
            </div>
        </div>
        <div class="border-t border-[#2a2418]">
            <p class="mx-auto max-w-7xl px-4 py-5 text-xs text-[#9c9384] md:px-6">© {{ date('Y') }} Black Multimarca</p>
        </div>
    </footer>
</body>
</html>
