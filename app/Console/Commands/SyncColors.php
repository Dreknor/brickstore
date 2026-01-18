<?php

namespace App\Console\Commands;

use App\Services\BrickLink\ColorService;
use Illuminate\Console\Command;

class SyncColors extends Command
{
    protected $signature = 'colors:sync {--store= : Store ID to use for authentication}';
    protected $description = 'Sync colors from BrickLink API';

    public function __construct(private ColorService $colorService)
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('🎨 Starting color synchronization...');

        try {
            $store = null;
            if ($storeId = $this->option('store')) {
                $store = \App\Models\Store::find($storeId);
                if (!$store) {
                    $this->error("Store with ID {$storeId} not found!");
                    return 1;
                }
            }

            $result = $this->colorService->syncColorsFromBrickLink($store);

            $this->info('✅ Sync completed!');
            $this->table(
                ['Metric', 'Value'],
                [
                    ['Total Colors', $result['total']],
                    ['Created', $result['created']],
                    ['Updated', $result['updated']],
                    ['Errors', count($result['errors'])],
                ]
            );

            if (!empty($result['errors'])) {
                $this->warn('⚠️ Errors during sync:');
                foreach ($result['errors'] as $error) {
                    $this->line("  - Color ID {$error['color_id']}: {$error['error']}");
                }
            }

            // Verify data in database
            $this->info('📊 Database verification:');
            $colorCount = \App\Models\Color::count();
            $this->info("Total colors in database: {$colorCount}");

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Sync failed: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}

