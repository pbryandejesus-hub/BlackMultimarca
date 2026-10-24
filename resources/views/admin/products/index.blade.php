<x-layouts.admin title="Productos">
    <div class="mb-6 flex flex-wrap items-end justify-between gap-4">
        <form method="GET" class="flex flex-wrap items-end gap-3">
            <label class="field-label">Buscar
                <input type="search" name="q" value="{{ $search }}" class="field-input w-56" placeholder="Nombre o código">
            </label>
            <label class="field-label">Categoría
                <select name="categoria" class="field-input w-48">
                    <option value="">Todas</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((int) request('categoria') === $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="btn-outline">Filtrar</button>
        </form>
        <a href="{{ route('admin.products.create') }}" class="btn-dark">+ Nuevo producto</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[760px] text-left text-sm">
            <thead class="border-b border-line text-xs tracking-wide text-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Producto</th>
                    <th class="px-4 py-3 font-semibold">Categoría</th>
                    <th class="px-4 py-3 text-right font-semibold">Precio</th>
                    <th class="px-4 py-3 text-right font-semibold">Stock</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($products as $product)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-14 w-11 shrink-0 overflow-hidden bg-sand"><x-product-image :product="$product" /></div>
                                <div class="flex flex-col">
                                    <span class="font-medium">{{ $product->name }}</span>
                                    <span class="text-xs text-muted">{{ $product->brand?->name ?? 'Sin marca' }}{{ $product->sku ? ' · '.$product->sku : '' }}</span>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ $product->category->name }}</td>
                        <td class="px-4 py-3 text-right"><x-price :amount="$product->price" /></td>
                        <td @class(['px-4 py-3 text-right font-semibold', 'text-red-700' => $product->totalStock() === 0])>{{ $product->totalStock() }}</td>
                        <td class="px-4 py-3">
                            <span @class(['px-2 py-1 text-xs font-semibold', 'bg-emerald-100 text-emerald-900' => $product->is_active, 'bg-stone-200 text-stone-700' => ! $product->is_active])>{{ $product->is_active ? 'Visible' : 'Oculto' }}</span>
                            @if ($product->is_featured)
                                <span class="ml-1 bg-gold/30 px-2 py-1 text-xs font-semibold text-bronze-dark">Destacado</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.products.edit', $product) }}" class="text-bronze underline">Editar</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-8 text-center text-muted">No hay productos. <a href="{{ route('admin.products.create') }}" class="text-bronze underline">Cargá el primero</a>.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-6">{{ $products->links() }}</div>
</x-layouts.admin>
