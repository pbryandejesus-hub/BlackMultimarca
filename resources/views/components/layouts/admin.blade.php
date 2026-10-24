@props(['title'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $title }} · Panel · {{ config('app.name') }}</title>
    <link rel="icon" href="{{ asset('images/logo.jpg') }}">
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen">
    <div class="flex min-h-screen flex-wrap">
        <aside class="w-full bg-ink text-cream lg:w-64">
            <div class="flex items-center justify-between px-5 py-5 lg:block">
                <a href="{{ route('admin.dashboard') }}" aria-label="Panel de administración"><x-logo /></a>
                <button type="button" data-toggle="admin-nav" aria-expanded="false" aria-controls="admin-nav" class="flex h-11 w-11 items-center justify-center text-gold-light lg:hidden" aria-label="Abrir menú">
                    <svg aria-hidden="true" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
                </button>
            </div>
            <nav id="admin-nav" class="hidden px-3 pb-6 text-sm lg:block" aria-label="Panel">
                <div class="flex flex-col gap-1">
                @foreach ([
                    ['admin.dashboard', 'admin.dashboard', 'Resumen'],
                    ['admin.orders.index', 'admin.orders.*', 'Pedidos'],
                    ['admin.products.index', 'admin.products.*', 'Productos'],
                    ['admin.categories.index', 'admin.categories.*', 'Categorías'],
                    ['admin.brands.index', 'admin.brands.*', 'Marcas'],
                    ['admin.settings.edit', 'admin.settings.*', 'Configuración'],
                ] as [$navRoute, $navPattern, $navLabel])
                    <a href="{{ route($navRoute) }}" @class([
                        'px-3 py-2.5 tracking-wide',
                        'bg-gold text-ink font-semibold' => request()->routeIs($navPattern),
                        'text-cream-muted hover:bg-white/5 hover:text-gold-light' => ! request()->routeIs($navPattern),
                    ])>{{ $navLabel }}</a>
                @endforeach
                <div class="my-3 h-px bg-edge"></div>
                <a href="{{ route('home') }}" target="_blank" class="px-3 py-2.5 text-cream-muted hover:text-gold-light">Ver tienda ↗</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full px-3 py-2.5 text-left text-cream-muted hover:text-gold-light">Cerrar sesión</button>
                </form>
                </div>
            </nav>
        </aside>

        <main class="min-w-0 flex-[999_1_560px] px-4 py-8 md:px-8">
            <h1 class="mb-6 font-display text-2xl font-semibold md:text-3xl">{{ $title }}</h1>

            @if (session('status'))
                <div role="status" class="mb-6 border border-emerald-300 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div role="alert" class="mb-6 border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800">
                    <p class="font-semibold">Revisá los datos:</p>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>
</body>
</html>
