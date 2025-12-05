<?php

namespace App\Services\BrickLink;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ImageCacheService
{
    /**
     * Cache directory for BrickLink images.
     */
    protected string $cacheDirectory = 'bricklink-images';

    /**
     * Download and cache an image from a URL.
     */
    public function cacheImage(string $imageUrl, string $itemType, string $itemNumber, ?string $colorId = null): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        // Generate filename based on item details
        $filename = $this->generateFilename($itemType, $itemNumber, $colorId, $imageUrl);
        $path = "{$this->cacheDirectory}/{$filename}";

        // Check if image already exists in cache
        if (Storage::disk('public')->exists($path)) {
            return asset('storage/'.$path);
        }

        try {
            // Download image
            $response = Http::timeout(10)->get($imageUrl);

            if ($response->successful()) {
                // Store image
                Storage::disk('public')->put($path, $response->body());

                Log::info('BrickLink image cached', [
                    'url' => $imageUrl,
                    'path' => $path,
                ]);

                return asset('storage/'.$path);
            }

            Log::warning('Failed to download BrickLink image', [
                'url' => $imageUrl,
                'status' => $response->status(),
            ]);

            return null; // Return null instead of original URL
        } catch (\Exception $e) {
            Log::error('Error caching BrickLink image', [
                'url' => $imageUrl,
                'error' => $e->getMessage(),
            ]);

            return null; // Return null instead of original URL
        }
    }

    /**
     * Generate a unique filename for the cached image.
     */
    protected function generateFilename(string $itemType, string $itemNumber, ?string $colorId, string $url): string
    {
        $extension = $this->getExtensionFromUrl($url);
        $colorPart = $colorId ? "_{$colorId}" : '';

        // Sanitize item number (remove invalid filename characters)
        $sanitizedNumber = preg_replace('/[^a-zA-Z0-9_-]/', '_', $itemNumber);

        return strtolower("{$itemType}_{$sanitizedNumber}{$colorPart}.{$extension}");
    }

    /**
     * Get file extension from URL.
     */
    protected function getExtensionFromUrl(string $url): string
    {
        $path = parse_url($url, PHP_URL_PATH);
        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return in_array(strtolower($extension), ['jpg', 'jpeg', 'png', 'gif', 'webp'])
            ? strtolower($extension)
            : 'jpg';
    }

    /**
     * Delete cached image.
     */
    public function deleteCachedImage(string $itemType, string $itemNumber, ?string $colorId = null): bool
    {
        $pattern = "{$this->cacheDirectory}/{$itemType}_{$itemNumber}";
        if ($colorId) {
            $pattern .= "_{$colorId}";
        }
        $pattern .= '.*';

        $files = Storage::disk('public')->files($this->cacheDirectory);

        foreach ($files as $file) {
            if (fnmatch($pattern, $file)) {
                Storage::disk('public')->delete($file);

                return true;
            }
        }

        return false;
    }

    /**
     * Clear all cached images.
     */
    public function clearCache(): int
    {
        $files = Storage::disk('public')->files($this->cacheDirectory);

        foreach ($files as $file) {
            Storage::disk('public')->delete($file);
        }

        return count($files);
    }

    /**
     * Get cache statistics.
     */
    public function getCacheStats(): array
    {
        $files = Storage::disk('public')->files($this->cacheDirectory);
        $totalSize = 0;

        foreach ($files as $file) {
            $totalSize += Storage::disk('public')->size($file);
        }

        return [
            'count' => count($files),
            'size' => $totalSize,
            'size_mb' => round($totalSize / 1024 / 1024, 2),
        ];
    }
}
