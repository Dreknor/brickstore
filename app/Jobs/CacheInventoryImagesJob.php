<?php

namespace App\Jobs;

use App\Models\Inventory;
use App\Services\BrickLink\ImageCacheService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CacheInventoryImagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $storeId,
        public ?int $limit = null
    ) {}

    /**
     * Execute the job.
     */
    public function handle(ImageCacheService $imageCacheService): void
    {
        Log::info('Starting inventory image caching', [
            'store_id' => $this->storeId,
            'limit' => $this->limit,
        ]);

        $query = Inventory::where('store_id', $this->storeId)
            ->whereNotNull('image_url')
            ->where('image_url', '!=', '');

        if ($this->limit) {
            $query->limit($this->limit);
        }

        $inventories = $query->get();
        $cached = 0;
        $failed = 0;

        foreach ($inventories as $inventory) {
            try {
                $cachedUrl = $imageCacheService->cacheImage(
                    $inventory->image_url,
                    $inventory->item_type,
                    $inventory->item_no,
                    $inventory->color_id
                );

                if ($cachedUrl) {
                    // WICHTIG: Speichere die gecachte URL zurück in die Datenbank
                    // So haben wir zukünftig schnellere Zugriffe auf lokale Bilder
                    $inventory->update(['image_url' => $cachedUrl]);
                    $cached++;
                } else {
                    $failed++;
                }

                // Small delay to avoid overwhelming the server
                usleep(100000); // 100ms delay
            } catch (\Exception $e) {
                $failed++;
                Log::warning('Failed to cache inventory image', [
                    'inventory_id' => $inventory->id,
                    'item_no' => $inventory->item_no,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Inventory image caching completed', [
            'store_id' => $this->storeId,
            'total' => $inventories->count(),
            'cached' => $cached,
            'failed' => $failed,
        ]);

        \App\Services\ActivityLogger::info(
            'inventory.images.cached',
            "Inventory images cached: {$cached} of {$inventories->count()} items",
            null,
            [
                'store_id' => $this->storeId,
                'total' => $inventories->count(),
                'cached' => $cached,
                'failed' => $failed,
            ]
        );
    }
}

