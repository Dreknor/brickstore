<?php

namespace App\Console\Commands;

use App\Jobs\CacheInventoryImagesJob;
use App\Models\Inventory;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessImageCachingQueueCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:process-image-queue
                            {--stores=* : Specific store IDs to process}';

    /**
     * The console command description.
     */
    protected $description = 'Process inventory image caching queue (runs every minute via cron)';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeIds = $this->option('stores');

        if (empty($storeIds)) {
            // Verarbeite alle Stores
            $stores = Store::all();
        } else {
            $stores = Store::whereIn('id', $storeIds)->get();
        }

        if ($stores->isEmpty()) {
            $this->info('No stores found.');
            return self::SUCCESS;
        }

        $dispatched = 0;

        foreach ($stores as $store) {
            // Prüfe, ob es Inventare gibt, die Caching benötigen
            $needsCaching = Inventory::where('store_id', $store->id)
                ->where(function ($q) {
                    $q->whereNull('image_url')
                        ->orWhere('image_url', '')
                        ->orWhere('image_url', 'not like', asset('storage/') . '%');
                })
                ->exists();

            if ($needsCaching) {
                // Prüfe, ob bereits ein Job für diesen Store in der Queue ist
                $existingJobs = DB::table('jobs')
                    ->where('queue', 'default')
                    ->where('payload', 'like', '%CacheInventoryImagesJob%')
                    ->where('payload', 'like', '%"storeId":' . $store->id . '%')
                    ->count();

                if ($existingJobs === 0) {
                    // Dispatch neuen Job nur wenn keiner in der Queue ist
                    CacheInventoryImagesJob::dispatch(
                        $store->id,
                        CacheInventoryImagesJob::DEFAULT_BATCH_SIZE,
                        true
                    );

                    $this->line("✓ Dispatched image caching job for store: {$store->name} (ID: {$store->id})");
                    $dispatched++;
                } else {
                    $this->line("⊙ Job already queued for store: {$store->name} (ID: {$store->id})");
                }
            }
        }

        if ($dispatched > 0) {
            $this->info("Dispatched {$dispatched} image caching job(s).");
        } else {
            $this->info('No new jobs dispatched (all up to date or already queued).');
        }

        return self::SUCCESS;
    }
}
