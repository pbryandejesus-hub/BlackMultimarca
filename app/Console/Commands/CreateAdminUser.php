<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

class CreateAdminUser extends Command
{
    protected $signature = 'admin:create {email : Correo de la cuenta administradora}';

    protected $description = 'Crea la primera cuenta administradora o eleva una cuenta existente';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('Este comando requiere una terminal interactiva para no exponer contraseñas.');

            return self::FAILURE;
        }

        $email = (string) $this->argument('email');
        $existingUser = User::query()->where('email', $email)->first();

        if ($existingUser?->is_admin) {
            $this->info('La cuenta indicada ya es administradora.');

            return self::SUCCESS;
        }

        if ($existingUser && ! $this->confirm('¿Confirmás otorgar acceso administrativo a esta cuenta?')) {
            return self::SUCCESS;
        }

        $name = $existingUser->name ?? (string) $this->ask('Nombre y apellido');
        $password = $existingUser ? null : $this->secret('Contraseña (mínimo 12 caracteres)');
        $passwordConfirmation = $existingUser ? null : $this->secret('Repetí la contraseña');

        $rules = [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:120'],
        ];

        if (! $existingUser) {
            $rules['password'] = ['required', 'confirmed', Password::min(12)->letters()->mixedCase()->numbers()];
        }

        $validator = Validator::make([
            'email' => $email,
            'name' => $name,
            'password' => $password,
            'password_confirmation' => $passwordConfirmation,
        ], $rules);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $message) {
                $this->error($message);
            }

            return self::FAILURE;
        }

        $user = $existingUser ?? User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make((string) $password),
        ]);
        $user->forceFill(['is_admin' => true])->save();

        $this->info("Cuenta administradora habilitada para {$user->email}.");

        return self::SUCCESS;
    }
}
