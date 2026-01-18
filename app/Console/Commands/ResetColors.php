<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ResetColors extends Command
{
    protected $signature = 'colors:reset';
    protected $description = 'Reset colors table by dropping and recreating it';

    public function handle()
    {
        $this->warn('⚠️  This will delete all colors from the database!');

        if (!$this->confirm('Do you want to continue?')) {
            $this->info('Cancelled.');
            return 0;
        }

        try {
            $this->info('Dropping colors table...');
            Schema::dropIfExists('colors');

            $this->info('Running migration for colors table...');
            $this->call('migrate', ['--path' => 'database/migrations/2025_12_06_000000_create_colors_table.php']);

            $this->info('✅ Colors table reset successfully!');

            $this->info('Now run: php artisan colors:sync');

            return 0;
        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
            return 1;
        }
    }
}

