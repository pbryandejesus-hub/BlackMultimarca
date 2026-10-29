<x-layouts.admin :title="'Pedido '.$order->number">
    <div class="flex flex-wrap items-start gap-6">
        <div class="flex min-w-0 flex-[999_1_520px] flex-col gap-6">
            <section class="card p-6">
                <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <h2 class="font-display text-lg font-semibold">Productos</h2>
                    <span class="text-sm text-muted">{{ $order->created_at->format('d/m/Y H:i') }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[480px] text-left text-sm">
                        <thead class="border-b border-line text-xs tracking-wide text-muted uppercase">
                            <tr>
                                <th class="py-2 font-semibold">Producto</th>
                                <th class="py-2 text-right font-semibold">Precio</th>
                                <th class="py-2 text-right font-semibold">Cant.</th>
                                <th class="py-2 text-right font-semibold">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @foreach ($order->items as $item)
                                <tr>
                                    <td class="py-3">
                                        @if ($item->product)
                                            <a href="{{ route('admin.products.edit', $item->product) }}" class="font-medium hover:text-bronze">{{ $item->product_name }}</a>
                                        @else
                                            <span class="font-medium">{{ $item->product_name }}</span>
                                        @endif
                                        @if ($item->product_sku)
                                            <span class="block text-xs text-muted">SKU: {{ $item->product_sku }}</span>
                                        @endif
                                        <span class="block text-xs text-muted">{{ $item->variant_label }}</span>
                                    </td>
                                    <td class="py-3 text-right"><x-price :amount="$item->unit_price" /></td>
                                    <td class="py-3 text-right">{{ $item->quantity }}</td>
                                    <td class="py-3 text-right"><x-price :amount="$item->line_total" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <dl class="mt-4 ml-auto flex max-w-xs flex-col gap-1.5 border-t border-line pt-4 text-sm">
                    <div class="flex justify-between"><dt>Subtotal</dt><dd><x-price :amount="$order->subtotal" /></dd></div>
                    @if ($order->discount > 0)
                        <div class="flex justify-between text-bronze"><dt>Descuento ({{ $order->discount_percent }}%)</dt><dd>− <x-price :amount="$order->discount" /></dd></div>
                    @endif
                    <div class="flex justify-between"><dt>Envío</dt><dd><x-price :amount="$order->shipping" /></dd></div>
                    <div class="flex justify-between border-t border-coal pt-2 font-semibold"><dt>Total</dt><dd><x-price :amount="$order->total" /></dd></div>
                </dl>
            </section>

            <section class="card grid gap-6 p-6 sm:grid-cols-2">
                <div>
                    <h2 class="mb-2 font-display text-lg font-semibold">Cliente</h2>
                    <dl class="flex flex-col gap-1 text-sm">
                        <dd class="font-medium">{{ $order->customer_name }}</dd>
                        <dd>C.I. {{ $order->cedula }}</dd>
                        <dd><a href="mailto:{{ $order->email }}" class="text-bronze underline">{{ $order->email }}</a></dd>
                        <dd>
                            {{ $order->phone }}
                            @if ($whatsapp = preg_replace('/\D/', '', $order->phone))
                                · <a href="https://wa.me/{{ str_starts_with($whatsapp, '0') ? '595'.substr($whatsapp, 1) : $whatsapp }}" target="_blank" rel="noopener" class="text-bronze underline">WhatsApp</a>
                            @endif
                        </dd>
                        <dd class="text-muted">{{ $order->user ? 'Cliente registrado' : 'Compra como invitado' }}</dd>
                    </dl>
                </div>
                <div>
                    <h2 class="mb-2 font-display text-lg font-semibold">{{ $order->deliveryLabel() }}</h2>
                    @if ($order->isShipping())
                        <p class="text-sm">{{ $order->address }}<br>{{ $order->city }}</p>
                    @else
                        <p class="text-sm text-muted">El cliente retira en el local.</p>
                    @endif
                    @if ($order->notes)
                        <p class="mt-3 border-l-2 border-gold pl-3 text-sm">{{ $order->notes }}</p>
                    @endif
                </div>
            </section>
        </div>

        <aside class="flex flex-[1_1_300px] flex-col gap-6">
            <section class="card flex flex-col gap-3 p-6">
                <h2 class="font-display text-lg font-semibold">Comprobante</h2>
                @if ($order->receipt_path)
                    <p class="text-sm text-muted">Subido el {{ $order->receipt_uploaded_at?->format('d/m/Y H:i') }}</p>
                    <a href="{{ route('admin.orders.receipt', $order) }}" target="_blank" class="btn-outline">Ver comprobante ↗</a>
                @else
                    <p class="text-sm text-muted">El cliente todavía no subió el comprobante.</p>
                @endif
            </section>

            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="card flex flex-col gap-4 p-6">
                @csrf
                @method('PATCH')
                <h2 class="font-display text-lg font-semibold">Estado</h2>
                <span class="self-start px-2.5 py-1 text-xs font-semibold {{ $order->status->badgeClasses() }}">{{ $order->status->label() }}</span>
                <label class="field-label">Cambiar a
                    <select name="status" class="field-input">
                        @foreach (\App\Enums\OrderStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected($order->status === $status)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field-label">Motivo del cambio
                    <input type="text" name="reason" value="{{ old('reason') }}" maxlength="500" class="field-input">
                    @error('reason')<span class="text-sm text-red-700">{{ $message }}</span>@enderror
                </label>
                <label class="field-label">Notas internas
                    <textarea name="admin_notes" rows="3" class="field-input py-2.5">{{ old('admin_notes', $order->admin_notes) }}</textarea>
                </label>
                <p class="text-xs text-muted">Al cancelar un pedido, el stock de sus productos vuelve a estar disponible.</p>
                <button type="submit" class="btn-dark">Guardar</button>
            </form>
        </aside>
    </div>
</x-layouts.admin>
