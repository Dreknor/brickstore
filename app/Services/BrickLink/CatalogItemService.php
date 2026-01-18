<?php

namespace App\Services\BrickLink;

use Illuminate\Support\Facades\Log;

class CatalogItemService
{
    public function __construct(
        protected BrickLinkService $brickLinkService
    ) {}

    /**
     * Get item details from BrickLink by item_no and type
     *
     * @param  \App\Models\Store  $store
     * @param  string  $itemType  Item type (PART, SET, MINIFIG, etc.)
     * @param  string  $itemNo    Item number
     * @return array|null Item details or null if not found
     */
    public function getItemDetails($store, string $itemType, string $itemNo): ?array
    {
        try {
            Log::info('Fetching BrickLink item details', [
                'item_type' => $itemType,
                'item_no' => $itemNo,
            ]);

            // Fetch from BrickLink API
            $catalogItem = $this->brickLinkService->fetchCatalogItem($store, $itemType, $itemNo);

            if (empty($catalogItem)) {
                Log::warning('Item not found on BrickLink', [
                    'item_type' => $itemType,
                    'item_no' => $itemNo,
                ]);
                return null;
            }

            // Map BrickLink data to our format
            return $this->mapCatalogItemToInventory($catalogItem);
        } catch (\Exception $e) {
            Log::error('Failed to fetch item details from BrickLink', [
                'item_type' => $itemType,
                'item_no' => $itemNo,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get item image from BrickLink
     *
     * @param  \App\Models\Store  $store
     * @param  string  $itemType
     * @param  string  $itemNo
     * @param  int|null  $colorId
     * @return string|null Image URL or null
     */
    public function getItemImage($store, string $itemType, string $itemNo, ?int $colorId = null): ?string
    {
        try {
            $imageData = $this->brickLinkService->fetchCatalogItemImage(
                $store,
                $itemType,
                $itemNo,
                $colorId ?? 0
            );

            return $imageData['thumbnail_url'] ?? $imageData['image_url'] ?? null;
        } catch (\Exception $e) {
            Log::debug('Failed to fetch item image', [
                'item_no' => $itemNo,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Get price guide suggestion for an item
     *
     * @param  \App\Models\Store  $store
     * @param  string  $itemType  Item type (PART, SET, MINIFIG, etc.)
     * @param  string  $itemNo    Item number
     * @param  string  $condition Item condition (N for new, U for used)
     * @return array Price guide data with avg_price, min_price, max_price, qty_sold
     */
    public function getPriceGuideSuggestion($store, string $itemType, string $itemNo, string $condition = 'N'): array
    {
        try {
            Log::info('Fetching price guide suggestion', [
                'item_type' => $itemType,
                'item_no' => $itemNo,
                'condition' => $condition,
            ]);

            // Fetch from BrickLink API
            $priceGuide = $this->brickLinkService->fetchPriceGuide($store, $itemType, $itemNo, $condition);

            if (empty($priceGuide)) {
                Log::warning('Price guide not found on BrickLink', [
                    'item_type' => $itemType,
                    'item_no' => $itemNo,
                    'condition' => $condition,
                ]);
                return [];
            }

            // Extract relevant price guide data
            return [
                'avg_price' => (float) ($priceGuide['avg_price'] ?? 0),
                'min_price' => (float) ($priceGuide['min_price'] ?? 0),
                'max_price' => (float) ($priceGuide['max_price'] ?? 0),
                'qty_sold' => (int) ($priceGuide['qty_sold'] ?? 0),
                'unit_quantity' => (int) ($priceGuide['unit_quantity'] ?? 1),
                'price_guide_data' => $priceGuide, // Store full response for reference
                'fetched_at' => now(),
            ];
        } catch (\Exception $e) {
            Log::error('Failed to fetch price guide from BrickLink', [
                'item_type' => $itemType,
                'item_no' => $itemNo,
                'condition' => $condition,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Map BrickLink catalog item to inventory format
     *
     * BrickLink API returns:
     * {
     *   "no": "3001",
     *   "name": "Brick 2 x 4",
     *   "type": "PART",
     *   "category_id": 1,
     *   "weight": "2.50",
     *   "dim_x": "15.8",
     *   "dim_y": "31.8",
     *   "dim_z": "9.6",
     *   "year_released": 1978,
     *   "description": "...",
     *   "is_obsolete": false
     * }
     */
    protected function mapCatalogItemToInventory(array $catalogItem): array
    {
        return [
            'item_no' => $catalogItem['no'] ?? null,
            'item_type' => $catalogItem['type'] ?? 'PART',
            'description' => $catalogItem['name'] ?? null,
            'item_name' => $catalogItem['name'] ?? null,
            'category_id' => $catalogItem['category_id'] ?? null,
            'weight' => $catalogItem['weight'] ?? null,
        ];
    }
}

