<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\InventoryService;
use App\Services\BrickLink\CatalogItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService,
        protected CatalogItemService $catalogItemService
    ) {}

    /**
     * Display a listing of the inventory
     */
    public function index(Request $request)
    {
        $store = auth()->user()->store;

        if (!$store) {
            return redirect()->route('store.setup-wizard');
        }

        Gate::authorize('view', $store);

        $query = Inventory::where('store_id', $store->id);

        // Filters
        if ($request->filled('item_type')) {
            $query->where('item_type', $request->item_type);
        }

        if ($request->filled('condition')) {
            $condition = strtoupper($request->condition) === 'NEW' ? 'N' : 'U';
            $query->where('new_or_used', $condition);
        }

        if ($request->filled('stock_room')) {
            if ($request->stock_room === 'yes') {
                $query->where('is_stock_room', true);
            } elseif ($request->stock_room === 'no') {
                $query->where('is_stock_room', false);
            }
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('item_no', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('color_name', 'like', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_direction', 'desc');

        $allowedSorts = ['item_no', 'item_type', 'quantity', 'unit_price', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'created_at';
        }

        $query->orderBy($sortBy, $sortDirection);

        $inventories = $query->paginate(50)->appends($request->query());

        return view('inventory.index', compact('inventories', 'store'));
    }

    /**
     * Show the form for creating a new inventory item
     */
    public function create(Request $request)
    {
        $store = auth()->user()->store;

        if (!$store) {
            return redirect()->route('store.setup-wizard');
        }

        Gate::authorize('update', $store);

        // Hole Vorausfüllung aus Query-Parametern (von Brickognize)
        $prefilledData = [
            'item_no' => $request->query('item_no'),
            'item_type' => $request->query('item_type', 'PART'),
            'color_id' => $request->query('color_id'),
            'color_name' => $request->query('color_name'),
            'item_name' => $request->query('item_name'),
            'identification_id' => $request->query('identification_id'),
        ];

        // Hole alle verfügbaren Farben von BrickLink
        $colors = \App\Models\Color::orderBy('color_type')->orderBy('color_name')->get();

        return view('inventory.create', compact('store', 'prefilledData', 'colors'));
    }

    /**
     * Store a newly created inventory item
     */
    public function store(Request $request)
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        $validated = $request->validate([
            'item_no' => 'required|string|max:255',
            'item_type' => 'required|in:PART,SET,MINIFIG,BOOK,GEAR,CATALOG,INSTRUCTION,UNSORTED_LOT,ORIGINAL_BOX',
            'color_id' => 'nullable|integer',
            'quantity' => 'required|integer|min:0',
            'new_or_used' => 'required|in:N,U',
            'completeness' => 'nullable|in:C,B,S', // Only when item_type is SET
            'unit_price' => 'required|numeric|min:0|regex:/^\d+(\.\d{1,3})?$/',
            'description' => 'nullable|string',
            'remarks' => 'required|string|max:255',
            'bulk' => 'nullable|integer|min:1',
            'is_retain' => 'boolean',
            'is_stock_room' => 'boolean',
            'stock_room_id' => 'nullable|string', // Only when is_stock_room is true
            'sale_rate' => 'nullable|numeric|min:0|max:100',
            'my_cost' => 'nullable|numeric|min:0',
            'tier_quantity1' => 'nullable|integer|min:1',
            'tier_price1' => 'nullable|numeric|min:0',
            'tier_quantity2' => 'nullable|integer|min:1',
            'tier_price2' => 'nullable|numeric|min:0',
            'tier_quantity3' => 'nullable|integer|min:1',
            'tier_price3' => 'nullable|numeric|min:0',
            'my_weight' => 'nullable|numeric|min:0',
        ]);

        // Checkboxes: Explicitly set to false if not present in request
        $validated['is_retain'] = $request->boolean('is_retain');
        $validated['is_stock_room'] = $request->boolean('is_stock_room');

        // Ensure remarks is always set (even if empty)
        if (!isset($validated['remarks'])) {
            $validated['remarks'] = '';
        }

        // Ensure description is always set (even if empty)
        if (!isset($validated['description'])) {
            $validated['description'] = '';
        }

        // Additional validation: completeness only for SETs
        if ($validated['item_type'] !== 'SET') {
            unset($validated['completeness']);
        }

        // Additional validation: stock_room_id only when is_stock_room is true
        if (!$validated['is_stock_room']) {
            unset($validated['stock_room_id']);
        }

        try {
            // Check if identical item already exists
            $existingItem = Inventory::where('store_id', $store->id)
                ->where('item_no', $validated['item_no'])
                ->where('item_type', $validated['item_type'])
                ->where('color_id', $validated['color_id'])
                ->where('new_or_used', $validated['new_or_used'])
                ->where('unit_price', $validated['unit_price'])
                ->first();

            if ($existingItem) {
                // Item exists - increase quantity instead of creating new one
                $oldQuantity = $existingItem->quantity;
                $newQuantity = $oldQuantity + $validated['quantity'];

                // Update quantity in BrickLink and locally
                $updateData = ['quantity' => $newQuantity];

                // Also update remarks to combine information
                if (!empty($validated['remarks'])) {
                    $updateData['remarks'] = trim($existingItem->remarks . ' | ' . $validated['remarks']);
                }

                $inventory = $this->inventoryService->updateInventoryInBrickLink(
                    $store,
                    $existingItem,
                    $updateData
                );

                \Illuminate\Support\Facades\Log::info('Inventory quantity increased for existing item', [
                    'inventory_id' => $inventory->inventory_id,
                    'item_no' => $inventory->item_no,
                    'old_quantity' => $oldQuantity,
                    'added_quantity' => $validated['quantity'],
                    'new_quantity' => $newQuantity,
                ]);

                return redirect()
                    ->route('inventory.show', $inventory)
                    ->with('success', "✅ Menge von bestehendem Artikel erhöht: {$oldQuantity} + {$validated['quantity']} = {$newQuantity}");
            }

            // No duplicate found - create new item
            $inventory = $this->inventoryService->createInventoryInBrickLink($store, $validated);

            return redirect()
                ->route('inventory.show', $inventory)
                ->with('success', '✅ Artikel erfolgreich erstellt und zu BrickLink synchronisiert');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Inventory creation error', [
                'error' => $e->getMessage(),
                'item_no' => $validated['item_no'] ?? null,
            ]);

            return back()
                ->withInput()
                ->with('error', '❌ Fehler beim Synchronisieren mit BrickLink: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified inventory item
     */
    public function show(Inventory $inventory)
    {
        Gate::authorize('view', $inventory->store);

        return view('inventory.show', compact('inventory'));
    }

    /**
     * Show the form for editing the specified inventory item
     */
    public function edit(Inventory $inventory)
    {
        Gate::authorize('update', $inventory->store);

        return view('inventory.edit', compact('inventory'));
    }

    /**
     * Update the specified inventory item
     */
    public function update(Request $request, Inventory $inventory)
    {
        Gate::authorize('update', $inventory->store);

        $validated = $request->validate([
            'quantity' => 'sometimes|integer|min:0',
            'unit_price' => 'sometimes|numeric|min:0|regex:/^\d+(\.\d{1,3})?$/',
            'new_or_used' => 'sometimes|in:N,U',
            'completeness' => 'nullable|in:C,B,S', // Only when item_type is SET
            'description' => 'nullable|string',
            'remarks' => 'nullable|string',
            'bulk' => 'nullable|integer|min:1',
            'is_retain' => 'boolean',
            'is_stock_room' => 'boolean',
            'stock_room_id' => 'nullable|string', // Only when is_stock_room is true
            'sale_rate' => 'nullable|numeric|min:0|max:100',
            'my_cost' => 'nullable|numeric|min:0',
            'tier_quantity1' => 'nullable|integer|min:1',
            'tier_price1' => 'nullable|numeric|min:0',
            'tier_quantity2' => 'nullable|integer|min:1',
            'tier_price2' => 'nullable|numeric|min:0',
            'tier_quantity3' => 'nullable|integer|min:1',
            'tier_price3' => 'nullable|numeric|min:0',
            'my_weight' => 'nullable|numeric|min:0',
        ]);

        // Checkboxes: Explicitly set to false if not present in request
        $validated['is_retain'] = $request->boolean('is_retain');
        $validated['is_stock_room'] = $request->boolean('is_stock_room');

        // Ensure remarks is always set (even if empty) - only if present in request
        if ($request->has('remarks') && !isset($validated['remarks'])) {
            $validated['remarks'] = '';
        }

        // Ensure description is always set (even if empty) - only if present in request
        if ($request->has('description') && !isset($validated['description'])) {
            $validated['description'] = '';
        }

        // Additional validation: completeness only for SETs
        if ($inventory->item_type !== 'SET') {
            unset($validated['completeness']);
        }

        // Additional validation: stock_room_id only when is_stock_room is true
        if (!$validated['is_stock_room']) {
            unset($validated['stock_room_id']);
        }

        try {
            $inventory = $this->inventoryService->updateInventoryInBrickLink(
                $inventory->store,
                $inventory,
                $validated
            );

            return redirect()
                ->route('inventory.show', $inventory)
                ->with('success', '✅ Artikel erfolgreich aktualisiert und zu BrickLink synchronisiert');
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Inventory update error', [
                'error' => $e->getMessage(),
                'inventory_id' => $inventory->inventory_id,
                'item_no' => $inventory->item_no,
            ]);

            return back()
                ->withInput()
                ->with('error', '❌ Fehler beim Synchronisieren mit BrickLink: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified inventory item
     */
    public function destroy(Inventory $inventory)
    {
        Gate::authorize('update', $inventory->store);

        try {
            $this->inventoryService->deleteInventoryFromBrickLink($inventory->store, $inventory);

            return redirect()
                ->route('inventory.index')
                ->with('success', 'Inventory item deleted successfully');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete inventory: ' . $e->getMessage());
        }
    }

    /**
     * Sync inventory from BrickLink
     */
    public function sync(Request $request)
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        $params = [];

        // Optional filters for sync
        if ($request->filled('item_type')) {
            $params['item_type'] = $request->item_type;
        }

        try {
            // Check if we should run async (default) or sync (for small inventories)
            $runAsync = $request->boolean('async', true);

            if ($runAsync) {
                // Dispatch job to run in background
                \App\Jobs\SyncBrickLinkInventoryJob::dispatch($store, $params);

                return redirect()
                    ->route('inventory.index')
                    ->with('success', '🔄 Inventory sync started in background. This may take a few minutes for large inventories. You can continue working while the sync runs.');
            } else {
                // Run synchronously (for testing or small inventories)
                $result = $this->inventoryService->syncInventoryFromBrickLink($store, $params);

                return redirect()
                    ->route('inventory.index')
                    ->with('success', sprintf(
                        '✅ Inventory synced: %d total, %d created, %d updated',
                        $result['total'],
                        $result['created'],
                        $result['updated']
                    ));
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Inventory sync failed: ' . $e->getMessage());
        }
    }

    /**
     * Refresh price guide for an inventory item
     */
    public function refreshPriceGuide(Inventory $inventory)
    {
        Gate::authorize('update', $inventory->store);

        try {
            $priceGuide = $this->catalogItemService->getPriceGuideSuggestion(
                $inventory->store,
                $inventory->item_type,
                $inventory->item_no,
                $inventory->new_or_used
            );

            if (!empty($priceGuide)) {
                $inventory->update([
                    'avg_price' => $priceGuide['avg_price'] ?? null,
                    'min_price' => $priceGuide['min_price'] ?? null,
                    'max_price' => $priceGuide['max_price'] ?? null,
                    'qty_sold' => $priceGuide['qty_sold'] ?? null,
                    'price_guide_data' => $priceGuide['price_guide_data'] ?? null,
                    'price_guide_fetched_at' => $priceGuide['fetched_at'] ?? now(),
                ]);

                return redirect()
                    ->route('inventory.show', $inventory)
                    ->with('success', '✅ Price Guide aktualisiert: Durchschnittspreis ' . number_format($priceGuide['avg_price'], 2) . ' €');
            } else {
                return redirect()
                    ->route('inventory.show', $inventory)
                    ->with('warning', '⚠️ Kein Price Guide für diesen Artikel gefunden');
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Price guide refresh error', [
                'error' => $e->getMessage(),
                'inventory_id' => $inventory->inventory_id,
                'item_no' => $inventory->item_no,
            ]);

            return back()
                ->with('error', '❌ Fehler beim Abrufen des Price Guide: ' . $e->getMessage());
        }
    }

    /**
     * Cache inventory images
     */
    public function cacheImages(Request $request)
    {
        $store = auth()->user()->store;
        Gate::authorize('update', $store);

        try {
            // Count images that need to be cached
            $imagesToCache = \App\Models\Inventory::where('store_id', $store->id)
                ->whereNotNull('image_url')
                ->where('image_url', 'NOT LIKE', '%/storage/%')
                ->where('image_url', '!=', '')
                ->count();

            if ($imagesToCache === 0) {
                return redirect()
                    ->route('inventory.index')
                    ->with('info', '✅ Alle Bilder sind bereits gecacht!');
            }

            \App\Jobs\CacheInventoryImagesJob::dispatch($store->id)
                ->onQueue('default');

            return redirect()
                ->route('inventory.index')
                ->with('success', "🚀 Bild-Caching gestartet für {$imagesToCache} Bilder. Dies kann einige Minuten dauern. Die Seite aktualisiert sich automatisch, wenn die Bilder fertig sind.");
        } catch (\Exception $e) {
            return back()->with('error', 'Fehler beim Starten des Bild-Cachings: ' . $e->getMessage());
        }
    }
}

