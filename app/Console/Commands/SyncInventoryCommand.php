<?php

namespace App\Console\Commands;

use App\Jobs\SyncBrickLinkInventoryJob;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Console\Command;

class SyncInventoryCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync
                            {store_id? : The store ID to sync (optional, syncs all stores if not provided)}
                            {--async : Run the sync job asynchronously in the queue}
                            {--item-type= : Filter by item type (PART, SET, MINIFIG, etc.)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync inventory from BrickLink to local database';

    /**
     * Execute the console command.
     */
    public function handle(InventoryService $inventoryService): int
    {
        $storeId = $this->argument('store_id');
        $async = $this->option('async');
        $itemType = $this->option('item-type');

        $params = [];
        if ($itemType) {
            $params['item_type'] = $itemType;
        }

        if ($storeId) {
            // Sync specific store
            $store = Store::find($storeId);

            if (!$store) {
                $this->error("Store with ID {$storeId} not found.");
                return 1;
            }

            return $this->syncStore($store, $inventoryService, $params, $async);
        } else {
            // Sync all stores
            $stores = Store::all();

            if ($stores->isEmpty()) {
                $this->error('No stores found.');
                return 1;
            }

            $this->info("Syncing inventory for {$stores->count()} store(s)...");

            foreach ($stores as $store) {
                $this->syncStore($store, $inventoryService, $params, $async);
            }

            $this->info('All stores synced successfully.');
            return 0;
        }
    }

    /**
     * Sync a single store
     */
    private function syncStore(Store $store, InventoryService $inventoryService, array $params, bool $async): int
    {
        $this->info("Syncing inventory for store: {$store->name} (ID: {$store->id})");

        if ($async) {
            // Dispatch job to queue
            SyncBrickLinkInventoryJob::dispatch($store, $params);
            $this->info("✓ Sync job dispatched to queue for store: {$store->name}");
            return 0;
        }

        // Run synchronously
        try {
            $result = $inventoryService->syncInventoryFromBrickLink($store, $params);

            $this->info("✓ Sync completed for store: {$store->name}");
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Items', $result['total']],
                    ['Synced', $result['synced']],
                    ['Created', $result['created']],
                    ['Updated', $result['updated']],
                    ['Errors', count($result['errors'])],
                ]
            );

            if (!empty($result['errors'])) {
                $this->warn('Some items failed to sync:');
                foreach ($result['errors'] as $error) {
                    $this->error("  - Inventory ID {$error['inventory_id']}: {$error['error']}");
                }
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("✗ Sync failed for store: {$store->name}");
            $this->error("  Error: {$e->getMessage()}");
            return 1;
        }
    }
}
