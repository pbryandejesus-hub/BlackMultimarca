<x-layouts.admin title="Configuración">
    <form method="POST" action="{{ route('admin.settings.update') }}" class="flex max-w-4xl flex-col gap-6">
        @csrf
        @method('PUT')

        <section class="card grid gap-4 p-6 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <h2 class="font-display text-lg font-semibold">Datos bancarios</h2>
                <p class="text-sm text-muted">Se muestran al cliente cuando confirma su pedido.</p>
            </div>
            <label class="field-label">Banco
                <input type="text" name="bank_name" value="{{ old('bank_name', $settings['bank_name']) }}" class="field-input">
            </label>
            <label class="field-label">Titular de la cuenta
                <input type="text" name="bank_holder" value="{{ old('bank_holder', $settings['bank_holder']) }}" class="field-input">
            </label>
            <label class="field-label">C.I. / RUC del titular
                <input type="text" name="bank_holder_document" value="{{ old('bank_holder_document', $settings['bank_holder_document']) }}" class="field-input">
            </label>
            <label class="field-label">Número de cuenta
                <input type="text" name="bank_account" value="{{ old('bank_account', $settings['bank_account']) }}" class="field-input">
            </label>
            <label class="field-label">Alias
                <input type="text" name="bank_alias" value="{{ old('bank_alias', $settings['bank_alias']) }}" class="field-input" placeholder="Ej.: C.I., celular o correo">
            </label>
            <label class="field-label">Horas de reserva del pedido
                <input type="number" name="reserve_hours" value="{{ old('reserve_hours', $settings['reserve_hours']) }}" min="1" class="field-input">
            </label>
        </section>

        <section class="card grid gap-4 p-6 sm:grid-cols-3">
            <div class="sm:col-span-3">
                <h2 class="font-display text-lg font-semibold">Descuento por cantidad y envío</h2>
                <p class="text-sm text-muted">“Llevando X o más productos, Y% de descuento”. Poné 0 en el porcentaje para desactivarlo.</p>
            </div>
            <label class="field-label">Cantidad mínima de productos
                <input type="number" name="discount_min_items" value="{{ old('discount_min_items', $settings['discount_min_items']) }}" min="0" class="field-input">
            </label>
            <label class="field-label">Porcentaje de descuento
                <input type="number" name="discount_percent" value="{{ old('discount_percent', $settings['discount_percent']) }}" min="0" max="90" class="field-input">
            </label>
            <label class="field-label">Costo de envío (₲, 0 = a coordinar)
                <input type="text" name="shipping_cost" value="{{ old('shipping_cost', $settings['shipping_cost']) }}" inputmode="numeric" class="field-input">
            </label>
        </section>

        <section class="card grid gap-4 p-6 sm:grid-cols-2">
            <h2 class="font-display text-lg font-semibold sm:col-span-2">Contacto de la tienda</h2>
            <label class="field-label">WhatsApp (con código de país)
                <input type="text" name="store_whatsapp" value="{{ old('store_whatsapp', $settings['store_whatsapp']) }}" class="field-input" placeholder="595 981 000 000">
            </label>
            <label class="field-label">Instagram
                <input type="text" name="store_instagram" value="{{ old('store_instagram', $settings['store_instagram']) }}" class="field-input" placeholder="@blackmultimarca">
            </label>
            <label class="field-label">Correo
                <input type="email" name="store_email" value="{{ old('store_email', $settings['store_email']) }}" class="field-input">
            </label>
            <label class="field-label">Horario de atención
                <input type="text" name="store_hours" value="{{ old('store_hours', $settings['store_hours']) }}" class="field-input" placeholder="Lunes a sábado de 8 a 18 h">
            </label>
            <label class="field-label sm:col-span-2">Dirección del local
                <input type="text" name="store_address" value="{{ old('store_address', $settings['store_address']) }}" class="field-input">
            </label>
        </section>

        <button type="submit" class="btn-dark self-start">Guardar configuración</button>
    </form>
</x-layouts.admin>
