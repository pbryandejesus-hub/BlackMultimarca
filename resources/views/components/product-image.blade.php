@props(['product', 'image' => null])

@php
    $image ??= $product->images->first();
@endphp

@if ($image)
    <img src="{{ $image->url() }}" alt="{{ $product->name }}" loading="lazy" {{ $attributes->merge(['class' => 'h-full w-full object-cover']) }}>
@else
    <div {{ $attributes->merge(['class' => 'flex h-full w-full flex-col items-center justify-center gap-2 bg-sand text-muted']) }}>
        <span class="flex h-14 w-12 items-center justify-center rounded-b-full border border-bronze/40 font-display text-lg text-bronze/70" aria-hidden="true">BM</span>
        <span class="text-[10px] tracking-[0.2em] uppercase">Sin foto</span>
    </div>
@endif
