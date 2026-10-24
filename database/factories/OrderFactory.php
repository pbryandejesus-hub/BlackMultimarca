<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => null,
            'status' => OrderStatus::PendingPayment,
            'customer_name' => fake()->name(),
            'cedula' => (string) fake()->numberBetween(1000000, 7000000),
            'email' => fake()->safeEmail(),
            'phone' => '0981'.fake()->numerify('######'),
            'delivery_method' => Order::DELIVERY_PICKUP,
            'items_count' => 1,
            'subtotal' => 100000,
            'discount' => 0,
            'discount_percent' => 0,
            'shipping' => 0,
            'total' => 100000,
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => $status,
        ]);
    }
}
