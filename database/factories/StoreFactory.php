<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Store>
 */
class StoreFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'name' => fake()->company(),
            'bricklink_store_name' => fake()->unique()->userName(),
            'is_active' => true,
            'invoice_number_format' => 'RE-{year}-{number}',
            'invoice_number_counter' => 0,
            'is_small_business' => fake()->boolean(30),
            'company_name' => fake()->company(),
            'owner_name' => fake()->name(),
            'street' => fake()->streetAddress(),
            'postal_code' => fake()->postcode(),
            'city' => fake()->city(),
            'country' => 'Deutschland',
            'tax_number' => fake()->numerify('##/###/####'),
            'vat_id' => 'DE'.fake()->numerify('#########'),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'bank_name' => fake()->randomElement(['Sparkasse', 'Volksbank', 'Deutsche Bank', 'Commerzbank']),
            'bank_account_holder' => fake()->name(),
            'iban' => 'DE'.fake()->numerify('## #### #### #### #### ##'),
            'bic' => fake()->regexify('[A-Z]{6}[A-Z0-9]{2}([A-Z0-9]{3})?'),
            'nextcloud_invoice_path' => '/Rechnungen/{year}/{month}',
        ];
    }

    /**
     * Indicate that the store has BrickLink credentials.
     */
    public function withBrickLinkCredentials(): static
    {
        return $this->state(fn (array $attributes) => [
            'bl_consumer_key' => fake()->sha256(),
            'bl_consumer_secret' => fake()->sha256(),
            'bl_token' => fake()->sha256(),
            'bl_token_secret' => fake()->sha256(),
        ]);
    }

    /**
     * Indicate that the store has SMTP credentials.
     */
    public function withSmtpCredentials(): static
    {
        return $this->state(fn (array $attributes) => [
            'smtp_host' => 'smtp.example.com',
            'smtp_port' => 587,
            'smtp_username' => fake()->email(),
            'smtp_password' => fake()->password(),
            'smtp_encryption' => 'tls',
            'smtp_from_address' => fake()->email(),
            'smtp_from_name' => $attributes['name'] ?? fake()->company(),
        ]);
    }

    /**
     * Indicate that the store has Nextcloud credentials.
     */
    public function withNextcloudCredentials(): static
    {
        return $this->state(fn (array $attributes) => [
            'nextcloud_url' => 'https://cloud.example.com',
            'nextcloud_username' => fake()->userName(),
            'nextcloud_password' => fake()->password(),
        ]);
    }
}
