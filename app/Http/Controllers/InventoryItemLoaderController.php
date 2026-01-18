<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\BrickLink\BrickLinkService;
use App\Services\BrickLink\CatalogItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InventoryItemLoaderController extends Controller
{
    public function __construct(
        protected CatalogItemService $catalogItemService,
        protected BrickLinkService $brickLinkService
    ) {}

    /**
     * Load item details from BrickLink by item_no and type
     *
     * GET /api/inventory/load-item
     * Query params: item_type, item_no, color_id (optional)
     */
    public function loadItem(Request $request)
    {
        $validated = $request->validate([
            'item_type' => 'required|string|in:PART,SET,MINIFIG,BOOK,GEAR,CATALOG,INSTRUCTION,UNSORTED_LOT,ORIGINAL_BOX',
            'item_no' => 'required|string|max:100',
            'color_id' => 'nullable|integer',
            'new_or_used' => 'nullable|in:N,U',
        ]);

        $store = auth()->user()->store;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'Kein Store konfiguriert',
            ], 400);
        }

        $colorId = $validated['color_id'] ?? null;
        $newOrUsed = $validated['new_or_used'] ?? 'N';

        // Fetch item details from BrickLink
        $itemDetails = $this->catalogItemService->getItemDetails(
            $store,
            $validated['item_type'],
            $validated['item_no']
        );

        if (!$itemDetails) {
            return response()->json([
                'success' => false,
                'message' => 'Artikel nicht auf BrickLink gefunden',
            ], 404);
        }

        // Get image if available (mit Farbe wenn angegeben)
        $imageUrl = null;
        $thumbnailUrl = null;

        try {
            $imageData = $this->brickLinkService->fetchCatalogItemImage(
                $store,
                $validated['item_type'],
                $validated['item_no'],
                $colorId ?? 0
            );
            $imageUrl = $imageData['image_url'] ?? null;
            $thumbnailUrl = $imageData['thumbnail_url'] ?? null;
        } catch (\Exception $e) {
            Log::debug('Could not fetch image', ['error' => $e->getMessage()]);
        }

        // Price Guide holen (wenn Farbe angegeben)
        $priceGuide = null;
        if ($colorId) {
            try {
                $priceGuide = $this->catalogItemService->getPriceGuideSuggestion(
                    $store,
                    $validated['item_type'],
                    $validated['item_no'],
                    $newOrUsed,
                    $colorId
                );
            } catch (\Exception $e) {
                Log::debug('Could not fetch price guide', ['error' => $e->getMessage()]);
            }
        }

        // Prüfe ob bereits im Inventar (Duplikatsprüfung)
        $existingItems = [];
        if ($colorId) {
            $existingItems = Inventory::where('store_id', $store->id)
                ->where('item_no', $validated['item_no'])
                ->where('item_type', $validated['item_type'])
                ->where('color_id', $colorId)
                ->get()
                ->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'inventory_id' => $item->inventory_id,
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'new_or_used' => $item->new_or_used,
                        'remarks' => $item->remarks,
                        'color_name' => $item->color_name,
                    ];
                })
                ->toArray();
        }

        return response()->json([
            'success' => true,
            'data' => array_merge($itemDetails, [
                'image_url' => $imageUrl,
                'thumbnail_url' => $thumbnailUrl,
            ]),
            'price_guide' => $priceGuide,
            'existing_items' => $existingItems,
            'has_duplicates' => count($existingItems) > 0,
        ]);
    }
}

