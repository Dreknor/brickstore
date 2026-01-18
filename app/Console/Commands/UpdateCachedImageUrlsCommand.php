<?php

namespace App\Console\Commands;

use App\Models\Inventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class UpdateCachedImageUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'inventory:update-cached-image-urls
                            {--dry-run : Run without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Update inventory items to use cached image URLs from storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('Running in DRY RUN mode - no changes will be made');
        }

        // Get all cached image files
        $cachedFiles = Storage::disk('public')->files('bricklink-images');
        $this->info('Found ' . count($cachedFiles) . ' cached images in storage');

        // Create a map of cached files
        $cachedFileMap = [];
        foreach ($cachedFiles as $file) {
            $filename = basename($file);
            $cachedFileMap[$filename] = asset('storage/' . $file);
        }

        // Get all inventory items with external image URLs
        $inventories = Inventory::whereNotNull('image_url')
            ->where('image_url', 'NOT LIKE', '%/storage/%')
            ->where('image_url', '!=', '')
            ->get();

        $this->info('Found ' . $inventories->count() . ' inventory items with external URLs');

        $updated = 0;
        $notFound = 0;

        $progressBar = $this->output->createProgressBar($inventories->count());
        $progressBar->start();

        foreach ($inventories as $inventory) {
            // Generate expected filename
            $colorPart = $inventory->color_id ? "_{$inventory->color_id}" : '';
            $sanitizedNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $inventory->item_no);
            $itemType = strtolower($inventory->item_type);

            // Try different extensions
            $found = false;
            foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $ext) {
                $expectedFilename = "{$itemType}_{$sanitizedNumber}{$colorPart}.{$ext}";

                if (isset($cachedFileMap[$expectedFilename])) {
                    if (!$dryRun) {
                        $inventory->update(['image_url' => $cachedFileMap[$expectedFilename]]);
                    }
                    $updated++;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $notFound++;
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info('Updated: ' . $updated);
        $this->info('Not found in cache: ' . $notFound);

        if ($dryRun) {
            $this->warn('DRY RUN - No changes were made. Run without --dry-run to apply changes.');
        } else {
            $this->info('Successfully updated cached image URLs!');
        }

        return self::SUCCESS;
    }
}
