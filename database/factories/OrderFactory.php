<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
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
        $subtotal = fake()->randomFloat(2, 10, 500);
        $shipping = fake()->randomFloat(2, 3, 15);
        $tax = $subtotal * 0.19;

        return [
            'store_id' => \App\Models\Store::factory(),
            'bricklink_order_id' => fake()->unique()->numerify('#######'),
            'order_date' => fake()->dateTimeBetween('-3 months', 'now'),
            'status' => fake()->randomElement(['Pending', 'Updated', 'Processing', 'Ready', 'Paid', 'Packed', 'Shipped', 'Received', 'Completed']),
            'buyer_name' => fake()->name(),
            'buyer_email' => fake()->email(),
            'buyer_username' => fake()->userName(),
            'shipping_name' => fake()->name(),
            'shipping_address1' => fake()->streetAddress(),
            'shipping_address2' => fake()->optional()->buildingNumber(),
            'shipping_city' => fake()->city(),
            'shipping_state' => fake()->optional()->state(),
            'shipping_postal_code' => fake()->postcode(),
            'shipping_country' => fake()->randomElement(['Deutschland', 'Österreich', 'Schweiz', 'Niederlande', 'Frankreich']),
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'tax' => $tax,
            'grand_total' => $subtotal + $shipping + $tax,
            'insurance' => fake()->randomFloat(2, 0, 5),
            'discount' => 0,
            'currency_code' => 'EUR',
            'shipping_method' => fake()->randomElement(['DHL', 'Deutsche Post', 'DPD', 'Hermes', 'UPS']),
            'payment_method' => fake()->randomElement(['PayPal', 'Überweisung', 'Kreditkarte']),
            'is_paid' => fake()->boolean(70),
            'buyer_remarks' => fake()->optional()->sentence(),
            'seller_remarks' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the order is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_paid' => true,
            'paid_date' => fake()->dateTimeBetween($attributes['order_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the order is shipped.
     */
    public function shipped(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Shipped',
            'shipped_date' => fake()->dateTimeBetween($attributes['order_date'], 'now'),
            'tracking_number' => fake()->numerify('##########'),
        ]);
    }

    /**
     * Indicate that the order is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'Completed',
            'is_paid' => true,
            'paid_date' => fake()->dateTimeBetween($attributes['order_date'], 'now'),
            'shipped_date' => fake()->dateTimeBetween($attributes['order_date'], 'now'),
            'tracking_number' => fake()->numerify('##########'),
        ]);
    }
}
