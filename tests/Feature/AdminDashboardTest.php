<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_charts_only_count_confirmed_sales(): void
    {
        $this->travelTo(now()->setDate(2026, 10, 29)->setTime(12, 0));

        Order::factory()->status(OrderStatus::Paid)
            ->has(OrderItem::factory()->state(['product_name' => 'Calza tiro alto', 'quantity' => 3]), 'items')
            ->create(['total' => 300000, 'created_at' => now()]);
        Order::factory()->status(OrderStatus::Delivered)
            ->has(OrderItem::factory()->state(['product_name' => 'Toallón', 'quantity' => 1]), 'items')
            ->create(['total' => 100000, 'created_at' => now()->subDays(2)]);
        Order::factory()->status(OrderStatus::Cancelled)
            ->has(OrderItem::factory()->state(['product_name' => 'Calza tiro alto', 'quantity' => 10]), 'items')
            ->create(['total' => 999000, 'created_at' => now()]);

        $response = $this->actingAs(User::factory()->admin()->create())->get(route('admin.dashboard'))->assertOk();

        $charts = $response->viewData('charts');

        $this->assertCount(30, $charts['dailySales']['values']);
        $this->assertSame('29/10', end($charts['dailySales']['labels']));
        $this->assertSame(300000, end($charts['dailySales']['values']));
        $this->assertSame(400000, array_sum($charts['dailySales']['values']));

        $this->assertCount(12, $charts['monthlySales']['values']);
        $this->assertSame(400000, end($charts['monthlySales']['values']));

        $this->assertSame(['Calza tiro alto', 'Toallón'], $charts['topProducts']['labels']);
        $this->assertSame([3, 1], $charts['topProducts']['values']);

        $this->assertSame(3, array_sum($charts['ordersByStatus']['values']));
        $this->assertSame(400000, $response->viewData('monthSales'));
        $this->assertSame(200000, $response->viewData('monthAverageTicket'));
    }

    public function test_dashboard_works_without_orders(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Todavía no hay ventas confirmadas.');
    }
}
