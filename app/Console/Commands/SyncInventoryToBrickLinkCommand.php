<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncInventoryToBrickLinkCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:sync-to-bricklink
                            {store_id? : The ID of the store}
                            {--all : Sync all stores}
                            {--item-id= : Sync specific item by ID}
                            {--item-no= : Sync specific item by item number}';

    /**
     * The console command description.
     */
    protected $description = 'Synchronize inventory items to BrickLink';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeId = $this->argument('store_id');
        $allStores = $this->option('all');
        $itemId = $this->option('item-id');
        $itemNo = $this->option('item-no');

        if (!$storeId && !$allStores && !$itemId && !$itemNo) {
            $this->error('Please provide a store ID, use --all, or specify --item-id or --item-no');
            return self::FAILURE;
        }

        try {
            $inventoryService = app(InventoryService::class);

            if ($itemId) {
                // Sync specific item by ID
                return $this->syncItemById($inventoryService, $itemId);
            } elseif ($itemNo) {
                // Sync specific item by number
                return $this->syncItemByNumber($inventoryService, $itemNo);
            } elseif ($allStores) {
                // Sync all stores
                return $this->syncAllStores($inventoryService);
            } else {
                // Sync specific store
                return $this->syncStore($inventoryService, $storeId);
            }
        } catch (\Exception $e) {
            $this->error("Error during sync: {$e->getMessage()}");
            Log::error('Inventory sync to BrickLink failed', [
                'error' => $e->getMessage(),
            ]);
            return self::FAILURE;
        }
    }

    /**
     * Sync specific item by ID
     */
    private function syncItemById(InventoryService $service, int $itemId): int
    {
        $inventory = Inventory::find($itemId);

        if (!$inventory) {
            $this->error("Inventory item with ID {$itemId} not found");
            return self::FAILURE;
        }

        $this->info("Syncing item: {$inventory->item_no}");

        try {
            // Update item (which syncs to BrickLink)
            $service->updateInventoryInBrickLink(
                $inventory->store,
                $inventory,
                $inventory->only(['quantity', 'unit_price', 'description', 'remarks'])
            );

            $this->info("✅ Item {$inventory->item_no} synced successfully");
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("❌ Failed to sync item {$inventory->item_no}: {$e->getMessage()}");
            return self::FAILURE;
        }
    }

    /**
     * Sync specific item by number
     */
    private function syncItemByNumber(InventoryService $service, string $itemNo): int
    {
        $inventory = Inventory::where('item_no', $itemNo)->first();

        if (!$inventory) {
            $this->error("Inventory item with number {$itemNo} not found");
            return self::FAILURE;
        }

        return $this->syncItemById($service, $inventory->id);
    }

    /**
     * Sync specific store
     */
    private function syncStore(InventoryService $service, int $storeId): int
    {
        $store = Store::find($storeId);

        if (!$store) {
            $this->error("Store with ID {$storeId} not found");
            return self::FAILURE;
        }

        $this->info("Syncing all items for store: {$store->name}");

        $inventories = Inventory::where('store_id', $storeId)->get();

        if ($inventories->isEmpty()) {
            $this->warn('No inventory items found for this store');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($inventories->count());
        $bar->start();

        $synced = 0;
        $failed = 0;

        foreach ($inventories as $inventory) {
            try {
                $service->updateInventoryInBrickLink(
                    $store,
                    $inventory,
                    $inventory->only(['quantity', 'unit_price', 'description', 'remarks'])
                );
                $synced++;
            } catch (\Exception $e) {
                $failed++;
                Log::warning('Failed to sync inventory item', [
                    'inventory_id' => $inventory->id,
                    'item_no' => $inventory->item_no,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("✅ Sync completed!");
        $this->line("  Synced: <info>{$synced}</info>");
        $this->line("  Failed: <comment>{$failed}</comment>");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }

    /**
     * Sync all stores
     */
    private function syncAllStores(InventoryService $service): int
    {
        $stores = Store::all();

        if ($stores->isEmpty()) {
            $this->warn('No stores found');
            return self::SUCCESS;
        }

        $this->info("Syncing {$stores->count()} store(s)");

        foreach ($stores as $store) {
            $this->line("\n▸ {$store->name}");
            $this->syncStore($service, $store->id);
        }

        return self::SUCCESS;
    }
}

