<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $invoiceDate = fake()->dateTimeBetween('-6 months', 'now');
        $subtotal = fake()->randomFloat(2, 20, 600);
        $shipping = fake()->randomFloat(2, 3, 15);
        $isSmallBusiness = fake()->boolean(30);
        $taxRate = $isSmallBusiness ? 0 : 19;
        $taxAmount = $isSmallBusiness ? 0 : ($subtotal + $shipping) * 0.19;

        return [
            'store_id' => \App\Models\Store::factory(),
            'order_id' => null, // Wird überschrieben wenn eine echte Order vorhanden ist
            'invoice_number' => 'RE-'.$invoiceDate->format('Y').'-'.fake()->unique()->numberBetween(1, 9999),
            'invoice_date' => $invoiceDate,
            'service_date' => $invoiceDate,
            'due_date' => (clone $invoiceDate)->modify('+14 days'),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->email(),
            'customer_address1' => fake()->streetAddress(),
            'customer_city' => fake()->city(),
            'customer_state' => fake()->optional()->state(),
            'customer_postal_code' => fake()->postcode(),
            'customer_country' => fake()->randomElement(['Deutschland', 'Österreich', 'Schweiz']),
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total' => $subtotal + $shipping + $taxAmount,
            'currency' => 'EUR',
            'status' => fake()->randomElement(['draft', 'sent', 'paid']),
            'is_paid' => fake()->boolean(60),
            'is_small_business' => $isSmallBusiness,
        ];
    }

    /**
     * Indicate that the invoice is paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'paid',
            'is_paid' => true,
            'paid_date' => fake()->dateTimeBetween($attributes['invoice_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the invoice is sent via email.
     */
    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'sent_via_email' => true,
            'email_sent_at' => fake()->dateTimeBetween($attributes['invoice_date'], 'now'),
        ]);
    }

    /**
     * Indicate that the invoice uses small business tax exemption.
     */
    public function smallBusiness(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_small_business' => true,
            'tax_rate' => 0,
            'tax_amount' => 0,
            'total' => $attributes['subtotal'] + $attributes['shipping_cost'],
        ]);
    }
}
