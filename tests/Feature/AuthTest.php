<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_customer_can_register_and_is_logged_in(): void
    {
        $this->post(route('register'), [
            'name' => 'Lucía Benítez',
            'email' => 'lucia@example.com',
            'cedula' => '5.123.456',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
        ])->assertRedirect(route('account.index'));

        $this->assertAuthenticatedAs(User::where('email', 'lucia@example.com')->sole());
    }

    public function test_login_with_a_wrong_password_fails(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => 'incorrecta'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_admins_land_on_the_panel_after_login(): void
    {
        $admin = User::factory()->admin()->create();

        $this->post(route('login'), ['email' => $admin->email, 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_cart_is_kept_after_logging_in(): void
    {
        $user = User::factory()->create();

        $this->withSession(['cart' => [5 => 2]])
            ->post(route('login'), ['email' => $user->email, 'password' => 'password']);

        $this->assertSame([5 => 2], session('cart'));
    }
}
