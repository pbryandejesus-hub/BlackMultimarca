@props(['variants', 'hidden' => false])

{{-- Con "hidden" lo muestra el selector de talle y color al elegir una combinación agotada; si no, se elige acá la variante. --}}
<form method="POST" action="{{ route('stock-alerts.store') }}" @if ($hidden) data-stock-alert hidden @endif class="flex flex-col gap-3 border border-gold/60 bg-white p-4">
    @csrf
    <p class="text-sm font-semibold">Avisarme cuando vuelva</p>
    <p class="-mt-2 text-xs text-muted">Dejanos tu WhatsApp o tu email y te avisamos apenas tengamos stock otra vez.</p>

    @if ($hidden)
        <input type="hidden" name="variant_id" value="">
    @elseif ($variants->count() === 1)
        <input type="hidden" name="variant_id" value="{{ $variants->first()->id }}">
    @else
        <label class="field-label">Talle y color
            <select name="variant_id" class="field-input" required>
                @foreach ($variants as $variant)
                    <option value="{{ $variant->id }}">{{ $variant->label() }}</option>
                @endforeach
            </select>
        </label>
    @endif

    <div class="flex flex-wrap gap-2">
        <label for="stock-alert-contact" class="sr-only">Tu WhatsApp o email</label>
        <input id="stock-alert-contact" type="text" name="contact" value="{{ old('contact', auth()->user()?->phone ?: auth()->user()?->email) }}" required maxlength="120" placeholder="0981 123456 o tu@email.com" class="field-input min-w-0 flex-[1_1_200px]">
        <button type="submit" class="btn-outline flex-[0_0_auto]">Avisarme</button>
    </div>
    @error('contact')
        <p class="field-error">{{ $message }}</p>
    @enderror
</form>
