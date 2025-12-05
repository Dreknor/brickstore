<?php

namespace App\Console\Commands\BrickLink;

use App\Models\OrderItem;
use App\Services\BrickLink\ImageCacheService;
use Illuminate\Console\Command;

class CacheImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bricklink:cache-images {--force : Force re-cache all images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cache all BrickLink item images locally';

    /**
     * Execute the console command.
     */
    public function handle(ImageCacheService $cacheService): int
    {
        $this->info('Starting image caching...');

        // Get all order items with images
        $items = OrderItem::whereNotNull('image_url')
            ->where('image_url', '!=', '')
            ->get();

        if ($items->isEmpty()) {
            $this->warn('No items with images found.');

            return self::SUCCESS;
        }

        $this->info("Found {$items->count()} items with images.");

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $cached = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $cachedUrl = $item->cacheImage();

                if ($cachedUrl) {
                    $cached++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("\nError caching image for {$item->item_number}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);
        $this->info('Image caching complete!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Cached', $cached],
                ['Failed', $failed],
            ]
        );

        // Display cache statistics
        $stats = $cacheService->getCacheStats();
        $this->info("Cache contains {$stats['count']} images ({$stats['size_mb']} MB)");

        return self::SUCCESS;
    }
}
