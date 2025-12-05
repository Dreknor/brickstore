<?php

namespace App\Services;

use App\Models\Inventory;
use App\Models\Store;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService
{
    public function __construct(
        protected BrickLinkService $brickLinkService
    ) {}

    /**
     * Sync inventory from BrickLink to local database
     */
    public function syncInventoryFromBrickLink(Store $store, array $params = []): array
    {
        try {
            Log::info('Starting inventory sync from BrickLink', [
                'store_id' => $store->id,
                'params' => $params,
            ]);

            // Log start of sync
            \App\Services\ActivityLogger::info(
                'inventory.sync.started',
                'Inventory sync from BrickLink started',
                $store,
                ['params' => $params]
            );

            // Fetch inventories from BrickLink
            $brickLinkItems = $this->brickLinkService->fetchInventories($store, $params);

            $synced = 0;
            $created = 0;
            $updated = 0;
            $errors = [];

            DB::beginTransaction();

            foreach ($brickLinkItems as $blItem) {
                try {
                    $wasNew = !Inventory::where('inventory_id', $blItem['inventory_id'])->exists();

                    $inventory = $this->syncInventoryItem($store, $blItem);
                    $synced++;

                    if ($wasNew) {
                        $created++;
                        // Log creation
                        \App\Services\ActivityLogger::info(
                            'inventory.item.synced.created',
                            "New inventory item synced: {$inventory->item_no}",
                            $inventory,
                            [
                                'item_no' => $inventory->item_no,
                                'item_type' => $inventory->item_type,
                                'quantity' => $inventory->quantity,
                            ]
                        );
                    } else {
                        $updated++;
                        // Log update
                        \App\Services\ActivityLogger::debug(
                            'inventory.item.synced.updated',
                            "Inventory item updated: {$inventory->item_no}",
                            $inventory
                        );
                    }
                } catch (\Exception $e) {
                    $errors[] = [
                        'inventory_id' => $blItem['inventory_id'] ?? null,
                        'error' => $e->getMessage(),
                    ];
                    Log::error('Failed to sync inventory item', [
                        'inventory_id' => $blItem['inventory_id'] ?? null,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            DB::commit();

            Log::info('Inventory sync completed', [
                'store_id' => $store->id,
                'total' => count($brickLinkItems),
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'errors' => count($errors),
            ]);

            // Log completion
            \App\Services\ActivityLogger::info(
                'inventory.sync.completed',
                "Inventory sync completed: {$synced} items synced ({$created} created, {$updated} updated)",
                $store,
                [
                    'total' => count($brickLinkItems),
                    'synced' => $synced,
                    'created' => $created,
                    'updated' => $updated,
                    'errors_count' => count($errors),
                ]
            );

            return [
                'total' => count($brickLinkItems),
                'synced' => $synced,
                'created' => $created,
                'updated' => $updated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Inventory sync failed', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            // Log error
            \App\Services\ActivityLogger::error(
                'inventory.sync.failed',
                "Inventory sync failed: {$e->getMessage()}",
                $store,
                ['error' => $e->getMessage()]
            );

            throw $e;
        }
    }

    /**
     * Sync a single inventory item
     */
    public function syncInventoryItem(Store $store, array $blItem): Inventory
    {
        $data = $this->mapBrickLinkToInventory($blItem);
        $data['store_id'] = $store->id;

        return Inventory::updateOrCreate(
            [
                'store_id' => $store->id,
                'inventory_id' => $blItem['inventory_id'],
            ],
            $data
        );
    }

    /**
     * Create inventory in BrickLink from local data
     */
    public function createInventoryInBrickLink(Store $store, array $inventoryData): Inventory
    {
        // Format data for BrickLink API
        $blData = $this->mapInventoryToBrickLink($inventoryData);

        // Create in BrickLink
        $blResponse = $this->brickLinkService->createInventory($store, $blData);

        // Save to local database
        $data = $this->mapBrickLinkToInventory($blResponse);
        $data['store_id'] = $store->id;

        $inventory = Inventory::create($data);

        // Log creation
        \App\Services\ActivityLogger::info(
            'inventory.item.created',
            "New inventory item created: {$inventory->item_no} ({$inventory->item_type})",
            $inventory,
            [
                'item_no' => $inventory->item_no,
                'item_type' => $inventory->item_type,
                'quantity' => $inventory->quantity,
                'unit_price' => $inventory->unit_price,
                'color_name' => $inventory->color_name,
            ]
        );

        return $inventory;
    }

    /**
     * Update inventory in BrickLink
     */
    public function updateInventoryInBrickLink(Store $store, Inventory $inventory, array $inventoryData): Inventory
    {
        // Store old values for logging
        $oldValues = $inventory->only(['quantity', 'unit_price', 'is_stock_room']);

        // Format data for BrickLink API
        $blData = $this->mapInventoryToBrickLink($inventoryData);

        // Update in BrickLink
        $blResponse = $this->brickLinkService->updateInventory($store, $inventory->inventory_id, $blData);

        // Update local database
        $data = $this->mapBrickLinkToInventory($blResponse);
        $inventory->update($data);

        $inventory = $inventory->fresh();

        // Determine what changed
        $changes = [];
        foreach (['quantity', 'unit_price', 'is_stock_room'] as $field) {
            if (isset($oldValues[$field]) && $oldValues[$field] != $inventory->$field) {
                $changes[$field] = [
                    'old' => $oldValues[$field],
                    'new' => $inventory->$field,
                ];
            }
        }

        // Log update
        \App\Services\ActivityLogger::info(
            'inventory.item.updated',
            "Inventory item updated: {$inventory->item_no}",
            $inventory,
            [
                'item_no' => $inventory->item_no,
                'changes' => $changes,
            ]
        );

        return $inventory;
    }

    /**
     * Delete inventory from BrickLink and local database
     */
    public function deleteInventoryFromBrickLink(Store $store, Inventory $inventory): bool
    {
        // Store info for logging
        $itemInfo = [
            'inventory_id' => $inventory->inventory_id,
            'item_no' => $inventory->item_no,
            'item_type' => $inventory->item_type,
            'quantity' => $inventory->quantity,
            'color_name' => $inventory->color_name,
        ];

        try {
            // Delete from BrickLink
            $this->brickLinkService->deleteInventory($store, $inventory->inventory_id);

            // Delete from local database
            $inventory->delete();

            // Log deletion
            \App\Services\ActivityLogger::warning(
                'inventory.item.deleted',
                "Inventory item deleted: {$itemInfo['item_no']} ({$itemInfo['item_type']})",
                null,
                $itemInfo
            );

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to delete inventory', [
                'inventory_id' => $inventory->id,
                'bl_inventory_id' => $inventory->inventory_id,
                'error' => $e->getMessage(),
            ]);

            // Log error
            \App\Services\ActivityLogger::error(
                'inventory.item.delete.failed',
                "Failed to delete inventory item: {$e->getMessage()}",
                null,
                array_merge($itemInfo, ['error' => $e->getMessage()])
            );

            throw $e;
        }
    }

    /**
     * Map BrickLink API response to Inventory model data
     */
    protected function mapBrickLinkToInventory(array $blItem): array
    {
        return [
            'inventory_id' => $blItem['inventory_id'],
            'item_no' => $blItem['item']['no'] ?? null,
            'item_type' => $blItem['item']['type'] ?? null,
            'color_id' => $blItem['color_id'] ?? null,
            'color_name' => $blItem['color_name'] ?? null,
            'quantity' => $blItem['quantity'] ?? 0,
            'new_or_used' => $blItem['new_or_used'] ?? 'N',
            'completeness' => $blItem['completeness'] ?? null,
            'unit_price' => $blItem['unit_price'] ?? 0,
            'bind_id' => $blItem['bind_id'] ?? null,
            'description' => $blItem['description'] ?? null,
            'remarks' => $blItem['remarks'] ?? null,
            'bulk' => $blItem['bulk'] ?? 1,
            'is_retain' => $blItem['is_retain'] ?? false,
            'is_stock_room' => $blItem['is_stock_room'] ?? false,
            'stock_room_id' => $blItem['stock_room_id'] ?? null,
            'date_created' => $blItem['date_created'] ?? null,
            'date_updated' => isset($blItem['date_updated']) ? $blItem['date_updated'] : now(),
            'tier_prices' => $blItem['tier_prices'] ?? null,
            'sale_rate' => $blItem['sale_rate'] ?? null,
            'my_cost' => $blItem['my_cost'] ?? null,
            'tier_quantity1' => $blItem['tier_quantity1'] ?? null,
            'tier_price1' => $blItem['tier_price1'] ?? null,
            'tier_quantity2' => $blItem['tier_quantity2'] ?? null,
            'tier_price2' => $blItem['tier_price2'] ?? null,
            'tier_quantity3' => $blItem['tier_quantity3'] ?? null,
            'tier_price3' => $blItem['tier_price3'] ?? null,
            'my_weight' => $blItem['my_weight'] ?? null,
        ];
    }

    /**
     * Map Inventory model data to BrickLink API format
     */
    protected function mapInventoryToBrickLink(array $inventoryData): array
    {
        $blData = [
            'item' => [
                'no' => $inventoryData['item_no'],
                'type' => $inventoryData['item_type'],
            ],
            'quantity' => $inventoryData['quantity'],
            'unit_price' => $inventoryData['unit_price'],
            'new_or_used' => $inventoryData['new_or_used'] ?? 'N',
        ];

        // Optional fields
        if (isset($inventoryData['color_id'])) {
            $blData['color_id'] = $inventoryData['color_id'];
        }

        if (isset($inventoryData['completeness'])) {
            $blData['completeness'] = $inventoryData['completeness'];
        }

        if (isset($inventoryData['description'])) {
            $blData['description'] = $inventoryData['description'];
        }

        if (isset($inventoryData['remarks'])) {
            $blData['remarks'] = $inventoryData['remarks'];
        }

        if (isset($inventoryData['bulk'])) {
            $blData['bulk'] = $inventoryData['bulk'];
        }

        if (isset($inventoryData['is_retain'])) {
            $blData['is_retain'] = $inventoryData['is_retain'];
        }

        if (isset($inventoryData['is_stock_room'])) {
            $blData['is_stock_room'] = $inventoryData['is_stock_room'];
        }

        if (isset($inventoryData['stock_room_id'])) {
            $blData['stock_room_id'] = $inventoryData['stock_room_id'];
        }

        if (isset($inventoryData['sale_rate'])) {
            $blData['sale_rate'] = $inventoryData['sale_rate'];
        }

        if (isset($inventoryData['my_cost'])) {
            $blData['my_cost'] = $inventoryData['my_cost'];
        }

        // Tier pricing
        if (isset($inventoryData['tier_quantity1']) && isset($inventoryData['tier_price1'])) {
            $blData['tier_quantity1'] = $inventoryData['tier_quantity1'];
            $blData['tier_price1'] = $inventoryData['tier_price1'];
        }

        if (isset($inventoryData['tier_quantity2']) && isset($inventoryData['tier_price2'])) {
            $blData['tier_quantity2'] = $inventoryData['tier_quantity2'];
            $blData['tier_price2'] = $inventoryData['tier_price2'];
        }

        if (isset($inventoryData['tier_quantity3']) && isset($inventoryData['tier_price3'])) {
            $blData['tier_quantity3'] = $inventoryData['tier_quantity3'];
            $blData['tier_price3'] = $inventoryData['tier_price3'];
        }

        if (isset($inventoryData['my_weight'])) {
            $blData['my_weight'] = $inventoryData['my_weight'];
        }

        return $blData;
    }

    /**
     * Check if item was recently created (used for sync statistics)
     */
    protected function wasRecentlyCreated(int $inventoryId): bool
    {
        $item = Inventory::where('inventory_id', $inventoryId)->first();

        if (!$item) {
            return false;
        }

        return $item->created_at->gt(now()->subMinutes(5));
    }
}

