<x-layouts.page :title="$title">
    @foreach ([
        '¿Qué medios de pago aceptan?' => 'Por ahora trabajamos con transferencia bancaria. Los datos aparecen al confirmar tu pedido.',
        '¿Necesito crear una cuenta para comprar?' => 'No. Podés comprar como invitado. Si creás una cuenta, podés ver el historial de tus pedidos.',
        '¿Cómo funciona el descuento por cantidad?' => (int) $settings['discount_percent'] > 0 && (int) $settings['discount_min_items'] > 0
            ? 'Llevando '.$settings['discount_min_items'].' o más productos (pueden ser de distintas categorías) se aplica automáticamente un '.$settings['discount_percent'].'% de descuento sobre el total.'
            : 'Cuando haya promociones por cantidad las vas a ver en la tienda y se aplican solas en el carrito.',
        '¿Cuánto tiempo reservan mi pedido?' => 'Reservamos los productos por '.$settings['reserve_hours'].' horas para que hagas la transferencia.',
        '¿Cómo sé si confirmaron mi pago?' => 'En la página de tu pedido vas a ver el estado actualizado. También te avisamos por WhatsApp o correo.',
    ] as $question => $answer)
        <details class="group border-b border-line py-4" @if ($loop->first) open @endif>
            <summary class="flex cursor-pointer list-none items-center justify-between font-medium text-coal">{{ $question }} <span class="text-bronze group-open:rotate-45" aria-hidden="true">+</span></summary>
            <p class="mt-3">{{ $answer }}</p>
        </details>
    @endforeach
</x-layouts.page>
