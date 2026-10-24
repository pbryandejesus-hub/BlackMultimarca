<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'cedula' => ['nullable', 'string', 'max:20', 'regex:/^[0-9.\-]+$/'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ], [], [
            'name' => 'nombre y apellido',
            'phone' => 'teléfono / WhatsApp',
            'cedula' => 'cédula de identidad',
        ]);

        $user = User::create($validated);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('account.index'))->with('status', '¡Bienvenido/a a Black Multimarca!');
    }
}
