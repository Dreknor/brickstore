<?php

namespace App\Http\Controllers;

use App\Jobs\SyncInventoryItemToBrickLinkJob;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Inventory;
use App\Services\ActivityLogger;
use App\Services\BrickLink\BrickLinkService;
use App\Services\InvoiceService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(protected BrickLinkService $brickLinkService) {}

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

    public function searchInventory(Request $request, Order $order): JsonResponse
    {
        Gate::authorize('view', $order);

        $validated = $request->validate([
            'search' => 'required|string|min:2|max:100',
        ]);

        $search = $validated['search'];
        $items = Inventory::query()
            ->where('store_id', $order->store_id)
            ->where('quantity', '>', 0)
            ->where('is_retain', false)
            ->where(function ($query) use ($search) {
                $query->where('item_no', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('color_name', 'like', "%{$search}%");
            })
            ->orderBy('item_no')
            ->limit(20)
            ->get(['id', 'item_no', 'item_type', 'description', 'color_name', 'new_or_used', 'quantity', 'unit_price'])
            ->map(fn (Inventory $item) => [
                'id' => $item->id,
                'item_no' => $item->item_no,
                'item_type' => $item->item_type,
                'item_name' => $item->description ?: $item->item_no,
                'color_name' => $item->color_name,
                'condition' => $item->new_or_used,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ]);

        return response()->json($items);
    }

    public function addItem(Request $request, Order $order, InvoiceService $invoiceService): RedirectResponse
    {
        Gate::authorize('update', $order);

        $validated = $request->validate([
            'inventory_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
            'unit_price' => ['required', 'numeric', 'min:0', 'max:99999999.99', 'regex:/^\d+(\.\d{1,2})?$/'],
        ]);

        [$item, $invoice] = DB::transaction(function () use ($order, $validated) {
            Order::whereKey($order->id)->lockForUpdate()->firstOrFail();

            $inventory = Inventory::where('store_id', $order->store_id)
                ->lockForUpdate()
                ->whereKey($validated['inventory_id'])
                ->first();

            if (! $inventory) {
                throw ValidationException::withMessages([
                    'inventory_id' => 'Der Inventar-Artikel wurde nicht gefunden.',
                ]);
            }

            if ($inventory->is_retain) {
                throw ValidationException::withMessages([
                    'inventory_id' => 'Dieser Artikel ist im Inventar als zurückzuhalten markiert.',
                ]);
            }

            if ($validated['quantity'] > $inventory->quantity) {
                throw ValidationException::withMessages([
                    'quantity' => "Es sind nur {$inventory->quantity} Stück auf Lager.",
                ]);
            }

            $unitPrice = round((float) $validated['unit_price'], 2);
            $totalPrice = round($unitPrice * $validated['quantity'], 2);

            $item = $order->items()->create([
                'inventory_id' => $inventory->id,
                'is_manual' => true,
                'item_type' => $inventory->item_type,
                'item_number' => $inventory->item_no,
                'item_name' => $inventory->description ?: $inventory->item_no,
                'color_id' => $inventory->color_id,
                'color_name' => $inventory->color_name,
                'quantity' => $validated['quantity'],
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'condition' => $inventory->new_or_used,
                'completeness' => $inventory->completeness,
                'description' => $inventory->description,
                'remarks' => $inventory->remarks,
                'image_url' => $inventory->image_url,
                'store_location' => $inventory->stock_room_id,
            ]);

            $inventory->decrement('quantity', $validated['quantity']);

            $invoice = $order->invoice()->lockForUpdate()->first();
            if ($invoice) {
                $additionalTax = $invoice->is_small_business
                    ? 0
                    : round($totalPrice * (float) $invoice->tax_rate / 100, 2);

                $invoice->update([
                    'subtotal' => (float) $invoice->subtotal + $totalPrice,
                    'tax_amount' => (float) $invoice->tax_amount + $additionalTax,
                    'total' => (float) $invoice->total + $totalPrice + $additionalTax,
                ]);
            }

            return [$item, $invoice];
        });

        $bricklinkSyncQueued = false;
        if ($item->inventory_id) {
            SyncInventoryItemToBrickLinkJob::dispatch($item->inventory_id);
            $bricklinkSyncQueued = true;
        }

        if ($invoice) {
            try {
                $invoiceService->updateInvoiceAndRegeneratePDF($invoice, []);
            } catch (\Exception $e) {
                Log::error('Could not regenerate invoice after adding order item', [
                    'order_id' => $order->id,
                    'invoice_id' => $invoice->id,
                    'order_item_id' => $item->id,
                    'error' => $e->getMessage(),
                ]);

                return redirect()->back()->with('error', 'Der Artikel wurde ergänzt und der Bestand angepasst, aber die Rechnung konnte nicht neu erstellt werden.');
            }
        }

        ActivityLogger::info(
            'order.item.added',
            "Zusätzlicher Artikel {$item->item_number} zur Bestellung {$order->bricklink_order_id} hinzugefügt",
            $order,
            [
                'order_item_id' => $item->id,
                'inventory_id' => $item->inventory_id,
                'quantity' => $item->quantity,
                'bricklink_sync_queued' => $bricklinkSyncQueued,
            ]
        );

        $syncMessage = $bricklinkSyncQueued
            ? ' Die aktualisierte Menge wird mit BrickLink synchronisiert.'
            : ' Für diesen Artikel ist keine BrickLink-Inventar-ID hinterlegt; die Änderung gilt nur lokal.';

        return redirect()->back()->with(
            'success',
            $invoice
                ? 'Artikel ergänzt, Bestand reduziert und Rechnung aktualisiert.'.$syncMessage
                : 'Artikel ergänzt und Bestand reduziert. Die Rechnung berücksichtigt den Artikel bei der Erstellung.'.$syncMessage
        );
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
            $orderData = $this->brickLinkService->fetchOrder($order->store, $order->bricklink_order_id);

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
            $orders = $this->brickLinkService->fetchOrders($store, '');

            Log::info('Manual order sync started', [
                'store_id' => $store->id,
                'store_name' => $store->name,
                'total_orders_fetched' => count($orders),
            ]);

            $synced = 0;
            $skipped = 0;
            $failed = 0;

            foreach ($orders as $orderData) {
                // Skip orders older than 30 days for manual sync
                $orderDate = \Carbon\Carbon::parse($orderData['date_ordered']);
                if ($orderDate->lt(now()->subDays(30))) {
                    $skipped++;
                    Log::debug("Skipping old order {$orderData['order_id']}", [
                        'order_date' => $orderDate->toDateString(),
                        'days_old' => $orderDate->diffInDays(now()),
                    ]);
                    continue;
                }

                try {
                    // Fetch complete order details
                    $completeOrderData = $this->brickLinkService->fetchOrder($store, $orderData['order_id']);
                    $this->syncOrderData($store, $completeOrderData, $this->brickLinkService);
                    $synced++;

                    Log::debug("Synced order {$orderData['order_id']}", [
                        'order_date' => $orderDate->toDateString(),
                        'status' => $orderData['status'] ?? 'unknown',
                    ]);
                } catch (\Exception $e) {
                    $failed++;
                    Log::warning("Could not sync order {$orderData['order_id']}: {$e->getMessage()}", [
                        'order_id' => $orderData['order_id'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::info('Manual order sync completed', [
                'store_id' => $store->id,
                'total_fetched' => count($orders),
                'synced' => $synced,
                'skipped' => $skipped,
                'failed' => $failed,
            ]);

            $message = "Order synchronization completed. Synced {$synced} orders.";
            if ($skipped > 0) {
                $message .= " Skipped {$skipped} orders (older than 30 days).";
            }
            if ($failed > 0) {
                $message .= " {$failed} orders failed.";
            }

            return redirect()->back()->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Order sync failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
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
            $order->items()->where('is_manual', false)->delete();

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

        $updatedOrderData = $this->brickLinkService->updateOrderStatus($order->store, $order->bricklink_order_id, $validated['status']);

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

            // Prepare shipping data for BrickLink
            $shippingData = [
                'tracking_no' => $validated['tracking_number'],
            ];

            if (isset($validated['tracking_link'])) {
                $shippingData['tracking_link'] = $validated['tracking_link'];
            }

            // Update shipping info on BrickLink
            $this->brickLinkService->updateOrderShipping($order->store, $order->bricklink_order_id, $shippingData);

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

            // Update status to Shipped
            $this->brickLinkService->updateOrderStatus($order->store, $order->bricklink_order_id, 'Shipped');

            // Update shipping info if tracking number provided
            $shippingData = [
                'tracking_no' => $validated['tracking_number'],
            ];

            if (isset($validated['tracking_link'])) {
                $shippingData['tracking_link'] = $validated['tracking_link'];
            }

            $this->brickLinkService->updateOrderShipping($order->store, $order->bricklink_order_id, $shippingData);

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
