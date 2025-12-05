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
        $service = new BrickLinkService;

        $statusParam = $status ?? '';

        $orders = $service->fetchOrders($store, $statusParam);
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

            DB::transaction(function () use ($store, $service, $orderData, &$synced) {
                // Fetch complete order details from API
                try {
                    $completeOrderData = $service->fetchOrder($store, $orderData['order_id']);
                    $this->syncOrder($store, $completeOrderData);
                } catch (\Exception $e) {
                    // If fetching complete details fails, use the list data as fallback
                    $this->warn("Could not fetch complete order details for {$orderData['order_id']}, using list data: {$e->getMessage()}");
                    $this->syncOrder($store, $orderData);
                }
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
                // Timestamps
                'order_date' => $orderData['date_ordered'],
                'date_ordered' => $orderData['date_ordered'],
                'date_status_changed' => $orderData['date_status_changed'] ?? null,

                // Status & Counts
                'status' => $orderData['status'],
                'total_count' => $orderData['total_count'] ?? 0,
                'unique_count' => $orderData['unique_count'] ?? 0,

                // Buyer Information
                'buyer_name' => $orderData['buyer_name'] ?? '',
                'buyer_email' => $orderData['buyer_email'] ?? null,
                'buyer_username' => $orderData['username'] ?? null,
                'buyer_order_count' => $orderData['buyer_order_count'] ?? 0,

                // Shipping Address
                'shipping_name' => $orderData['shipping']['address']['name']['full'] ?? null,
                'shipping_address1' => $orderData['shipping']['address']['address1'] ?? null,
                'shipping_address2' => $orderData['shipping']['address']['address2'] ?? null,
                'shipping_city' => $orderData['shipping']['address']['city'] ?? null,
                'shipping_state' => $orderData['shipping']['address']['state'] ?? null,
                'shipping_postal_code' => $orderData['shipping']['address']['postal_code'] ?? null,
                'shipping_country' => $orderData['shipping']['address']['country_code'] ?? null,

                // Cost Information
                'subtotal' => $orderData['cost']['subtotal'] ?? 0,
                'grand_total' => $orderData['cost']['grand_total'] ?? 0,
                'final_total' => $orderData['cost']['final_total'] ?? ($orderData['cost']['grand_total'] ?? 0),
                'shipping_cost' => $orderData['cost']['shipping'] ?? 0,
                'insurance' => $orderData['cost']['insurance'] ?? 0,
                'tax' => $orderData['cost']['salesTax'] ?? 0,
                'discount' => $orderData['cost']['etc1'] ?? 0,
                'etc1' => $orderData['cost']['etc1'] ?? 0,
                'etc2' => $orderData['cost']['etc2'] ?? 0,
                'credit' => $orderData['cost']['credit'] ?? 0,
                'credit_coupon' => $orderData['cost']['credit_coupon'] ?? 0,
                'currency_code' => $orderData['cost']['currency_code'] ?? 'EUR',

                // VAT Information
                'vat_collected_by_bl' => $orderData['vat_collected_by_bl'] ?? false,
                'vat_rate' => $orderData['cost']['vat_rate'] ?? null,
                'vat_amount' => $orderData['cost']['vat_amount'] ?? 0,
                'salesTax_collected_by_bl' => $orderData['salesTax_collected_by_bl'] ?? false,

                // Display Cost (in different currency)
                'display_currency_code' => $orderData['disp_cost']['currency_code'] ?? null,
                'disp_subtotal' => $orderData['disp_cost']['subtotal'] ?? null,
                'disp_grand_total' => $orderData['disp_cost']['grand_total'] ?? null,
                'disp_final_total' => $orderData['disp_cost']['final_total'] ?? null,
                'disp_shipping' => $orderData['disp_cost']['shipping'] ?? null,
                'disp_insurance' => $orderData['disp_cost']['insurance'] ?? null,
                'disp_etc1' => $orderData['disp_cost']['etc1'] ?? null,
                'disp_etc2' => $orderData['disp_cost']['etc2'] ?? null,
                'disp_vat' => $orderData['disp_cost']['vat_amount'] ?? null,

                // Shipping Information
                'shipping_method' => $orderData['shipping']['method'] ?? null,
                'tracking_number' => $orderData['shipping']['tracking_no'] ?? null,
                'tracking_link' => $orderData['shipping']['tracking_link'] ?? null,

                // Payment Information
                'payment_method' => $orderData['payment']['method'] ?? null,
                'is_paid' => $orderData['payment']['status'] === 'Received',
                'paid_date' => isset($orderData['payment']['date_paid']) ? $orderData['payment']['date_paid'] : null,

                // Order Flags
                'is_filed' => $orderData['is_filed'] ?? false,
                'drive_thru_sent' => $orderData['drive_thru_sent'] ?? false,

                // Remarks
                'buyer_remarks' => $orderData['remarks'] ?? null,
                'seller_remarks' => $orderData['seller_remarks'] ?? null,

                // Sync Status
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
        $service = new BrickLinkService;

        try {
            $items = $service->fetchOrderItems($order->store, $bricklinkOrderId);

            // Delete existing items
            $order->items()->delete();

            // Create new items
            foreach ($items as $itemData) {
                $imageUrl = $this->getBrickLinkImageUrl(
                    $itemData['item']['type'] ?? '',
                    $itemData['item']['no'] ?? '',
                    $itemData['color_id'] ?? null
                );

                $item = OrderItem::create([
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

                // Cache image
                if ($imageUrl) {
                    try {
                        $item->cacheImage();
                    } catch (\Exception $e) {
                        // Silently fail - image caching is not critical
                    }
                }
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
