@props(['size' => 'md'])

@php
    $large = $size === 'lg';
@endphp

<span {{ $attributes->merge(['class' => 'flex items-center gap-3']) }}>
    <span @class([
        'flex shrink-0 items-center justify-center rounded-b-full border border-gold font-display font-semibold text-gold',
        'h-12 w-11 text-lg' => $large,
        'h-11 w-10 text-base' => ! $large,
    ]) aria-hidden="true">BM</span>
    <span class="flex flex-col leading-none">
        <span @class(['font-display font-semibold tracking-[0.12em] text-gold-light', 'text-2xl' => $large, 'text-xl' => ! $large])>BLACK</span>
        <span class="mt-1 font-display text-[9px] tracking-[0.42em] text-gold">MULTIMARCA</span>
    </span>
</span>
