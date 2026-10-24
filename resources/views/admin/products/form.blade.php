@php
    $isEditing = $product->exists;
    $variantRows = old('variants', $isEditing
        ? $product->variants->map(fn ($variant) => $variant->only(['id', 'size', 'color', 'color_hex', 'stock']))->all()
        : [['size' => '', 'color' => '', 'color_hex' => '#151515', 'stock' => 0]]);
@endphp

<x-layouts.admin :title="$isEditing ? 'Editar producto' : 'Nuevo producto'">
    <form method="POST" action="{{ $isEditing ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data" class="flex flex-wrap items-start gap-6">
        @csrf
        @if ($isEditing)
            @method('PUT')
        @endif

        <div class="flex min-w-0 flex-[999_1_560px] flex-col gap-6">
            <section class="card grid gap-4 p-6 sm:grid-cols-2">
                <label class="field-label sm:col-span-2">Nombre
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" class="field-input" required>
                </label>
                <label class="field-label">Categoría
                    <select name="category_id" class="field-input" required>
                        <option value="">Elegí una categoría</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $product->category_id) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field-label">Marca
                    <select name="brand_id" class="field-input">
                        <option value="">Sin marca</option>
                        @foreach ($brands as $brand)
                            <option value="{{ $brand->id }}" @selected((int) old('brand_id', $product->brand_id) === $brand->id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="field-label">Precio (₲)
                    <input type="text" name="price" value="{{ old('price', $product->price) }}" class="field-input" inputmode="numeric" placeholder="150000" required>
                </label>
                <label class="field-label">Precio anterior (₲, opcional)
                    <input type="text" name="compare_price" value="{{ old('compare_price', $product->compare_price) }}" class="field-input" inputmode="numeric" placeholder="Se muestra tachado">
                </label>
                <label class="field-label">Código / SKU (opcional)
                    <input type="text" name="sku" value="{{ old('sku', $product->sku) }}" class="field-input">
                </label>
                <label class="field-label">URL (opcional)
                    <input type="text" name="slug" value="{{ old('slug', $product->slug) }}" class="field-input" placeholder="Se genera desde el nombre">
                </label>
                <label class="field-label sm:col-span-2">Descripción
                    <textarea name="description" rows="5" class="field-input py-2.5">{{ old('description', $product->description) }}</textarea>
                </label>
            </section>

            <section class="card flex flex-col gap-4 p-6">
                <div>
                    <h2 class="font-display text-lg font-semibold">Variantes y stock</h2>
                    <p class="text-sm text-muted">Una fila por cada combinación de talle y color. Si el producto no tiene talles ni colores (por ejemplo, un mantel), dejá una sola fila con el stock.</p>
                </div>
                <div data-variant-rows data-next-index="{{ count($variantRows) }}" class="flex flex-col gap-4">
                    @foreach (array_values($variantRows) as $rowIndex => $variantRow)
                        @include('admin.products._variant-row', ['index' => $rowIndex, 'variant' => $variantRow])
                    @endforeach
                </div>
                <button type="button" data-add-variant class="btn-outline self-start">+ Agregar variante</button>
                <template id="variant-row-template">
                    @include('admin.products._variant-row', ['index' => '__INDEX__', 'variant' => []])
                </template>
            </section>

            <section class="card flex flex-col gap-4 p-6">
                <h2 class="font-display text-lg font-semibold">Fotos</h2>
                @if ($isEditing && $product->images->isNotEmpty())
                    <div class="flex flex-wrap gap-3">
                        @foreach ($product->images as $image)
                            <div class="flex w-28 flex-col gap-1.5">
                                <img src="{{ $image->url() }}" alt="" class="aspect-[4/5] w-full border border-line object-cover">
                                <button type="submit" form="delete-image-{{ $image->id }}" class="text-xs text-red-700 hover:underline">Eliminar</button>
                            </div>
                        @endforeach
                    </div>
                @endif
                <label class="field-label">Agregar fotos (JPG, PNG o WEBP, hasta 4 MB cada una)
                    <input type="file" name="images[]" accept="image/jpeg,image/png,image/webp" multiple class="border border-dashed border-[#b7a584] bg-ivory-dark p-3.5 text-sm font-normal">
                </label>
            </section>
        </div>

        <aside class="card flex flex-[1_1_260px] flex-col gap-4 p-6">
            <label class="flex items-center gap-2.5 text-sm">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" class="h-4 w-4 accent-bronze" @checked(old('is_active', $product->is_active))>
                Visible en la tienda
            </label>
            <label class="flex items-center gap-2.5 text-sm">
                <input type="hidden" name="is_featured" value="0">
                <input type="checkbox" name="is_featured" value="1" class="h-4 w-4 accent-bronze" @checked(old('is_featured', $product->is_featured))>
                Destacado en el inicio
            </label>
            <button type="submit" class="btn-dark">{{ $isEditing ? 'Guardar cambios' : 'Crear producto' }}</button>
            @if ($isEditing)
                <a href="{{ route('products.show', $product) }}" target="_blank" class="text-center text-sm text-bronze underline">Ver en la tienda ↗</a>
            @endif
            <a href="{{ route('admin.products.index') }}" class="text-center text-sm text-muted hover:underline">Volver al listado</a>
        </aside>
    </form>

    @if ($isEditing)
        @foreach ($product->images as $image)
            <form id="delete-image-{{ $image->id }}" method="POST" action="{{ route('admin.product-images.destroy', $image) }}" data-confirm="¿Eliminar esta foto?" hidden>
                @csrf
                @method('DELETE')
            </form>
        @endforeach

        <form method="POST" action="{{ route('admin.products.destroy', $product) }}" data-confirm="¿Eliminar este producto? Esta acción no se puede deshacer." class="mt-10 border-t border-line pt-6">
            @csrf
            @method('DELETE')
            <button type="submit" class="text-sm text-red-700 hover:underline">Eliminar producto</button>
        </form>
    @endif
</x-layouts.admin>
