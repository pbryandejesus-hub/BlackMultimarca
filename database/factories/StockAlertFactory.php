<?php

namespace Database\Factories;

use App\Models\ProductVariant;
use App\Models\StockAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockAlert>
 */
class StockAlertFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_variant_id' => ProductVariant::factory(),
            'email' => fake()->unique()->safeEmail(),
            'notified_at' => null,
        ];
    }

    public function notified(): static
    {
        return $this->state(fn (array $attributes) => ['notified_at' => now()]);
    }
}
