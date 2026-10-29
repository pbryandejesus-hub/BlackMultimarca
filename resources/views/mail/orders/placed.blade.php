<x-mail::message>
# ¡Gracias por tu compra, {{ $order->customer_name }}!

Recibimos tu pedido **{{ $order->number }}**. Este es el detalle:

@include('mail.orders._items')

@if ($order->status->acceptsReceipt() && ! $order->receipt_path)
## Datos para la transferencia

Transferí **₲ {{ number_format($order->total, 0, ',', '.') }}** a esta cuenta y después subí el comprobante desde la página de tu pedido:

<x-mail::panel>
@foreach (['Banco' => $settings['bank_name'], 'Titular' => $settings['bank_holder'], 'C.I. / RUC del titular' => $settings['bank_holder_document'], 'Número de cuenta' => $settings['bank_account'], 'Alias' => $settings['bank_alias']] as $label => $value)
@if (filled($value))
**{{ $label }}:** {{ $value }}<br>
@endif
@endforeach
</x-mail::panel>
@else
Ya recibimos tu comprobante. Te avisamos apenas confirmemos el pago.
@endif

@if ($order->user_id)
<x-mail::button :url="route('orders.show', $order)">
Ver mi pedido
</x-mail::button>
@endif

Saludos,<br>
{{ config('app.name') }}
</x-mail::message>
