<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feedback>
 */
class FeedbackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $ratings = ['S', 'G', 'N']; // Satisfied, Good, Neutral

        return [
            'order_id' => \App\Models\Order::factory(),
            'direction' => fake()->randomElement(['from_buyer', 'to_buyer']),
            'rating' => fake()->numberBetween(0, 2), // 0=Praise, 1=Neutral, 2=Complaint
            'comment' => fake()->sentence(),
            'rating_of_bs' => fake()->randomElement($ratings),
            'rating_of_td' => fake()->randomElement($ratings),
            'rating_of_comm' => fake()->randomElement($ratings),
            'rating_of_ship' => fake()->randomElement($ratings),
            'rating_of_pack' => fake()->randomElement($ratings),
            'can_edit' => false,
            'can_reply' => false,
            'feedback_date' => fake()->dateTimeBetween('-1 month', 'now'),
        ];
    }

    public function fromBuyer(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'from_buyer',
        ]);
    }

    public function toBuyer(): static
    {
        return $this->state(fn (array $attributes) => [
            'direction' => 'to_buyer',
        ]);
    }

    public function praise(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 0,
            'comment' => fake()->randomElement([
                'Excellent seller! Fast shipping and great packaging!',
                'Perfect transaction, highly recommended!',
                'Great communication and fast delivery!',
            ]),
        ]);
    }

    public function neutral(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 1,
            'comment' => fake()->randomElement([
                'Transaction was okay.',
                'Average experience.',
            ]),
        ]);
    }

    public function complaint(): static
    {
        return $this->state(fn (array $attributes) => [
            'rating' => 2,
            'comment' => fake()->randomElement([
                'Shipping took too long.',
                'Some items were missing.',
            ]),
        ]);
    }
}
