@props(['eyebrow' => null, 'title', 'center' => true])

<div {{ $attributes->merge(['class' => $center ? 'text-center' : '']) }}>
    @if ($eyebrow)
        <span class="eyebrow">{{ $eyebrow }}</span>
    @endif
    <h2 class="mt-2 font-display text-3xl font-medium md:text-4xl">{{ $title }}</h2>
    @if ($center)
        <div class="mx-auto mt-4 h-px w-16 bg-gold" aria-hidden="true"></div>
    @endif
</div>
