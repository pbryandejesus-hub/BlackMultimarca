@props(['product', 'badge' => null])

<article {{ $attributes->merge(['class' => 'card group flex flex-col']) }}>
    <a href="{{ route('products.show', $product) }}" class="relative block aspect-[4/5] overflow-hidden bg-sand">
        <x-product-image :product="$product" class="transition-transform duration-500 group-hover:scale-105" />
        @if ($product->images->count() > 1)
            <x-product-image :product="$product" :image="$product->images[1]" class="absolute inset-0 opacity-0 transition-opacity duration-500 group-hover:opacity-100" />
        @endif
        @if ($badge)
            <span class="absolute top-3 left-3 bg-ink px-2.5 py-1.5 text-[10px] font-semibold tracking-[0.16em] text-gold uppercase">{{ $badge }}</span>
        @endif
    </a>
    <div class="flex grow flex-col gap-1.5 p-4">
        <span class="text-[11px] tracking-[0.2em] text-bronze uppercase">
            {{ $product->brand?->name ?? 'Black Multimarca' }} · {{ $product->category->name }}
        </span>
        <a href="{{ route('products.show', $product) }}" class="text-[15px] font-medium text-coal hover:text-bronze">{{ $product->name }}</a>
        <div class="mt-1 flex items-baseline gap-2">
            <x-price :amount="$product->price" class="font-display text-xl font-semibold" />
            @if ($product->hasComparePrice())
                <x-price :amount="$product->compare_price" class="text-sm text-muted line-through" />
            @endif
        </div>
        <a href="{{ route('products.show', $product) }}" class="btn-dark mt-3 w-full">Ver producto</a>
    </div>
</article>
