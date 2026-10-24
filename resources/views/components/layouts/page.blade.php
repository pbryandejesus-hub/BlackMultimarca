@props(['title'])

<x-layouts.store :title="$title">
    <div class="mx-auto max-w-3xl px-4 py-12 md:px-6">
        <span class="eyebrow">Ayuda</span>
        <h1 class="mt-2 mb-8 font-display text-3xl font-medium md:text-4xl">{{ $title }}</h1>
        <div class="flex flex-col gap-5 leading-relaxed text-coal-soft [&_h2]:mt-4 [&_h2]:font-display [&_h2]:text-xl [&_h2]:text-coal [&_strong]:text-coal">
            {{ $slot }}
        </div>
    </div>
</x-layouts.store>
