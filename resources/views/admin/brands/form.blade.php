<x-layouts.admin :title="$brand->exists ? 'Editar marca' : 'Nueva marca'">
    <form method="POST" action="{{ $brand->exists ? route('admin.brands.update', $brand) : route('admin.brands.store') }}" enctype="multipart/form-data" class="card grid max-w-2xl gap-4 p-6 sm:grid-cols-2">
        @csrf
        @if ($brand->exists)
            @method('PUT')
        @endif
        <label class="field-label">Nombre
            <input type="text" name="name" value="{{ old('name', $brand->name) }}" class="field-input" required>
        </label>
        <label class="field-label">URL (opcional)
            <input type="text" name="slug" value="{{ old('slug', $brand->slug) }}" class="field-input" placeholder="Se genera desde el nombre">
        </label>
        <div class="flex flex-col gap-3 sm:col-span-2">
            @if ($brand->logoUrl())
                <img src="{{ $brand->logoUrl() }}" alt="" class="h-16 w-32 border border-line object-contain p-2">
            @endif
            <label class="field-label">Logo (opcional)
                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" class="border border-dashed border-[#b7a584] bg-ivory-dark p-3.5 text-sm font-normal">
            </label>
        </div>
        <div class="flex gap-3 sm:col-span-2">
            <button type="submit" class="btn-dark">Guardar</button>
            <a href="{{ route('admin.brands.index') }}" class="btn-outline">Cancelar</a>
        </div>
    </form>
</x-layouts.admin>
