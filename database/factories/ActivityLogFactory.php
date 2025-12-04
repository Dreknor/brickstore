<?php

namespace Database\Factories;

use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ActivityLog>
 */
class ActivityLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $events = [
            'user.login',
            'user.logout',
            'order.created',
            'order.updated',
            'order.shipped',
            'invoice.created',
            'invoice.sent',
            'invoice.paid',
            'store.settings.updated',
            'sync.completed',
        ];

        $levels = ['debug', 'info', 'warning', 'error', 'critical'];

        return [
            'user_id' => User::factory(),
            'store_id' => Store::factory(),
            'log_level' => fake()->randomElement($levels),
            'event' => fake()->randomElement($events),
            'description' => fake()->sentence(),
            'model_type' => null,
            'model_id' => null,
            'properties' => fake()->boolean(30) ? [
                'key' => fake()->word(),
                'value' => fake()->word(),
            ] : null,
            'ip_address' => fake()->ipv4(),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * Indicate that the log level is debug
     */
    public function debug(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_level' => 'debug',
        ]);
    }

    /**
     * Indicate that the log level is info
     */
    public function info(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_level' => 'info',
        ]);
    }

    /**
     * Indicate that the log level is warning
     */
    public function warning(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_level' => 'warning',
        ]);
    }

    /**
     * Indicate that the log level is error
     */
    public function error(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_level' => 'error',
        ]);
    }

    /**
     * Indicate that the log level is critical
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'log_level' => 'critical',
        ]);
    }
}


