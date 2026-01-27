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
     * The queue this job should be sent to.
     */
    public string $queue = 'images';

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Default batch size for processing images.
     */
    public const DEFAULT_BATCH_SIZE = 50;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $storeId,
        public ?int $limit = self::DEFAULT_BATCH_SIZE,
        public bool $autoChain = true
    ) {}

    /**
     * Execute the job.
     */
    public function handle(
        ImageCacheService $imageCacheService,
        \App\Services\BrickLink\CatalogItemService $catalogItemService
    ): void
    {
        Log::info('Starting inventory image caching', [
            'store_id' => $this->storeId,
            'limit' => $this->limit,
        ]);

        // Finde alle Inventare, die entweder keine image_url haben
        // oder eine externe URL (nicht gecacht) haben
        $query = Inventory::where('store_id', $this->storeId)
            ->where(function ($q) {
                $q->whereNull('image_url')
                    ->orWhere('image_url', '')
                    ->orWhere('image_url', 'not like', asset('storage/') . '%');
            });

        if ($this->limit) {
            $query->limit($this->limit);
        }

        $inventories = $query->get();
        $cached = 0;
        $failed = 0;
        $fetched = 0; // Bild-URLs von BrickLink geholt

        $store = \App\Models\Store::find($this->storeId);
        if (!$store) {
            Log::error('Store not found', ['store_id' => $this->storeId]);
            return;
        }

        foreach ($inventories as $inventory) {
            try {
                $imageUrl = $inventory->image_url;

                // Wenn keine Bild-URL vorhanden ist, versuche sie von BrickLink zu holen
                if (empty($imageUrl)) {
                    Log::debug('Fetching image URL from BrickLink', [
                        'inventory_id' => $inventory->id,
                        'item_type' => $inventory->item_type,
                        'item_no' => $inventory->item_no,
                        'color_id' => $inventory->color_id,
                    ]);

                    $imageUrl = $catalogItemService->getItemImage(
                        $store,
                        $inventory->item_type,
                        $inventory->item_no,
                        $inventory->color_id
                    );

                    if (empty($imageUrl)) {
                        Log::warning('No image URL found on BrickLink', [
                            'inventory_id' => $inventory->id,
                            'item_no' => $inventory->item_no,
                        ]);
                        $failed++;
                        continue;
                    }

                    $fetched++;
                }

                // Überspringe bereits gecachte Bilder (die mit /storage/ beginnen)
                if (str_starts_with($imageUrl, asset('storage/'))) {
                    continue;
                }

                // Cache das Bild lokal
                $cachedUrl = $imageCacheService->cacheImage(
                    $imageUrl,
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
            'fetched_from_bricklink' => $fetched,
            'cached' => $cached,
            'failed' => $failed,
        ]);

        \App\Services\ActivityLogger::info(
            'inventory.images.cached',
            "Inventory images cached: {$cached} of {$inventories->count()} items (fetched {$fetched} from BrickLink)",
            null,
            [
                'store_id' => $this->storeId,
                'total' => $inventories->count(),
                'fetched_from_bricklink' => $fetched,
                'cached' => $cached,
                'failed' => $failed,
            ]
        );

        // Prüfe, ob noch mehr Inventare zu verarbeiten sind
        if ($this->autoChain && $inventories->count() >= ($this->limit ?? self::DEFAULT_BATCH_SIZE)) {
            $remainingCount = Inventory::where('store_id', $this->storeId)
                ->where(function ($q) {
                    $q->whereNull('image_url')
                        ->orWhere('image_url', '')
                        ->orWhere('image_url', 'not like', asset('storage/') . '%');
                })
                ->count();

            if ($remainingCount > 0) {
                Log::info('Dispatching next batch for image caching', [
                    'store_id' => $this->storeId,
                    'remaining' => $remainingCount,
                ]);

                // Dispatch nächsten Batch mit einer Verzögerung von 30 Sekunden
                self::dispatch($this->storeId, $this->limit, true)
                    ->delay(now()->addSeconds(30));
            }
        }
    }
}

