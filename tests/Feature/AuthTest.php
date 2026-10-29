<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\StoreSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitors_see_the_create_account_button_and_customers_do_not(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Crear cuenta')
            ->assertSee(route('register'), false);

        $this->actingAs(User::factory()->create())->get(route('home'))
            ->assertDontSee('Crear cuenta');
    }

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

    public function test_store_seeder_does_not_create_an_admin_with_a_default_password(): void
    {
        config()->set('services.admin_seed.email', null);
        config()->set('services.admin_seed.password', null);
        $this->seed(StoreSeeder::class);

        $this->assertDatabaseMissing('users', ['email' => 'admin@blackmultimarca.com']);
        $this->assertDatabaseCount('users', 0);
    }
}
