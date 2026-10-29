<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_admin_command_creates_a_hashed_administrator(): void
    {
        $this->artisan('admin:create', ['email' => 'owner@example.com'])
            ->expectsQuestion('Nombre y apellido', 'Tienda Owner')
            ->expectsQuestion('Contraseña (mínimo 12 caracteres)', 'StrongPassword123')
            ->expectsQuestion('Repetí la contraseña', 'StrongPassword123')
            ->expectsOutputToContain('Cuenta administradora habilitada para owner@example.com.')
            ->assertExitCode(0);

        $user = User::sole();

        $this->assertTrue($user->is_admin);
        $this->assertTrue(Hash::check('StrongPassword123', $user->password));
    }

    public function test_the_admin_command_requires_confirmation_to_promote_an_existing_user(): void
    {
        $user = User::factory()->create();

        $this->artisan('admin:create', ['email' => $user->email])
            ->expectsConfirmation('¿Confirmás otorgar acceso administrativo a esta cuenta?', 'yes')
            ->expectsOutputToContain("Cuenta administradora habilitada para {$user->email}.")
            ->assertExitCode(0);

        $this->assertTrue($user->fresh()->is_admin);
    }
}
