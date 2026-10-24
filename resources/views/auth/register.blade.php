<x-layouts.store title="Crear cuenta">
    <div class="mx-auto max-w-lg px-4 py-14">
        <div class="card flex flex-col gap-6 p-7 md:p-9">
            <div class="text-center">
                <span class="eyebrow">Black Multimarca</span>
                <h1 class="mt-2 font-display text-3xl font-medium">Crear cuenta</h1>
                <p class="mt-2 text-sm text-muted">Guardá tus datos y seguí tus pedidos.</p>
            </div>
            <form method="POST" action="{{ route('register') }}" class="grid gap-4 sm:grid-cols-2">
                @csrf
                <label class="field-label sm:col-span-2">Nombre y apellido
                    <input type="text" name="name" value="{{ old('name') }}" class="field-input" autocomplete="name" required>
                    @error('name') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="field-label sm:col-span-2">Correo electrónico
                    <input type="email" name="email" value="{{ old('email') }}" class="field-input" autocomplete="email" required>
                    @error('email') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="field-label">Teléfono / WhatsApp
                    <input type="tel" name="phone" value="{{ old('phone') }}" class="field-input" autocomplete="tel">
                    @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="field-label">Cédula de Identidad
                    <input type="text" name="cedula" value="{{ old('cedula') }}" class="field-input" inputmode="numeric">
                    @error('cedula') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="field-label">Contraseña
                    <input type="password" name="password" class="field-input" autocomplete="new-password" required>
                    @error('password') <span class="field-error">{{ $message }}</span> @enderror
                </label>
                <label class="field-label">Repetir contraseña
                    <input type="password" name="password_confirmation" class="field-input" autocomplete="new-password" required>
                </label>
                <button type="submit" class="btn-dark mt-2 sm:col-span-2">Crear cuenta</button>
            </form>
            <p class="text-center text-sm text-muted">¿Ya tenés cuenta? <a href="{{ route('login') }}" class="text-bronze underline">Ingresá</a></p>
        </div>
    </div>
</x-layouts.store>
