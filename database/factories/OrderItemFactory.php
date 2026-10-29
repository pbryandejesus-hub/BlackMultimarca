<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'product_variant_id' => null,
            'product_name' => fake()->words(3, true),
            'product_sku' => null,
            'variant_label' => 'Único',
            'unit_price' => 100000,
            'quantity' => 1,
            'line_total' => 100000,
        ];
    }
}
