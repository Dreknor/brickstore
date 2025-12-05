<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\NextcloudService;
use Illuminate\Console\Command;

class TestNextcloudConnection extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'nextcloud:test-connection {store-id? : The Store ID to test}';

    /**
     * The console command description.
     */
    protected $description = 'Test Nextcloud connection for a store';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeId = $this->argument('store-id');

        if ($storeId) {
            $stores = Store::where('id', $storeId)->get();
            if ($stores->isEmpty()) {
                $this->error("Store with ID {$storeId} not found");

                return 1;
            }
        } else {
            $stores = Store::whereNotNull('nextcloud_url')->get();

            if ($stores->isEmpty()) {
                $this->info('No stores with Nextcloud configured found');

                return 0;
            }
        }

        foreach ($stores as $store) {
            $this->info("\n");
            $this->info("Testing Nextcloud connection for Store: {$store->name}");
            $this->info("─".str_repeat('─', strlen("Testing Nextcloud connection for Store: {$store->name}")));

            if (! $store->nextcloud_url) {
                $this->warn('  Nextcloud URL not configured');

                continue;
            }

            try {
                $nextcloud = new NextcloudService($store);

                if ($nextcloud->testConnection()) {
                    $this->info('  ✓ Connection successful');

                    // Try to create test directory
                    $testPath = '/Test_'.now()->format('Y-m-d_H-i-s');
                    if ($nextcloud->ensureDirectoryExists($testPath)) {
                        $this->info('  ✓ Directory creation successful');

                        // Clean up
                        try {
                            $nextcloud->deleteFile($testPath);
                            $this->info('  ✓ Cleanup successful');
                        } catch (\Exception $e) {
                            $this->warn('  ⚠ Could not clean up test directory: '.$e->getMessage());
                        }
                    } else {
                        $this->error('  ✗ Directory creation failed');
                    }
                } else {
                    $this->error('  ✗ Connection failed');
                }
            } catch (\Exception $e) {
                $this->error('  ✗ Error: '.$e->getMessage());
            }
        }

        return 0;
    }
}

