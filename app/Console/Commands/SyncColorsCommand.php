<?php

namespace App\Console\Commands;

use App\Services\BrickLink\ColorService;
use Illuminate\Console\Command;

class SyncColorsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'colors:sync
                            {--force : Skip confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Sync all BrickLink colors to local database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (!$this->option('force')) {
            $this->info('This will fetch all colors from BrickLink API and store them locally.');
            if (!$this->confirm('Do you want to continue?')) {
                return self::SUCCESS;
            }
        }

        $this->info('Starting color synchronization...');

        try {
            $colorService = app(ColorService::class);
            $result = $colorService->syncColorsFromBrickLink();

            $this->newLine();
            $this->info('✅ Color sync completed successfully!');
            $this->line('');
            $this->table(['Metric', 'Value'], [
                ['Total Colors', $result['total']],
                ['Created', $result['created']],
                ['Updated', $result['updated']],
                ['Errors', count($result['errors'])],
            ]);

            if (!empty($result['errors'])) {
                $this->newLine();
                $this->error('Errors occurred:');
                foreach ($result['errors'] as $error) {
                    $this->line("  - Color {$error['color_id']}: {$error['error']}");
                }
                return self::FAILURE;
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error during color sync: {$e->getMessage()}");
            return self::FAILURE;
        }
    }
}

