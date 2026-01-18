<?php

namespace App\Http\Controllers;

use App\Models\Order;
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

        // Dispatch job to queue
        \App\Jobs\SyncBrickLinkOrdersJob::dispatch($store, null, 7);

        return redirect()->back()->with('success', 'Order synchronization started in background');
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
