<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Models\Store;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncInventoryImages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:sync-images
                            {--store= : Store ID to sync images for}
                            {--limit= : Limit number of items to process}
                            {--force : Force re-fetch even if image URL exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync missing images for inventory items from BrickLink';

    /**
     * Execute the console command.
     */
    public function handle(BrickLinkService $brickLinkService): int
    {
        $this->info('Starting inventory images sync...');

        // Get query
        $query = Inventory::query();

        // Filter by store if specified
        if ($storeId = $this->option('store')) {
            $query->where('store_id', $storeId);
            $store = Store::find($storeId);
            if (!$store) {
                $this->error("Store with ID {$storeId} not found!");
                return self::FAILURE;
            }
            $this->info("Syncing images for store: {$store->name}");
        }

        // Filter items without images (unless force is set)
        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->whereNull('image_url')
                  ->orWhere('image_url', '');
            });
        }

        // Apply limit if specified
        if ($limit = $this->option('limit')) {
            $query->limit($limit);
        }

        $items = $query->get();
        $total = $items->count();

        if ($total === 0) {
            $this->info('No items found to process.');
            return self::SUCCESS;
        }

        $this->info("Found {$total} items to process.");

        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $success = 0;
        $failed = 0;
        $skipped = 0;

        foreach ($items as $item) {
            try {
                // Skip if already has image and not forcing
                if (!$this->option('force') && !empty($item->image_url)) {
                    $skipped++;
                    $bar->advance();
                    continue;
                }

                // Fetch image from BrickLink
                $imageData = $brickLinkService->fetchCatalogItemImage(
                    $item->store,
                    $item->item_type,
                    $item->item_no,
                    $item->color_id ?? 0
                );

                if (!empty($imageData['thumbnail_url'])) {
                    $item->image_url = $imageData['thumbnail_url'];
                    $item->save();
                    $success++;
                } else {
                    $failed++;
                    Log::warning('No image URL found for inventory item', [
                        'item_id' => $item->id,
                        'item_no' => $item->item_no,
                    ]);
                }

                // Small delay to avoid rate limiting
                usleep(100000); // 100ms

            } catch (\Exception $e) {
                $failed++;
                Log::error('Failed to fetch image for inventory item', [
                    'item_id' => $item->id,
                    'item_no' => $item->item_no,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);

        $this->info("Image sync completed!");
        $this->info("Success: {$success}");
        $this->info("Failed: {$failed}");
        $this->info("Skipped: {$skipped}");

        return self::SUCCESS;
    }
}
