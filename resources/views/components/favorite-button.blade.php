@props(['product'])

@php($isFavorite = auth()->user()?->hasFavorited($product) ?? false)

<form method="POST" action="{{ route('favorites.toggle', $product) }}" data-favorite-form {{ $attributes }}>
    @csrf
    <button type="submit" aria-pressed="{{ $isFavorite ? 'true' : 'false' }}" aria-label="Guardar {{ $product->name }} en favoritos" title="Favoritos" class="group/fav flex h-10 w-10 items-center justify-center rounded-full bg-ivory/90 text-bronze shadow-sm transition-transform hover:scale-110 hover:text-red-700 aria-pressed:text-red-700">
        <svg aria-hidden="true" class="h-5 w-5 group-aria-pressed/fav:fill-current" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path d="M12 20s-7-4.4-7-10a4 4 0 0 1 7-2.6A4 4 0 0 1 19 10c0 5.6-7 10-7 10z"/></svg>
    </button>
</form>
