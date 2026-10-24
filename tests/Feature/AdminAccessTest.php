<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string}>
     */
    public static function adminRoutes(): array
    {
        return [
            'resumen' => ['admin.dashboard'],
            'pedidos' => ['admin.orders.index'],
            'productos' => ['admin.products.index'],
            'categorías' => ['admin.categories.index'],
            'marcas' => ['admin.brands.index'],
            'configuración' => ['admin.settings.edit'],
            'nuevo producto' => ['admin.products.create'],
            'nueva categoría' => ['admin.categories.create'],
            'nueva marca' => ['admin.brands.create'],
        ];
    }

    #[DataProvider('adminRoutes')]
    public function test_guests_are_sent_to_login(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    #[DataProvider('adminRoutes')]
    public function test_customers_cannot_enter_the_admin_panel(string $routeName): void
    {
        $this->actingAs(User::factory()->create())->get(route($routeName))->assertForbidden();
    }

    #[DataProvider('adminRoutes')]
    public function test_admins_can_open_every_admin_section(string $routeName): void
    {
        $this->actingAs(User::factory()->admin()->create())->get(route($routeName))->assertOk();
    }

    public function test_is_admin_cannot_be_set_when_registering(): void
    {
        $this->post(route('register'), [
            'name' => 'Intruso',
            'email' => 'intruso@example.com',
            'password' => 'secreto123',
            'password_confirmation' => 'secreto123',
            'is_admin' => true,
        ]);

        $this->assertFalse(User::where('email', 'intruso@example.com')->sole()->is_admin);
    }
}
