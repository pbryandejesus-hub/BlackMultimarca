<x-layouts.admin title="Marcas">
    <div class="mb-6 flex justify-end">
        <a href="{{ route('admin.brands.create') }}" class="btn-dark">+ Nueva marca</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[480px] text-left text-sm">
            <thead class="border-b border-line text-xs tracking-wide text-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Marca</th>
                    <th class="px-4 py-3 text-right font-semibold">Productos</th>
                    <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($brands as $brand)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                @if ($brand->logoUrl())
                                    <img src="{{ $brand->logoUrl() }}" alt="" class="h-10 w-16 object-contain">
                                @endif
                                <span class="font-medium">{{ $brand->name }}</span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right">{{ $brand->products_count }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.brands.edit', $brand) }}" class="text-bronze underline">Editar</a>
                            <form method="POST" action="{{ route('admin.brands.destroy', $brand) }}" class="ml-3 inline" data-confirm="¿Eliminar la marca {{ $brand->name }}? Sus productos quedarán sin marca.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-700 hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="px-4 py-8 text-center text-muted">No hay marcas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
