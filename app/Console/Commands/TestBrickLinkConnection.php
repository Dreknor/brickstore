<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Console\Command;

class TestBrickLinkConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bricklink:test {store_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test BrickLink API connection';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $storeId = $this->argument('store_id');

        if ($storeId) {
            $store = Store::find($storeId);
            if (! $store) {
                $this->error("Store with ID {$storeId} not found!");

                return 1;
            }
            $stores = [$store];
        } else {
            $stores = Store::all();
        }

        foreach ($stores as $store) {
            $this->info("Testing BrickLink connection for store: {$store->name} (ID: {$store->id})");

            if (! $store->hasBrickLinkCredentials()) {
                $this->warn('  ⚠ Store has no BrickLink credentials configured');

                continue;
            }

            $this->info('  ✓ BrickLink credentials found');

            // Debug: Show partial credentials
            $this->line('  → Consumer Key: '.substr($store->bl_consumer_key, 0, 8).'...');
            $this->line('  → Token: '.substr($store->bl_token, 0, 8).'...');
            $this->line('  → Consumer Secret Length: '.strlen($store->bl_consumer_secret ?? ''));
            $this->line('  → Token Secret Length: '.strlen($store->bl_token_secret ?? ''));

            try {
                $service = new BrickLinkService($store);

                $this->info('  → Fetching orders...');
                $orders = $service->getOrders(['direction' => 'in']);

                $this->info('  ✓ Successfully connected to BrickLink API');
                $this->info('  → Found '.count($orders).' orders');

                if (count($orders) > 0) {
                    $this->info('  → First 3 orders:');
                    foreach (array_slice($orders, 0, 3) as $order) {
                        $this->line("     - Order #{$order['order_id']}: {$order['status']} - {$order['buyer_name']}");
                    }
                }
            } catch (\Exception $e) {
                $this->error('  ✗ BrickLink API Error: '.$e->getMessage());
                $this->line('  → Check logs for more details: storage/logs/laravel.log');

                return 1;
            }

            $this->newLine();
        }

        return 0;
    }
}
