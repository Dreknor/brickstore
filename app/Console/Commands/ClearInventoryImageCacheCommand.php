<?php

namespace App\Console\Commands;

use App\Services\BrickLink\ImageCacheService;
use Illuminate\Console\Command;

class ClearInventoryImageCacheCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:clear-image-cache
                            {--stats : Show cache statistics before clearing}';

    /**
     * The console command description.
     */
    protected $description = 'Clear the cached inventory images';

    /**
     * Execute the console command.
     */
    public function handle(ImageCacheService $imageCacheService): int
    {
        if ($this->option('stats')) {
            $stats = $imageCacheService->getCacheStats();
            $this->info("Current cache statistics:");
            $this->line("- Files: {$stats['count']}");
            $this->line("- Size: {$stats['size_mb']} MB");
            $this->newLine();
        }

        if (!$this->confirm('Are you sure you want to clear all cached inventory images?', false)) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        $this->info('Clearing image cache...');

        $deletedCount = $imageCacheService->clearCache();

        $this->info("Successfully cleared {$deletedCount} cached image(s).");

        \App\Services\ActivityLogger::info(
            'inventory.image-cache.cleared',
            "Inventory image cache cleared: {$deletedCount} files",
            null,
            ['deleted_count' => $deletedCount]
        );

        return self::SUCCESS;
    }
}

