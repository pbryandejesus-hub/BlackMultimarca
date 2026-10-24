<div data-variant-row class="grid grid-cols-2 items-end gap-3 border-b border-line pb-4 sm:grid-cols-[1fr_1fr_auto_7rem_auto]">
    <input type="hidden" name="variants[{{ $index }}][id]" value="{{ $variant['id'] ?? '' }}">
    <label class="field-label">Talle / medida
        <input type="text" name="variants[{{ $index }}][size]" value="{{ $variant['size'] ?? '' }}" class="field-input" placeholder="M, 40, 2 plazas…">
    </label>
    <label class="field-label">Color
        <input type="text" name="variants[{{ $index }}][color]" value="{{ $variant['color'] ?? '' }}" class="field-input" placeholder="Negro">
    </label>
    <label class="field-label">Muestra
        <input type="color" name="variants[{{ $index }}][color_hex]" value="{{ $variant['color_hex'] ?? '#151515' }}" class="h-11 w-14 cursor-pointer border border-line-strong bg-white p-1">
    </label>
    <label class="field-label">Stock
        <input type="number" name="variants[{{ $index }}][stock]" value="{{ $variant['stock'] ?? 0 }}" min="0" class="field-input" required>
    </label>
    <button type="button" data-remove-variant class="flex h-11 items-center justify-center px-3 text-sm text-red-700 hover:underline">Quitar</button>
</div>
