<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Services\ActivityLogger;
use App\Services\BrickLink\BrickLinkService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Display a listing of the orders.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $user = auth()->user();

        // Check if user has a store
        if (! $user->store) {
            return redirect()->route('store.setup-wizard')
                ->with('error', 'Bitte richten Sie zuerst Ihren Store ein.');
        }

        $query = $user->store->orders()->with('items');

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by payment status
        if ($request->filled('is_paid')) {
            $query->where('is_paid', $request->boolean('is_paid'));
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bricklink_order_id', 'like', "%{$search}%")
                    ->orWhere('buyer_name', 'like', "%{$search}%")
                    ->orWhere('buyer_email', 'like', "%{$search}%");
            });
        }

        // Sort
        $sortBy = $request->get('sort_by', 'order_date');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $orders = $query->paginate(25);

        return view('orders.index', compact('orders'));
    }

    /**
     * Display the specified order.
     */
    public function show(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load('items', 'invoice', 'feedback');

        return view('orders.show', compact('order'));
    }

    /**
     * Display the pack view for an order.
     */
    public function pack(Order $order)
    {
        Gate::authorize('view', $order);

        $order->load(['items' => function ($query) {
            $query->orderBy('store_location')
                ->orderBy('item_name');
        }]);

        // Group items by store location
        $itemsByLocation = $order->items->groupBy('store_location');

        return view('orders.pack', compact('order', 'itemsByLocation'));
    }

    /**
     * Mark an order item as packed.
     */
    public function packItem(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $itemId = $request->input('item_id');
        $item = $order->items()->findOrFail($itemId);

        $item->update([
            'is_packed' => true,
            'packed_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Mark an order item as unpacked.
     */
    public function unpackItem(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $itemId = $request->input('item_id');
        $item = $order->items()->findOrFail($itemId);

        $item->update([
            'is_packed' => false,
            'packed_at' => null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * Sync order with BrickLink.
     */
    public function sync(Order $order)
    {
        Gate::authorize('update', $order);

        Log::debug('Synchronizing BrickLink order', ['orderId' => $order->bricklink_order_id]);

        try {
            $service = new BrickLinkService;
            $orderData = $service->fetchOrder($order->store, $order->bricklink_order_id);

            Log::debug('BrickLink order data', ['orderData' => $orderData]);

            $order->update([
                'status' => $orderData['status'],
                'is_paid' => $orderData['payment']['status'] === 'Received',
                'last_synced_at' => now(),
            ]);

            return redirect()->back()->with('success', 'Order synchronized successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to sync order: '.$e->getMessage());
        }
    }

    /**
     * Trigger async sync for all orders
     */
    public function syncAll(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        Log::debug('Synchronizing BrickLink orders', ['orderIds' => $request->all()]);

        $store = auth()->user()->store;

        if (! $store) {
            Log::debug('No store configured for user', ['userId' => auth()->id()]);
            return redirect()->back()->with('error', 'No store configured');
        }

        // Check if store has BrickLink credentials
        if (! $store->hasBrickLinkCredentials()) {
            Log::debug('Store missing BrickLink API credentials', ['storeId' => $store->id]);
            return redirect()->back()->with('error', 'BrickLink API credentials are not configured. Please update your store settings.');
        }

        // Execute sync directly (synchronously) for immediate results
        // Manual sync should not use queue to give instant feedback to user
        try {
            $service = new \App\Services\BrickLink\BrickLinkService;
            $orders = $service->fetchOrders($store, '');
            $synced = 0;

            foreach ($orders as $orderData) {
                // Skip orders older than 7 days for manual sync
                $orderDate = \Carbon\Carbon::parse($orderData['date_ordered']);
                if ($orderDate->lt(now()->subDays(7))) {
                    continue;
                }

                try {
                    // Fetch complete order details
                    $completeOrderData = $service->fetchOrder($store, $orderData['order_id']);
                    $this->syncOrderData($store, $completeOrderData, $service);
                    $synced++;
                } catch (\Exception $e) {
                    Log::warning("Could not sync order {$orderData['order_id']}: {$e->getMessage()}");
                }
            }

            return redirect()->back()->with('success', "Order synchronization completed. Synced {$synced} orders.");
        } catch (\Exception $e) {
            Log::error('Order sync failed', ['error' => $e->getMessage()]);
            return redirect()->back()->with('error', 'Order synchronization failed: ' . $e->getMessage());
        }
    }

    /**
     * Sync individual order data to database
     */
    private function syncOrderData(\App\Models\Store $store, array $orderData, \App\Services\BrickLink\BrickLinkService $service): void
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

                // Display Cost
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
        $this->syncOrderItems($order, $orderData['order_id'], $service);
    }

    /**
     * Sync order items
     */
    private function syncOrderItems(Order $order, string $bricklinkOrderId, \App\Services\BrickLink\BrickLinkService $service): void
    {
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

                \App\Models\OrderItem::create([
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
     * Generate BrickLink image URL
     */
    private function getBrickLinkImageUrl(string $itemType, string $itemNo, ?int $colorId): ?string
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

    /**
     * Update order status.
     */
    public function updateStatus(\App\Http\Requests\UpdateOrderStatusRequest $request, Order $order)
    {
        Gate::authorize('update', $order);

        $validated = $request->validated();

        $service = new BrickLinkService;
        $updatedOrderData = $service->updateOrderStatus($order->store, $order->bricklink_order_id, $validated['status']);

        // Use the status returned from BrickLink to ensure synchronization
        $newStatus = $updatedOrderData['status'] ?? $validated['status'];

        Log::info('Order status updated successfully', [
            'orderId' => $order->bricklink_order_id,
            'requestedStatus' => $validated['status'],
            'actualStatus' => $newStatus,
        ]);

        $order->update([
            'status' => $newStatus,
            'date_status_changed' => now(),
        ]);

        ActivityLogger::info('order.status.updated', "Status der Bestellung {$order->bricklink_order_id} geändert zu: {$newStatus}", $order);

        return redirect()->back()->with('success', 'Bestellstatus erfolgreich aktualisiert');

    }

    /**
     * Update order shipping information and tracking number.
     */
    public function updateShipping(\App\Http\Requests\UpdateOrderShippingRequest $request, Order $order)
    {
        Gate::authorize('update', $order);

        try {
            $validated = $request->validated();

            $service = new BrickLinkService;

            // Prepare shipping data for BrickLink
            $shippingData = [
                'tracking_no' => $validated['tracking_number'],
            ];

            if (isset($validated['tracking_link'])) {
                $shippingData['tracking_link'] = $validated['tracking_link'];
            }

            // Update shipping info on BrickLink
            $service->updateOrderShipping($order->store, $order->bricklink_order_id, $shippingData);

            // Update local order
            $updateData = [
                'tracking_number' => $validated['tracking_number'],
            ];

            if (isset($validated['tracking_link'])) {
                $updateData['tracking_link'] = $validated['tracking_link'];
            }

            $order->update($updateData);

            ActivityLogger::info('order.shipping.updated', "Sendungsverfolgung für Bestellung {$order->bricklink_order_id} aktualisiert: {$validated['tracking_number']}", $order);

            return redirect()->back()->with('success', 'Sendungsverfolgung erfolgreich aktualisiert');
        } catch (\Exception $e) {
            Log::error('Failed to update order shipping', [
                'orderId' => $order->bricklink_order_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Fehler beim Aktualisieren der Sendungsverfolgung: '.$e->getMessage());
        }
    }

    /**
     * Mark order as shipped.
     */
    public function ship(\App\Http\Requests\UpdateOrderShippingRequest $request, Order $order)
    {
        Gate::authorize('update', $order);

        try {
            $validated = $request->validated();

            $service = new BrickLinkService;

            // Update status to Shipped
            $service->updateOrderStatus($order->store, $order->bricklink_order_id, 'Shipped');

            // Update shipping info if tracking number provided
            $shippingData = [
                'tracking_no' => $validated['tracking_number'],
            ];

            if (isset($validated['tracking_link'])) {
                $shippingData['tracking_link'] = $validated['tracking_link'];
            }

            $service->updateOrderShipping($order->store, $order->bricklink_order_id, $shippingData);

            // Update local order
            $updateData = [
                'status' => 'Shipped',
                'shipped_date' => now(),
                'date_status_changed' => now(),
                'tracking_number' => $validated['tracking_number'],
            ];

            if (isset($validated['tracking_link'])) {
                $updateData['tracking_link'] = $validated['tracking_link'];
            }

            $order->update($updateData);

            ActivityLogger::info('order.shipped', "Bestellung {$order->bricklink_order_id} als versendet markiert", $order);

            return redirect()->back()->with('success', 'Bestellung erfolgreich als versendet markiert');
        } catch (\Exception $e) {
            Log::error('Failed to mark order as shipped', [
                'orderId' => $order->bricklink_order_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Fehler beim Markieren der Bestellung als versendet: '.$e->getMessage());
        }
    }

    /**
     * Generate and download shipping label PDF for an order.
     */
    public function shippingLabel(Order $order)
    {
        Gate::authorize('view', $order);

        // Check if order is already shipped
        if ($order->status === 'Shipped' || $order->shipped_date) {
            return redirect()->back()->with('error', 'Versandetikett kann nicht für bereits versendete Bestellungen erstellt werden.');
        }

        try {
            $data = [
                'order' => $order,
                'store' => $order->store,
            ];

            // Generate PDF with 15x10 cm dimensions in landscape (150mm x 100mm)
            // Width: 15cm = 425.197 points, Height: 10cm = 283.465 points
            $pdf = Pdf::loadView('orders.shipping-label', $data);
            $pdf->setPaper([0, 0, 425.197, 283.465]); // [x, y, width, height] in points

            return $pdf->download('Versandetikett-'.$order->bricklink_order_id.'.pdf');
        } catch (\Exception $e) {
            Log::error('Failed to generate shipping label', [
                'orderId' => $order->bricklink_order_id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Fehler beim Erstellen des Versandetiketts: '.$e->getMessage());
        }
    }
}
