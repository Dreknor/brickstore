<?php

namespace App\Services;

use App\Models\Store;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use League\Flysystem\WebDAV\WebDAVAdapter;
use Sabre\DAV\Client;
use Sabre\HTTP\ClientHttpException;

class NextcloudService
{
    protected Client $client;
    protected Store $store;
    protected string $basePath;

    /**
     * Create a new NextcloudService instance
     */
    public function __construct(Store $store)
    {
        $this->store = $store;
        $this->basePath = trim($store->nextcloud_invoice_path ?? '/Rechnungen', '/');

        // Initialize WebDAV client
        $this->initializeClient();
    }

    /**
     * Initialize WebDAV client connection
     */
    private function initializeClient(): void
    {
        try {
            $settings = [
                'baseUri' => rtrim($this->store->nextcloud_url, '/').'/remote.php/dav/files/',
                'userName' => $this->store->nextcloud_username,
                'password' => $this->store->nextcloud_password,
            ];

            $this->client = new Client($settings);
        } catch (Exception $e) {
            Log::error('Nextcloud client initialization failed', [
                'store_id' => $this->store->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to initialize Nextcloud client: '.$e->getMessage());
        }
    }

    /**
     * Test connection to Nextcloud
     */
    public function testConnection(): bool
    {
        try {
            // Test connection by checking if the user's home directory is accessible
            $userPath = $this->store->nextcloud_username;

            Log::debug('Testing Nextcloud connection', [
                'store_id' => $this->store->id,
                'url' => $this->store->nextcloud_url,
                'username' => $this->store->nextcloud_username,
                'test_path' => $userPath,
            ]);

            // propFind requires 2 arguments: path and an array of properties
            // Using empty array means get all properties
            $this->client->propFind($userPath, []);

            Log::info('Nextcloud user home directory accessible', [
                'store_id' => $this->store->id,
                'username' => $this->store->nextcloud_username,
            ]);

            // Now try to ensure the invoice directory exists
            $invoicePath = $this->generateUploadPath($this->basePath);

            if (! $this->ensureDirectoryExists($invoicePath)) {
                Log::warning('Could not ensure invoice directory exists', [
                    'store_id' => $this->store->id,
                    'invoice_path' => $invoicePath,
                ]);
                // Don't fail the test, just log it
            } else {
                Log::info('Invoice directory is ready', [
                    'store_id' => $this->store->id,
                    'invoice_path' => $invoicePath,
                ]);
            }

            return true;
        } catch (ClientHttpException $e) {
            // Handle HTTP errors specifically
            Log::warning('Nextcloud HTTP error during connection test', [
                'store_id' => $this->store->id,
                'http_code' => $e->getCode(),
                'message' => $e->getMessage(),
                'path' => $this->store->nextcloud_username,
            ]);
            return false;
        } catch (Exception $e) {
            Log::warning('Nextcloud connection test failed', [
                'store_id' => $this->store->id,
                'path' => $this->store->nextcloud_username,
                'error' => $e->getMessage(),
                'exception_type' => get_class($e),
            ]);

            return false;
        }
    }

    /**
     * Ensure directory exists, create if necessary
     * Recursively creates nested directories if needed
     */
    public function ensureDirectoryExists(string $path): bool
    {
        try {
            $fullPath = $this->store->nextcloud_username.'/'.trim($path, '/');

            // Check if directory exists
            try {
                // propFind requires 2 arguments: path and properties array
                $this->client->propFind($fullPath, []);

                Log::debug('Nextcloud directory already exists', [
                    'store_id' => $this->store->id,
                    'path' => $fullPath,
                ]);

                return true;
            } catch (ClientHttpException $e) {
                // Directory doesn't exist (404), create it
                if ($e->getCode() === 404) {
                    return $this->createDirectoryRecursive($fullPath);
                }

                // Re-throw other HTTP errors
                throw $e;
            }
        } catch (Exception $e) {
            Log::error('Failed to ensure Nextcloud directory exists', [
                'store_id' => $this->store->id,
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Recursively create directories in Nextcloud
     * Creates parent directories if needed
     */
    private function createDirectoryRecursive(string $fullPath): bool
    {
        try {
            // Split path into parts
            $parts = array_filter(explode('/', $fullPath));
            $currentPath = '';

            foreach ($parts as $part) {
                $currentPath .= '/'.$part;
                $pathToCheck = ltrim($currentPath, '/');

                try {
                    // Check if this part of the path exists
                    $this->client->propFind($pathToCheck, []);
                } catch (ClientHttpException $e) {
                    // If it's a 404, create it
                    if ($e->getCode() === 404) {
                        try {
                            // Use request() method with MKCOL HTTP method instead of mkcol()
                            $this->client->request('MKCOL', $pathToCheck);
                            Log::debug('Created Nextcloud directory', [
                                'store_id' => $this->store->id,
                                'path' => $pathToCheck,
                            ]);
                        } catch (Exception $createError) {
                            Log::error('Failed to create directory', [
                                'store_id' => $this->store->id,
                                'path' => $pathToCheck,
                                'error' => $createError->getMessage(),
                            ]);
                            return false;
                        }
                    } else {
                        // Other HTTP errors
                        throw $e;
                    }
                }
            }

            Log::info('Nextcloud directory structure created', [
                'store_id' => $this->store->id,
                'path' => $fullPath,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to create directory recursively', [
                'store_id' => $this->store->id,
                'path' => $fullPath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Generate upload path with placeholders
     * Supports: {year}, {month}, {day}, {store_name}
     */
    public function generateUploadPath(string $templatePath = ''): string
    {
        $path = $templatePath ?: $this->basePath;

        $replacements = [
            '{year}' => now()->year,
            '{month}' => now()->format('m'),
            '{day}' => now()->format('d'),
            '{store_name}' => Str::slug($this->store->name),
        ];

        foreach ($replacements as $placeholder => $value) {
            $path = str_replace($placeholder, $value, $path);
        }

        return $path;
    }

    /**
     * Upload invoice PDF to Nextcloud
     */
    public function uploadInvoicePDF(string $localFilePath, string $remotePath): string
    {
        try {
            // Verify local file exists
            if (! file_exists($localFilePath)) {
                throw new Exception("Local file not found: {$localFilePath}");
            }

            // Generate remote path if needed
            $uploadPath = $this->generateUploadPath($remotePath);

            // Ensure remote directory exists
            if (! $this->ensureDirectoryExists($uploadPath)) {
                throw new Exception("Failed to create remote directory: {$uploadPath}");
            }

            // Get filename
            $filename = basename($localFilePath);
            $fullRemotePath = $this->store->nextcloud_username.'/'.trim($uploadPath, '/').'/'.basename($filename);

            // Read file content
            $fileContent = file_get_contents($localFilePath);

            // Upload file
            $this->client->request('PUT', $fullRemotePath, $fileContent);

            Log::info('Invoice uploaded to Nextcloud', [
                'store_id' => $this->store->id,
                'local_path' => $localFilePath,
                'remote_path' => $fullRemotePath,
            ]);

            // Return the path for storage in database
            return trim($uploadPath, '/').'/'.basename($filename);
        } catch (Exception $e) {
            Log::error('Failed to upload invoice to Nextcloud', [
                'store_id' => $this->store->id,
                'local_path' => $localFilePath,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Upload to Nextcloud failed: '.$e->getMessage());
        }
    }

    /**
     * Delete file from Nextcloud
     */
    public function deleteFile(string $remotePath): bool
    {
        try {
            $fullPath = $this->store->nextcloud_username.'/'.trim($remotePath, '/');

            $this->client->request('DELETE', $fullPath);

            Log::info('File deleted from Nextcloud', [
                'store_id' => $this->store->id,
                'remote_path' => $fullPath,
            ]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete file from Nextcloud', [
                'store_id' => $this->store->id,
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get file from Nextcloud
     */
    public function getFile(string $remotePath): string
    {
        try {
            $fullPath = $this->store->nextcloud_username.'/'.trim($remotePath, '/');

            $response = $this->client->request('GET', $fullPath);

            return $response['body'];
        } catch (Exception $e) {
            Log::error('Failed to get file from Nextcloud', [
                'store_id' => $this->store->id,
                'remote_path' => $remotePath,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Failed to retrieve file from Nextcloud: '.$e->getMessage());
        }
    }

    /**
     * Get store credentials - for validation
     */
    public function hasCredentials(): bool
    {
        return ! empty($this->store->nextcloud_url)
            && ! empty($this->store->nextcloud_username)
            && ! empty($this->store->nextcloud_password);
    }
}

