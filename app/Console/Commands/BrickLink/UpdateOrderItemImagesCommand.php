<?php

namespace App\Console\Commands\BrickLink;

use App\Models\OrderItem;
use Illuminate\Console\Command;

class UpdateOrderItemImagesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bricklink:update-item-images {--force : Force update all images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update image URLs for order items that are missing them';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Updating order item image URLs...');

        // Get items without image_url
        $query = OrderItem::query();

        if ($this->option('force')) {
            $this->warn('Force mode: Updating ALL items');
        } else {
            $query->where(function ($q) {
                $q->whereNull('image_url')
                    ->orWhere('image_url', '');
            });
        }

        $items = $query->get();

        if ($items->isEmpty()) {
            $this->info('No items need updating.');

            return self::SUCCESS;
        }

        $this->info("Found {$items->count()} items to update.");

        $bar = $this->output->createProgressBar($items->count());
        $bar->start();

        $updated = 0;
        $failed = 0;

        foreach ($items as $item) {
            try {
                $imageUrl = $this->generateBrickLinkImageUrl(
                    $item->item_type,
                    $item->item_number,
                    $item->color_id
                );

                if ($imageUrl) {
                    $item->update(['image_url' => $imageUrl]);
                    $updated++;
                } else {
                    $failed++;
                }
            } catch (\Exception $e) {
                $this->error("\nError updating item {$item->id}: {$e->getMessage()}");
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();

        $this->newLine(2);
        $this->info('Update complete!');
        $this->table(
            ['Status', 'Count'],
            [
                ['Updated', $updated],
                ['Failed', $failed],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Generate BrickLink image URL for an item
     */
    protected function generateBrickLinkImageUrl(string $itemType, string $itemNo, ?int $colorId): ?string
    {
        if (empty($itemType) || empty($itemNo)) {
            return null;
        }

        $typeMap = [
            'PART' => 'PN',
            'SET' => 'SN',
            'MINIFIG' => 'MN',
            'GEAR' => 'PN',
            'BOOK' => 'PN',
            'CATALOG' => 'PN',
            'INSTRUCTION' => 'PN',
        ];

        $imageType = $typeMap[$itemType] ?? 'PN';

        if (! $colorId) {
            return "https://img.bricklink.com/ItemImage/{$imageType}/{$itemNo}.png";
        }

        return "https://img.bricklink.com/ItemImage/{$imageType}/{$colorId}/{$itemNo}.png";
    }
}
