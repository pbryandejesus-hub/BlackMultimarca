@if (session('status'))
    <div role="status" {{ $attributes->merge(['class' => 'border border-gold/60 bg-ink px-4 py-3 text-sm text-cream']) }}>
        {{ session('status') }}
    </div>
@endif

@if ($errors->has('cart') || $errors->has('category') || $errors->has('variant_id'))
    <div role="alert" {{ $attributes->merge(['class' => 'border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-800']) }}>
        {{ $errors->first('cart') ?: ($errors->first('category') ?: $errors->first('variant_id')) }}
    </div>
@endif
