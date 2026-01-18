<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Inventory;

echo "=== Inventory Images Test ===\n\n";

$totalInventories = Inventory::count();
echo "Total inventories: {$totalInventories}\n";

$withImages = Inventory::whereNotNull('image_url')->where('image_url', '!=', '')->count();
echo "With image URLs: {$withImages}\n";

$cachedImages = Inventory::whereNotNull('image_url')
    ->where('image_url', 'LIKE', '%/storage/%')
    ->count();
echo "With cached images (local storage): {$cachedImages}\n";

$externalImages = Inventory::whereNotNull('image_url')
    ->where('image_url', 'NOT LIKE', '%/storage/%')
    ->where('image_url', '!=', '')
    ->count();
echo "With external images (BrickLink): {$externalImages}\n";

echo "\n=== Sample External URLs ===\n";
$samples = Inventory::whereNotNull('image_url')
    ->where('image_url', 'NOT LIKE', '%/storage/%')
    ->where('image_url', '!=', '')
    ->limit(5)
    ->get(['item_no', 'item_type', 'color_id', 'image_url']);

foreach ($samples as $sample) {
    echo "{$sample->item_type}/{$sample->item_no} (Color: {$sample->color_id}): {$sample->image_url}\n";
}

echo "\n=== Sample Cached URLs ===\n";
$cachedSamples = Inventory::whereNotNull('image_url')
    ->where('image_url', 'LIKE', '%/storage/%')
    ->limit(5)
    ->get(['item_no', 'item_type', 'color_id', 'image_url']);

foreach ($cachedSamples as $sample) {
    echo "{$sample->item_type}/{$sample->item_no} (Color: {$sample->color_id}): {$sample->image_url}\n";
}
