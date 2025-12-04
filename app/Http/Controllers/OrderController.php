<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ActivityLogger;
use App\Services\BrickLink\BrickLinkService;
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

        $order->load('items', 'invoice');

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
            $service = new BrickLinkService($order->store);
            $orderData = $service->getOrder($order->bricklink_order_id);

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
            return redirect()->back()->with('error', 'No store configured');
        }

        // Dispatch job to queue
        \App\Jobs\SyncBrickLinkOrdersJob::dispatch($store, null, 7);

        return redirect()->back()->with('success', 'Order synchronization started in background');
    }

    /**
     * Update order status.
     */
    public function updateStatus(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $request->validate([
            'status' => 'required|string',
        ]);

        try {
            $service = new BrickLinkService($order->store);
            $service->updateOrderStatus($order->bricklink_order_id, $request->status);

            $order->update([
                'status' => $request->status,
            ]);

            return redirect()->back()->with('success', 'Order status updated successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to update order status: '.$e->getMessage());
        }
    }

    /**
     * Mark order as shipped.
     */
    public function ship(Request $request, Order $order)
    {
        Gate::authorize('update', $order);

        $request->validate([
            'tracking_number' => 'nullable|string',
        ]);

        $order->update([
            'status' => 'Shipped',
            'shipped_date' => now(),
            'tracking_number' => $request->tracking_number,
        ]);

        try {
            $service = new BrickLinkService($order->store);
            $service->updateOrderStatus($order->bricklink_order_id, 'Shipped');
        } catch (\Exception $e) {
            // Log error but don't fail the request
        }

        ActivityLogger::info('order.shipped', "Order {$order->bricklink_order_id} marked as shipped", $order);

        return redirect()->back()->with('success', 'Order marked as shipped');
    }
}
