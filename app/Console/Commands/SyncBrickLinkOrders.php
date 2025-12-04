<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncBrickLinkOrders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'bricklink:sync-orders
                            {--store= : Store ID to sync (default: all active stores)}
                            {--status= : Filter by order status}
                            {--days= : Sync orders from last X days (default: 30)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize orders from BrickLink API';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $storeId = $this->option('store');
        $status = $this->option('status');
        $days = $this->option('days') ?? 30;

        $stores = $storeId
            ? Store::where('id', $storeId)->get()
            : Store::where('is_active', true)->get();

        if ($stores->isEmpty()) {
            $this->error('No active stores found with BrickLink credentials');

            return self::FAILURE;
        }

        $totalSynced = 0;

        foreach ($stores as $store) {
            if (! $store->hasBrickLinkCredentials()) {
                $this->warn("Skipping store '{$store->name}' - no BrickLink credentials");

                continue;
            }

            $this->info("Syncing orders for store: {$store->name}");

            try {
                $synced = $this->syncStoreOrders($store, $status, $days);
                $totalSynced += $synced;

                $this->info("✓ Synced {$synced} orders for {$store->name}");
            } catch (\Exception $e) {
                $this->error("✗ Error syncing {$store->name}: {$e->getMessage()}");
            }
        }

        $this->info("Total orders synced: {$totalSynced}");

        return self::SUCCESS;
    }

    /**
     * Sync orders for a specific store
     */
    protected function syncStoreOrders(Store $store, ?string $status, int $days): int
    {
        $service = new BrickLinkService($store);

        $params = [
            'direction' => 'in',
        ];

        if ($status) {
            $params['status'] = $status;
        }

        $orders = $service->getOrders($params);
        $synced = 0;

        $bar = $this->output->createProgressBar(count($orders));
        $bar->start();

        foreach ($orders as $orderData) {
            // Skip orders older than X days
            $orderDate = \Carbon\Carbon::parse($orderData['date_ordered']);
            if ($orderDate->lt(now()->subDays($days))) {
                $bar->advance();

                continue;
            }

            DB::transaction(function () use ($store, $orderData, &$synced) {
                $this->syncOrder($store, $orderData);
                $synced++;
            });

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        return $synced;
    }

    /**
     * Sync individual order
     */
    protected function syncOrder(Store $store, array $orderData): void
    {
        $order = Order::updateOrCreate(
            [
                'store_id' => $store->id,
                'bricklink_order_id' => $orderData['order_id'],
            ],
            [
                'order_date' => $orderData['date_ordered'],
                'status' => $orderData['status'],
                'buyer_name' => $orderData['buyer_name'] ?? '',
                'buyer_email' => $orderData['buyer_email'] ?? null,
                'buyer_username' => $orderData['username'] ?? null,
                'shipping_name' => $orderData['shipping']['address']['name']['full'] ?? null,
                'shipping_address1' => $orderData['shipping']['address']['address1'] ?? null,
                'shipping_address2' => $orderData['shipping']['address']['address2'] ?? null,
                'shipping_city' => $orderData['shipping']['address']['city'] ?? null,
                'shipping_state' => $orderData['shipping']['address']['state'] ?? null,
                'shipping_postal_code' => $orderData['shipping']['address']['postal_code'] ?? null,
                'shipping_country' => $orderData['shipping']['address']['country_code'] ?? null,
                'subtotal' => $orderData['cost']['subtotal'] ?? 0,
                'grand_total' => $orderData['cost']['grand_total'] ?? 0,
                'shipping_cost' => $orderData['cost']['shipping'] ?? 0,
                'insurance' => $orderData['cost']['insurance'] ?? 0,
                'tax' => $orderData['cost']['salesTax'] ?? 0,
                'discount' => $orderData['cost']['etc1'] ?? 0,
                'currency_code' => $orderData['cost']['currency_code'] ?? 'EUR',
                'shipping_method' => $orderData['shipping']['method'] ?? null,
                'payment_method' => $orderData['payment']['method'] ?? null,
                'is_paid' => $orderData['payment']['status'] === 'Received',
                'buyer_remarks' => $orderData['remarks'] ?? null,
                'last_synced_at' => now(),
                'raw_data' => $orderData,
            ]
        );

        // Sync order items
        $this->syncOrderItems($order, $orderData['order_id']);
    }

    /**
     * Sync order items
     */
    protected function syncOrderItems(Order $order, string $bricklinkOrderId): void
    {
        $service = new BrickLinkService($order->store);

        try {
            $items = $service->getOrderItems($bricklinkOrderId);

            // Delete existing items
            $order->items()->delete();

            // Create new items
            foreach ($items as $itemData) {
                $imageUrl = $this->getBrickLinkImageUrl(
                    $itemData['item']['type'] ?? '',
                    $itemData['item']['no'] ?? '',
                    $itemData['color_id'] ?? null
                );

                OrderItem::create([
                    'order_id' => $order->id,
                    'item_type' => $itemData['item']['type'] ?? '',
                    'item_number' => $itemData['item']['no'] ?? '',
                    'item_name' => $itemData['item']['name'] ?? '',
                    'color_id' => $itemData['color_id'] ?? null,
                    'color_name' => $itemData['color_name'] ?? null,
                    'quantity' => $itemData['quantity'] ?? 0,
                    'unit_price' => $itemData['unit_price'] ?? 0,
                    'total_price' => ($itemData['quantity'] ?? 0) * ($itemData['unit_price'] ?? 0),
                    'condition' => $itemData['new_or_used'] ?? 'N',
                    'completeness' => $itemData['completeness'] ?? null,
                    'description' => $itemData['description'] ?? null,
                    'remarks' => $itemData['remarks'] ?? null,
                    'image_url' => $imageUrl,
                ]);
            }
        } catch (\Exception $e) {
            $this->warn("Could not sync items for order {$bricklinkOrderId}: {$e->getMessage()}");
        }
    }

    /**
     * Generate BrickLink image URL for an item
     */
    protected function getBrickLinkImageUrl(string $itemType, string $itemNo, ?int $colorId): ?string
    {
        if (empty($itemType) || empty($itemNo)) {
            return null;
        }

        // BrickLink image URL format:
        // https://img.bricklink.com/ItemImage/{PT|SN|MN|PN}/{colorId}/{itemNo}.png
        $typeMap = [
            'PART' => 'PN',
            'SET' => 'SN',
            'MINIFIG' => 'MN',
            'GEAR' => 'PN',
            'BOOK' => 'PN',
            'CATALOG' => 'PN',
            'INSTRUCTION' => 'PN',
        ];

        $imageType = $typeMap[$itemType] ?? 'PN';

        // If no color, use item type without color
        if (! $colorId) {
            return "https://img.bricklink.com/ItemImage/{$imageType}/{$itemNo}.png";
        }

        return "https://img.bricklink.com/ItemImage/{$imageType}/{$colorId}/{$itemNo}.png";
    }
}
