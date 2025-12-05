<?php

namespace App\Console\Commands;

use App\Models\Store;
use Illuminate\Console\Command;

use function Laravel\Prompts\password;
use function Laravel\Prompts\select;
use function Laravel\Prompts\text;

class UpdateBrickLinkCredentials extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bricklink:update-credentials {--store-id=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update BrickLink API credentials for a store';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeId = $this->option('store-id');

        // Get store
        if ($storeId) {
            $store = Store::find($storeId);
        } else {
            $stores = Store::all();

            if ($stores->isEmpty()) {
                $this->error('No stores found!');

                return self::FAILURE;
            }

            if ($stores->count() === 1) {
                $store = $stores->first();
            } else {
                $storeId = select(
                    label: 'Select a store',
                    options: $stores->pluck('name', 'id')->toArray()
                );
                $store = Store::find($storeId);
            }
        }

        if (! $store) {
            $this->error('Store not found!');

            return self::FAILURE;
        }

        $this->info("Updating BrickLink credentials for store: {$store->name}");
        $this->newLine();

        // Show current credentials (masked)
        $this->info('Current credentials:');
        $this->line('Consumer Key: '.($store->bl_consumer_key ? str_repeat('•', 20).' (SET)' : 'NOT SET'));
        $this->line('Consumer Secret: '.($store->bl_consumer_secret ? str_repeat('•', 20).' (SET)' : 'NOT SET'));
        $this->line('Token: '.($store->bl_token ? str_repeat('•', 20).' (SET)' : 'NOT SET'));
        $this->line('Token Secret: '.($store->bl_token_secret ? str_repeat('•', 20).' (SET)' : 'NOT SET'));
        $this->newLine();

        $this->warn('Please enter the new BrickLink API credentials.');
        $this->line('You can find these at: https://www.bricklink.com/v2/api/register_consumer.page');
        $this->newLine();

        // Get new credentials
        $consumerKey = text(
            label: 'Consumer Key',
            required: true,
            hint: 'The Consumer Key from your BrickLink API registration'
        );

        $consumerSecret = password(
            label: 'Consumer Secret',
            required: true,
            hint: 'The Consumer Secret from your BrickLink API registration'
        );

        $token = text(
            label: 'Token Value',
            required: true,
            hint: 'The Token Value from your BrickLink API registration'
        );

        $tokenSecret = password(
            label: 'Token Secret',
            required: true,
            hint: 'The Token Secret from your BrickLink API registration'
        );

        // Update credentials
        $store->bl_consumer_key = $consumerKey;
        $store->bl_consumer_secret = $consumerSecret;
        $store->bl_token = $token;
        $store->bl_token_secret = $tokenSecret;
        $store->save();

        $this->newLine();
        $this->info('✓ BrickLink credentials updated successfully!');
        $this->newLine();

        // Test connection
        if ($this->confirm('Would you like to test the connection now?', true)) {
            $this->call('bricklink:test-connection', [
                '--store-id' => $store->id,
            ]);
        }

        return self::SUCCESS;
    }
}
