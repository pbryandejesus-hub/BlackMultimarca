<x-mail::table>
| Producto | Cant. | Total |
|:--|:-:|--:|
@foreach ($items as $item)
| {{ $item->product_name }} ({{ $item->variant_label }}) | {{ $item->quantity }} | ₲ {{ number_format($item->line_total, 0, ',', '.') }} |
@endforeach
@if ($order->discount > 0)
| Descuento ({{ $order->discount_percent }}%) | | − ₲ {{ number_format($order->discount, 0, ',', '.') }} |
@endif
@if ($order->shipping > 0)
| Envío | | ₲ {{ number_format($order->shipping, 0, ',', '.') }} |
@endif
| **Total** | | **₲ {{ number_format($order->total, 0, ',', '.') }}** |
</x-mail::table>
