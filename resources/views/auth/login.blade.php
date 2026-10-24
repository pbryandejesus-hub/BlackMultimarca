<x-layouts.store title="Ingresar">
    <div class="mx-auto max-w-md px-4 py-14">
        <div class="card flex flex-col gap-6 p-7 md:p-9">
            <div class="text-center">
                <span class="eyebrow">Bienvenido/a</span>
                <h1 class="mt-2 font-display text-3xl font-medium">Ingresar</h1>
            </div>
            <form method="POST" action="{{ route('login') }}" class="flex flex-col gap-4">
                @csrf
                <label class="field-label">Correo electrónico
                    <input type="email" name="email" value="{{ old('email') }}" class="field-input" autocomplete="email" required autofocus>
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="field-label">Contraseña
                    <input type="password" name="password" class="field-input" autocomplete="current-password" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="remember" value="1" class="h-4 w-4 accent-bronze"> Recordarme
                </label>
                <button type="submit" class="btn-dark mt-2">Ingresar</button>
            </form>
            <p class="text-center text-sm text-muted">¿No tenés cuenta? <a href="{{ route('register') }}" class="text-bronze underline">Registrate</a></p>
        </div>
    </div>
</x-layouts.store>
