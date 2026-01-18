<?php

namespace App\Jobs;

use App\Models\Store;
use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncBrickLinkInventoryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60; // seconds
    public int $timeout = 600; // 10 minutes timeout for large inventories

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Store $store,
        public array $params = []
    ) {}

    /**
     * Execute the job.
     */
    public function handle(InventoryService $inventoryService): void
    {
        try {
            Log::info('Starting BrickLink inventory sync job', [
                'store_id' => $this->store->id,
                'store_name' => $this->store->name,
                'params' => $this->params,
            ]);

            // Log job start
            \App\Services\ActivityLogger::info(
                'inventory.sync.job.started',
                'Background inventory sync job started',
                $this->store,
                ['params' => $this->params]
            );

            $result = $inventoryService->syncInventoryFromBrickLink($this->store, $this->params);

            Log::info('BrickLink inventory sync job completed', [
                'store_id' => $this->store->id,
                'result' => $result,
            ]);

            // Log job completion (success already logged by service)
            \App\Services\ActivityLogger::info(
                'inventory.sync.job.completed',
                "Background sync completed successfully: {$result['synced']} items",
                $this->store,
                ['result' => $result]
            );

            // Optional: Benachrichtigung an User senden
            // $this->store->user->notify(new InventorySyncCompleted($result));

        } catch (\Exception $e) {
            Log::error('BrickLink inventory sync job failed', [
                'store_id' => $this->store->id,
                'attempt' => $this->attempts(),
                'error' => $e->getMessage(),
            ]);

            // Log job failure
            \App\Services\ActivityLogger::error(
                'inventory.sync.job.failed',
                "Background sync failed: {$e->getMessage()}",
                $this->store,
                [
                    'error' => $e->getMessage(),
                    'attempt' => $this->attempts(),
                ]
            );

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('BrickLink inventory sync job failed permanently', [
            'store_id' => $this->store->id,
            'error' => $exception->getMessage(),
        ]);

        // Log permanent failure
        \App\Services\ActivityLogger::critical(
            'inventory.sync.job.failed.permanent',
            "Background sync failed permanently after all retries",
            $this->store,
            ['error' => $exception->getMessage()]
        );

        // Optional: Benachrichtigung an User senden
        // $this->store->user->notify(new InventorySyncFailed($exception));
    }
}

