<?php

namespace App\Http\Controllers;

use App\Services\BrickLink\BrickLinkService;
use App\Services\BrickLink\CatalogItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrickLinkApiController extends Controller
{
    public function __construct(
        protected BrickLinkService $brickLinkService,
        protected CatalogItemService $catalogItemService
    ) {}

    /**
     * Get item information from BrickLink
     */
    public function getItemInfo(Request $request)
    {
        $validated = $request->validate([
            'item_no' => 'required|string',
            'item_type' => 'required|string',
            'color_id' => 'required|integer',
        ]);

        $store = auth()->user()->store;

        try {
            // Hole Item-Daten
            $item = $this->catalogItemService->getItemDetails(
                $store,
                $validated['item_type'],
                $validated['item_no']
            );

            // Hole Bild-URL für die spezifische Farbe
            $imageData = $this->brickLinkService->fetchCatalogItemImage(
                $store,
                $validated['item_type'],
                $validated['item_no'],
                $validated['color_id']
            );

            return response()->json([
                'success' => true,
                'item_no' => $item['no'] ?? $validated['item_no'],
                'item_type' => $item['type'] ?? $validated['item_type'],
                'name' => $item['name'] ?? null,
                'category_name' => $item['category_name'] ?? null,
                'category_id' => $item['category_id'] ?? null,
                'image_url' => $imageData['image_url'] ?? null,
                'thumbnail_url' => $imageData['thumbnail_url'] ?? null,
                'weight' => $item['weight'] ?? null,
                'dim_x' => $item['dim_x'] ?? null,
                'dim_y' => $item['dim_y'] ?? null,
                'dim_z' => $item['dim_z'] ?? null,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch item info from BrickLink', [
                'item_no' => $validated['item_no'],
                'item_type' => $validated['item_type'],
                'color_id' => $validated['color_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Item nicht gefunden oder BrickLink-Fehler: ' . $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Get price guide from BrickLink
     */
    public function getPriceGuide(Request $request)
    {
        $validated = $request->validate([
            'item_no' => 'required|string',
            'item_type' => 'required|string',
            'color_id' => 'nullable|integer',
            'new_or_used' => 'required|in:N,U',
        ]);

        $store = auth()->user()->store;

        try {
            $priceGuide = $this->catalogItemService->getPriceGuideSuggestion(
                $store,
                $validated['item_type'],
                $validated['item_no'],
                $validated['new_or_used'],
                $validated['color_id'] ?? null
            );

            if (empty($priceGuide)) {
                return response()->json([
                    'success' => false,
                    'error' => 'Keine Preisempfehlungen verfügbar',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'avg_price' => $priceGuide['avg_price'] ?? null,
                'min_price' => $priceGuide['min_price'] ?? null,
                'max_price' => $priceGuide['max_price'] ?? null,
                'qty_sold' => $priceGuide['qty_sold'] ?? null,
                'new_or_used' => $validated['new_or_used'],
                'color_id' => $validated['color_id'] ?? null,
                'fetched_at' => now()->toIso8601String(),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to fetch price guide from BrickLink', [
                'item_no' => $validated['item_no'],
                'item_type' => $validated['item_type'],
                'color_id' => $validated['color_id'] ?? null,
                'new_or_used' => $validated['new_or_used'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Preisempfehlungen konnten nicht geladen werden: ' . $e->getMessage(),
            ], 500);
        }
    }
}

