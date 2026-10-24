<x-layouts.page :title="$title">
    <h2>Retiro en el local</h2>
    <p>Sin costo. Te avisamos por WhatsApp cuando tu pedido esté listo.
        @if ($settings['store_address'])
            Dirección: <strong>{{ $settings['store_address'] }}</strong>.
        @endif
        @if ($settings['store_hours'])
            Horario: {{ $settings['store_hours'] }}.
        @endif
    </p>

    <h2>Envío a domicilio</h2>
    <p>
        @if ((int) $settings['shipping_cost'] > 0)
            El envío tiene un costo de <strong>₲ {{ number_format((int) $settings['shipping_cost'], 0, ',', '.') }}</strong>.
        @else
            El costo del envío se coordina por WhatsApp según tu zona.
        @endif
        Despachamos el pedido una vez confirmado el pago.
    </p>
</x-layouts.page>
