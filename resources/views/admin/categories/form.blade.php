<x-layouts.admin :title="$category->exists ? 'Editar categoría' : 'Nueva categoría'">
    <form method="POST" action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}" enctype="multipart/form-data" class="card grid max-w-2xl gap-4 p-6 sm:grid-cols-2">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif
        <label class="field-label">Nombre
            <input type="text" name="name" value="{{ old('name', $category->name) }}" class="field-input" required>
        </label>
        <label class="field-label">URL (opcional)
            <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" class="field-input" placeholder="Se genera desde el nombre">
        </label>
        <label class="field-label sm:col-span-2">Subtítulo
            <input type="text" name="subtitle" value="{{ old('subtitle', $category->subtitle) }}" class="field-input" placeholder="Ej.: Training · Running">
        </label>
        <label class="field-label">Orden en el menú
            <input type="number" name="position" value="{{ old('position', $category->position ?? 0) }}" min="0" class="field-input">
        </label>
        <label class="flex items-center gap-2.5 self-end pb-3 text-sm">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-bronze" @checked(old('is_active', $category->is_active))>
            Visible en la tienda
        </label>
        <div class="flex flex-col gap-3 sm:col-span-2">
            @if ($category->imageUrl())
                <img src="{{ $category->imageUrl() }}" alt="" class="h-32 w-24 border border-line object-cover">
            @endif
            <label class="field-label">Imagen (opcional)
                <input type="file" name="image" accept="image/jpeg,image/png,image/webp" class="border border-dashed border-[#b7a584] bg-ivory-dark p-3.5 text-sm font-normal">
            </label>
        </div>
        <div class="flex gap-3 sm:col-span-2">
            <button type="submit" class="btn-dark">Guardar</button>
            <a href="{{ route('admin.categories.index') }}" class="btn-outline">Cancelar</a>
        </div>
    </form>
</x-layouts.admin>
