<?php

namespace Database\Factories;

use App\Models\Inventory;
use App\Models\Store;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Inventory>
 */
class InventoryFactory extends Factory
{
    protected $model = Inventory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $itemTypes = ['PART', 'SET', 'MINIFIG', 'BOOK', 'GEAR'];
        $itemType = fake()->randomElement($itemTypes);

        return [
            'store_id' => Store::factory(),
            'inventory_id' => fake()->unique()->numberBetween(100000000, 999999999),
            'item_no' => $this->generateItemNo($itemType),
            'item_type' => $itemType,
            'color_id' => fake()->numberBetween(1, 200),
            'color_name' => fake()->randomElement(['White', 'Black', 'Red', 'Blue', 'Yellow', 'Green', 'Gray', 'Brown']),
            'quantity' => fake()->numberBetween(1, 500),
            'new_or_used' => fake()->randomElement(['N', 'U']),
            'completeness' => fake()->randomElement(['C', 'B', 'S', null]),
            'unit_price' => fake()->randomFloat(2, 0.01, 50.00),
            'bind_id' => null,
            'description' => fake()->optional()->sentence(),
            'remarks' => fake()->optional()->text(100),
            'bulk' => fake()->randomElement([1, 5, 10, 25, 50, 100]),
            'is_retain' => fake()->boolean(20),
            'is_stock_room' => fake()->boolean(30),
            'stock_room_id' => fake()->optional()->word(),
            'date_created' => fake()->dateTimeBetween('-1 year', 'now'),
            'date_updated' => fake()->dateTimeBetween('-1 month', 'now'),
            'tier_prices' => null,
            'sale_rate' => fake()->optional()->numberBetween(5, 50),
            'my_cost' => fake()->optional()->randomFloat(2, 0.01, 30.00),
            'tier_quantity1' => null,
            'tier_price1' => null,
            'tier_quantity2' => null,
            'tier_price2' => null,
            'tier_quantity3' => null,
            'tier_price3' => null,
            'my_weight' => fake()->optional()->randomFloat(2, 0.1, 100.0),
        ];
    }

    /**
     * State for new condition
     */
    public function newCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'new_or_used' => 'N',
        ]);
    }

    /**
     * State for used condition
     */
    public function usedCondition(): static
    {
        return $this->state(fn (array $attributes) => [
            'new_or_used' => 'U',
        ]);
    }

    /**
     * State for in stock room
     */
    public function inStockRoom(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_stock_room' => true,
            'stock_room_id' => fake()->word(),
        ]);
    }

    /**
     * State for available (not in stock room)
     */
    public function available(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_stock_room' => false,
            'stock_room_id' => null,
        ]);
    }

    /**
     * State with tier pricing
     */
    public function withTierPricing(): static
    {
        return $this->state(function (array $attributes) {
            $basePrice = $attributes['unit_price'];

            return [
                'tier_quantity1' => 10,
                'tier_price1' => round($basePrice * 0.9, 2),
                'tier_quantity2' => 50,
                'tier_price2' => round($basePrice * 0.85, 2),
                'tier_quantity3' => 100,
                'tier_price3' => round($basePrice * 0.8, 2),
            ];
        });
    }

    /**
     * State for specific item type
     */
    public function ofType(string $type): static
    {
        return $this->state(fn (array $attributes) => [
            'item_type' => strtoupper($type),
            'item_no' => $this->generateItemNo(strtoupper($type)),
        ]);
    }

    /**
     * Generate realistic item number based on type
     */
    protected function generateItemNo(string $type): string
    {
        return match($type) {
            'PART' => fake()->numberBetween(3001, 99999) . fake()->optional()->randomElement(['a', 'b', 'c']),
            'SET' => fake()->numberBetween(1000, 99999) . '-1',
            'MINIFIG' => 'fig-' . fake()->numberBetween(100, 999),
            'BOOK' => 'b' . fake()->numberBetween(1, 999),
            'GEAR' => 'gear' . fake()->numberBetween(1, 999),
            default => fake()->bothify('????-####'),
        };
    }
}

