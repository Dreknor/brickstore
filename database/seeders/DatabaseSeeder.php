<?php

namespace Database\Seeders;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin-Benutzer erstellen
        $admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@brickstore.local',
            'is_admin' => true,
        ]);

        // Test-Benutzer mit Store erstellen
        $testUser = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@brickstore.local',
        ]);

        $testStore = Store::factory()
            ->withBrickLinkCredentials()
            ->withSmtpCredentials()
            ->withNextcloudCredentials()
            ->create([
                'user_id' => $testUser->id,
                'name' => 'My LEGO Store',
                'bricklink_store_name' => 'TestStore',
            ]);

        // Orders mit Items für Test-Store erstellen
        Order::factory(15)
            ->has(OrderItem::factory()->count(rand(2, 8)))
            ->create([
                'store_id' => $testStore->id,
            ])->each(function ($order) {
                // Für 60% der Orders Rechnungen erstellen
                if (fake()->boolean(60)) {
                    Invoice::factory()->create([
                        'store_id' => $order->store_id,
                        'order_id' => $order->id,
                        'customer_name' => $order->buyer_name,
                        'customer_email' => $order->buyer_email,
                        'customer_address1' => $order->shipping_address1,
                        'customer_city' => $order->shipping_city,
                        'customer_postal_code' => $order->shipping_postal_code,
                        'customer_country' => $order->shipping_country,
                        'subtotal' => $order->subtotal,
                        'shipping_cost' => $order->shipping_cost,
                        'total' => $order->grand_total,
                    ]);
                }
            });

        // Weitere 5 normale Benutzer mit Stores
        User::factory(5)
            ->has(
                Store::factory()
                    ->withBrickLinkCredentials()
                    ->has(
                        Order::factory(rand(5, 15))
                            ->has(OrderItem::factory()->count(rand(2, 6)))
                    )
            )
            ->create();

        $this->command->info('✅ Database seeding completed!');
        $this->command->info('👤 Admin: admin@brickstore.local');
        $this->command->info('👤 Test User: test@brickstore.local');
        $this->command->info('🔑 Password für alle: password');
    }
}
