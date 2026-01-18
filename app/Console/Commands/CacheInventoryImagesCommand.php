<?php

namespace App\Console\Commands;

use App\Jobs\CacheInventoryImagesJob;
use App\Models\Store;
use Illuminate\Console\Command;

class CacheInventoryImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:cache-images
                            {store_id? : The ID of the store to cache images for}
                            {--limit=10 : Limit the number of images to cache per batch}
                            {--sync : Run synchronously instead of dispatching a job}
                            {--no-chain : Disable automatic chaining of batches}';

    /**
     * The console command description.
     */
    protected $description = 'Cache inventory item images from BrickLink';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeId = $this->argument('store_id');
        $limit = (int) $this->option('limit');
        $sync = $this->option('sync');
        $autoChain = !$this->option('no-chain');

        if ($storeId) {
            $store = Store::find($storeId);

            if (!$store) {
                $this->error("Store with ID {$storeId} not found.");
                return self::FAILURE;
            }

            $this->info("Caching images for store: {$store->name} (ID: {$store->id})");
            $this->info("Batch size: {$limit} images" . ($autoChain ? ' (will auto-chain if needed)' : ''));

            if ($sync) {
                // Run synchronously
                $job = new CacheInventoryImagesJob($store->id, $limit, $autoChain);
                $job->handle(
                    app(\App\Services\BrickLink\ImageCacheService::class),
                    app(\App\Services\BrickLink\CatalogItemService::class)
                );
                $this->info('Image caching completed.');
            } else {
                // Dispatch as job
                CacheInventoryImagesJob::dispatch($store->id, $limit, $autoChain);
                $this->info('Image caching job dispatched.');
            }
        } else {
            // Cache for all stores
            $stores = Store::all();

            if ($stores->isEmpty()) {
                $this->warn('No stores found.');
                return self::SUCCESS;
            }

            $this->info("Found {$stores->count()} store(s). Dispatching image caching jobs...");
            $this->info("Batch size: {$limit} images per store" . ($autoChain ? ' (will auto-chain if needed)' : ''));

            foreach ($stores as $store) {
                $this->line("- {$store->name} (ID: {$store->id})");

                if ($sync) {
                    $job = new CacheInventoryImagesJob($store->id, $limit, $autoChain);
                    $job->handle(
                        app(\App\Services\BrickLink\ImageCacheService::class),
                        app(\App\Services\BrickLink\CatalogItemService::class)
                    );
                } else {
                    CacheInventoryImagesJob::dispatch($store->id, $limit, $autoChain);
                }
            }

            $this->info($sync ? 'Image caching completed for all stores.' : 'Image caching jobs dispatched for all stores.');
        }

        return self::SUCCESS;
    }
}

