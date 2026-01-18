<?php

namespace App\Console\Commands;

use App\Services\BrickognizeService;
use Illuminate\Console\Command;

class TestBrickognizeConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'brickognize:test {image_path?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Brickognize API connection and configuration';

    /**
     * Execute the console command.
     */
    public function handle(BrickognizeService $brickognizeService)
    {
        $this->info('🧪 Brickognize API Connection Test');
        $this->line('');

        // 1. Konfiguration überprüfen
        $this->info('📋 Configuration:');
        $apiUrl = config('services.brickognize.api_url');
        $timeout = config('services.brickognize.timeout');

        $this->line("  API URL: {$apiUrl}");
        $this->line("  Timeout: {$timeout}s");
        $this->line('');

        // 2. Verbindungstest
        $this->info('🔗 Testing Connection...');
        try {
            $result = $brickognizeService->testConnection();

            if ($result['success']) {
                $this->components->task($result['message']);
                $this->line('');
            } else {
                $this->error('  ✗ ' . $result['message']);
                $this->line('');
                $this->warn('⚠ Troubleshooting:');
                $this->line('  1. Überprüfe die BRICKOGNIZE_API_URL in .env');
                $this->line('  2. Teste die Verbindung mit: curl -v ' . $apiUrl);
                $this->line('  3. Prüfe die Firewall/Proxy-Einstellungen');
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Fehler beim Verbindungstest: ' . $e->getMessage());
            return 1;
        }

        // 3. Endpoint-Test (wenn Bild vorhanden)
        $imagePath = $this->argument('image_path');

        if ($imagePath) {
            if (!file_exists($imagePath)) {
                $this->error("Image file not found: {$imagePath}");
                return 1;
            }

            $this->info('🖼  Testing Image Identification...');

            try {
                $result = $brickognizeService->identify($imagePath);

                if ($result['success']) {
                    $this->components->task('Image identification successful!');
                    $this->line('');

                    $data = $result['data'];
                    $this->info('📦 Identified Items:');

                    if (isset($data['items']) && !empty($data['items'])) {
                        $headers = ['Item No.', 'Name', 'Confidence', 'Color'];
                        $rows = [];

                        foreach ($data['items'] as $item) {
                            $rows[] = [
                                $item['item_no'] ?? 'N/A',
                                substr($item['name'] ?? 'Unknown', 0, 30),
                                ($item['confidence'] ?? 0) . '%',
                                $item['color_name'] ?? 'Unknown',
                            ];
                        }

                        $this->table($headers, $rows);
                    } else {
                        $this->warn('  No items identified');
                    }

                    return 0;
                } else {
                    $this->error('  ✗ ' . ($result['error'] ?? 'Unknown error'));
                    $this->line('');
                    $this->warn('⚠ Debug Information:');
                    $this->line('  - Check storage/logs/laravel.log for details');
                    $this->line('  - Run: tail -f storage/logs/laravel.log | grep -i brickognize');
                    return 1;
                }
            } catch (\Exception $e) {
                $this->error('Fehler bei der Identifikation: ' . $e->getMessage());
                return 1;
            }
        } else {
            $this->info('💡 Tips:');
            $this->line('  To test image identification, provide an image path:');
            $this->line('  php artisan brickognize:test /path/to/image.jpg');
            $this->line('');
            $this->info('✅ Connection test passed! Configuration looks good.');
            return 0;
        }
    }
}

