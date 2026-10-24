<x-layouts.page :title="$title">
    <h2>1. Elegí tus productos</h2>
    <p>Recorré la tienda, elegí color y talle, y agregá al carrito todo lo que quieras.
        @if ((int) $settings['discount_percent'] > 0 && (int) $settings['discount_min_items'] > 0)
            <strong>Llevando {{ $settings['discount_min_items'] }} o más productos tenés {{ $settings['discount_percent'] }}% de descuento</strong> en toda la compra.
        @endif
    </p>

    <h2>2. Completá tus datos</h2>
    <p>En “Finalizar compra” ingresá tu nombre, cédula de identidad, correo y WhatsApp, y elegí si retirás en el local o preferís envío a domicilio. No hace falta crear una cuenta.</p>

    <h2>3. Transferí el total</h2>
    <p>Al confirmar el pedido te mostramos los datos bancarios. Transferí el monto exacto e indicá el número de pedido en el concepto. Reservamos tus productos por {{ $settings['reserve_hours'] }} horas.</p>

    <h2>4. Subí el comprobante</h2>
    <p>Desde la página de tu pedido subís la captura o el PDF de la transferencia. Cuando confirmemos el pago te avisamos y preparamos tu pedido.</p>
</x-layouts.page>
