<x-mail::message>
# ¡Volvió lo que esperabas!

Hay stock otra vez de **{{ $variant->product->name }}** ({{ $variant->label() }}).

<x-mail::panel>
Las unidades son limitadas: si lo querés, te recomendamos comprarlo pronto.
</x-mail::panel>

<x-mail::button :url="route('products.show', $variant->product)">
Ver producto
</x-mail::button>

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
