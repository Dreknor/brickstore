<?php

namespace App\Jobs;

use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncInventoryItemToBrickLinkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $inventoryId
    ) {
        $this->onQueue('default');
    }

    /**
     * Execute the job.
     */
    public function handle(InventoryService $inventoryService): void
    {
        $inventory = Inventory::find($this->inventoryId);

        if (!$inventory) {
            Log::warning('Inventory item not found for sync job', [
                'inventory_id' => $this->inventoryId,
            ]);
            return;
        }

        try {
            Log::info('Starting async sync to BrickLink', [
                'inventory_id' => $inventory->id,
                'item_no' => $inventory->item_no,
            ]);

            // Update inventory item in BrickLink
            $inventoryService->updateInventoryInBrickLink(
                $inventory->store,
                $inventory,
                $inventory->only(['quantity', 'unit_price', 'description', 'remarks', 'bulk', 'is_stock_room'])
            );

            Log::info('Async sync to BrickLink completed successfully', [
                'inventory_id' => $inventory->id,
                'item_no' => $inventory->item_no,
            ]);
        } catch (\Exception $e) {
            Log::error('Async sync to BrickLink failed', [
                'inventory_id' => $inventory->id,
                'item_no' => $inventory->item_no,
                'error' => $e->getMessage(),
            ]);

            // Re-throw to let Laravel handle retries
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Exception $exception): void
    {
        Log::error('Sync inventory job failed after max retries', [
            'inventory_id' => $this->inventoryId,
            'error' => $exception->getMessage(),
        ]);

        // Optionally notify user or store about the failure
    }
}

