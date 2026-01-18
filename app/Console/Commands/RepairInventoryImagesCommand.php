<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairInventoryImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:repair-images
                            {store_id? : The ID of the store to repair images for}
                            {--missing-only : Only repair items with missing images}
                            {--limit= : Limit the number of items to process}';

    /**
     * The console command description.
     */
    protected $description = 'Repair missing inventory item images by fetching from BrickLink';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeId = $this->argument('store_id');
        $missingOnly = $this->option('missing-only');
        $limit = $this->option('limit') ? (int) $this->option('limit') : null;

        if (!$storeId) {
            $this->error('Store ID is required.');
            return self::FAILURE;
        }

        $query = Inventory::where('store_id', $storeId);

        if ($missingOnly) {
            $query->where(function ($q) {
                $q->whereNull('image_url')
                    ->orWhere('image_url', '=', '');
            });
        }

        if ($limit) {
            $query->limit($limit);
        }

        $inventories = $query->get();

        if ($inventories->isEmpty()) {
            $this->warn('No inventory items found to process.');
            return self::SUCCESS;
        }

        $this->info("Processing {$inventories->count()} inventory item(s)...");
        $bar = $this->output->createProgressBar($inventories->count());
        $bar->start();

        $repaired = 0;
        $failed = 0;

        $brickLinkService = app(BrickLinkService::class);
        $store = \App\Models\Store::find($storeId);

        foreach ($inventories as $inventory) {
            try {
                // Versuche Bild-URL von BrickLink API zu laden
                $imageData = $brickLinkService->fetchCatalogItemImage(
                    $store,
                    $inventory->item_type,
                    $inventory->item_no,
                    $inventory->color_id ?? 0
                );

                if (!empty($imageData['thumbnail_url'])) {
                    $inventory->update(['image_url' => $imageData['thumbnail_url']]);
                    $repaired++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::warning('Failed to repair inventory image', [
                    'inventory_id' => $inventory->id,
                    'item_no' => $inventory->item_no,
                    'error' => $e->getMessage(),
                ]);
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->info("Repair completed!");
        $this->line("✓ Repaired: <info>{$repaired}</info>");
        $this->line("✗ Failed: <comment>{$failed}</comment>");

        return self::SUCCESS;
    }
}

