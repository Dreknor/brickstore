<?php

/**
 * This script updates the database to use cached image URLs that are already in storage
 */

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Inventory;
use Illuminate\Support\Facades\Storage;

echo "=== Update Cached Image URLs ===\n\n";

// Get all cached image files
$cachedFiles = Storage::disk('public')->files('bricklink-images');
echo "Gefundene gecachte Bilder im Storage: " . count($cachedFiles) . "\n";

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

echo "Inventar-Items mit externen URLs: " . $inventories->count() . "\n\n";

$updated = 0;
$notFound = 0;

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
            $inventory->update(['image_url' => $cachedFileMap[$expectedFilename]]);
            echo ".";
            $updated++;
            $found = true;
            break;
        }
    }

    if (!$found) {
        $notFound++;
    }

    // Flush every 50 items
    if (($updated + $notFound) % 50 === 0) {
        echo " [" . ($updated + $notFound) . "]\n";
    }
}

echo "\n\n=== Ergebnis ===\n";
echo "Aktualisiert: " . $updated . "\n";
echo "Nicht im Cache gefunden: " . $notFound . "\n";
echo "\nFertig!\n";
