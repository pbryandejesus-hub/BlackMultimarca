<x-mail::message>
# Hola, {{ $order->customer_name }}

Tu pedido **{{ $order->number }}** cambió de estado:

<x-mail::panel>
**{{ $order->status->label() }}**

{{ $statusMessage }}
</x-mail::panel>

@include('mail.orders._items')

@if ($order->user_id)
<x-mail::button :url="route('orders.show', $order)">
Ver mi pedido
</x-mail::button>
@endif

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
