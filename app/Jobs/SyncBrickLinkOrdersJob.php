<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SyncBrickLinkOrdersJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Store $store,
        public ?string $status = null,
        public int $days = 30
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! $this->store->hasBrickLinkCredentials()) {
            Log::warning("Store {$this->store->id} has no BrickLink credentials");

            return;
        }

        Log::info("Starting BrickLink sync for store: {$this->store->name}");

        try {
            $service = new BrickLinkService($this->store);

            $params = [
                'direction' => 'in',
            ];

            if ($this->status) {
                $params['status'] = $this->status;
            }

            $orders = $service->getOrders($params);
            $synced = 0;

            foreach ($orders as $orderData) {
                // Skip orders older than X days
                $orderDate = \Carbon\Carbon::parse($orderData['date_ordered']);
                if ($orderDate->lt(now()->subDays($this->days))) {
                    continue;
                }

                DB::transaction(function () use ($orderData, &$synced) {
                    $this->syncOrder($orderData);
                    $synced++;
                });
            }

            Log::info("Synced {$synced} orders for store: {$this->store->name}");
        } catch (\Exception $e) {
            Log::error("BrickLink sync failed for store {$this->store->name}: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Sync individual order
     */
    protected function syncOrder(array $orderData): void
    {
        $order = Order::updateOrCreate(
            [
                'store_id' => $this->store->id,
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
        $service = new BrickLinkService($this->store);

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
            Log::warning("Could not sync items for order {$bricklinkOrderId}: {$e->getMessage()}");
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

        if (! $colorId) {
            return "https://img.bricklink.com/ItemImage/{$imageType}/{$itemNo}.png";
        }

        return "https://img.bricklink.com/ItemImage/{$imageType}/{$colorId}/{$itemNo}.png";
    }
}
