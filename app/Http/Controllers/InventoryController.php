<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class InventoryController extends Controller
{
    public function __construct(
        protected InventoryService $inventoryService
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
    public function create()
    {
        $store = auth()->user()->store;

        if (!$store) {
            return redirect()->route('store.setup-wizard');
        }

        Gate::authorize('update', $store);

        return view('inventory.create', compact('store'));
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
            'completeness' => 'nullable|in:C,B,S',
            'unit_price' => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'remarks' => 'nullable|string',
            'bulk' => 'nullable|integer|min:1',
            'is_retain' => 'boolean',
            'is_stock_room' => 'boolean',
            'stock_room_id' => 'nullable|string',
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

        try {
            $inventory = $this->inventoryService->createInventoryInBrickLink($store, $validated);

            return redirect()
                ->route('inventory.show', $inventory)
                ->with('success', 'Inventory item created successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to create inventory: ' . $e->getMessage());
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
            'unit_price' => 'sometimes|numeric|min:0',
            'description' => 'nullable|string',
            'remarks' => 'nullable|string',
            'bulk' => 'nullable|integer|min:1',
            'is_retain' => 'boolean',
            'is_stock_room' => 'boolean',
            'stock_room_id' => 'nullable|string',
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

        try {
            $inventory = $this->inventoryService->updateInventoryInBrickLink(
                $inventory->store,
                $inventory,
                $validated
            );

            return redirect()
                ->route('inventory.show', $inventory)
                ->with('success', 'Inventory item updated successfully');
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Failed to update inventory: ' . $e->getMessage());
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
            $result = $this->inventoryService->syncInventoryFromBrickLink($store, $params);

            return redirect()
                ->route('inventory.index')
                ->with('success', sprintf(
                    'Inventory synced: %d total, %d created, %d updated',
                    $result['total'],
                    $result['created'],
                    $result['updated']
                ));
        } catch (\Exception $e) {
            return back()->with('error', 'Inventory sync failed: ' . $e->getMessage());
        }
    }
}

