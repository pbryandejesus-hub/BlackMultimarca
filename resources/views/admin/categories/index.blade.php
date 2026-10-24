<x-layouts.admin title="Categorías">
    <div class="mb-6 flex justify-end">
        <a href="{{ route('admin.categories.create') }}" class="btn-dark">+ Nueva categoría</a>
    </div>

    <div class="card overflow-x-auto">
        <table class="w-full min-w-[560px] text-left text-sm">
            <thead class="border-b border-line text-xs tracking-wide text-muted uppercase">
                <tr>
                    <th class="px-4 py-3 font-semibold">Orden</th>
                    <th class="px-4 py-3 font-semibold">Categoría</th>
                    <th class="px-4 py-3 text-right font-semibold">Productos</th>
                    <th class="px-4 py-3 font-semibold">Estado</th>
                    <th class="px-4 py-3"><span class="sr-only">Acciones</span></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-4 py-3 text-muted">{{ $category->position }}</td>
                        <td class="px-4 py-3">
                            <span class="font-medium">{{ $category->name }}</span>
                            @if ($category->subtitle)
                                <span class="block text-xs text-muted">{{ $category->subtitle }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">{{ $category->products_count }}</td>
                        <td class="px-4 py-3">{{ $category->is_active ? 'Visible' : 'Oculta' }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-bronze underline">Editar</a>
                            <form method="POST" action="{{ route('admin.categories.destroy', $category) }}" class="ml-3 inline" data-confirm="¿Eliminar la categoría {{ $category->name }}?">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-700 hover:underline">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-muted">No hay categorías.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.admin>
