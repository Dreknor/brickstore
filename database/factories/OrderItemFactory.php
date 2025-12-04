<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
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
        $quantity = fake()->numberBetween(1, 20);
        $unitPrice = fake()->randomFloat(2, 0.10, 50);

        return [
            'order_id' => \App\Models\Order::factory(),
            'item_type' => fake()->randomElement(['PART', 'MINIFIG', 'SET', 'GEAR', 'INSTRUCTION']),
            'item_number' => fake()->numerify('####'),
            'item_name' => fake()->words(3, true),
            'color_id' => fake()->numberBetween(1, 150),
            'color_name' => fake()->randomElement(['Red', 'Blue', 'Yellow', 'Green', 'Black', 'White', 'Gray', 'Brown', 'Tan', 'Orange']),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $quantity * $unitPrice,
            'condition' => fake()->randomElement(['N', 'U']),
            'completeness' => fake()->optional()->randomElement(['C', 'B', 'S']),
            'description' => fake()->optional()->sentence(),
            'remarks' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the item is a LEGO part.
     */
    public function part(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => 'PART',
            'item_number' => fake()->randomElement(['3001', '3002', '3003', '3004', '2357', '3023', '3024']),
        ]);
    }

    /**
     * Indicate that the item is a minifigure.
     */
    public function minifig(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => 'MINIFIG',
            'item_number' => 'fig-'.fake()->numerify('######'),
        ]);
    }

    /**
     * Indicate that the item is a LEGO set.
     */
    public function legoSet(): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => 'SET',
            'item_number' => fake()->numerify('#####-#'),
            'completeness' => 'C',
        ]);
    }
}
