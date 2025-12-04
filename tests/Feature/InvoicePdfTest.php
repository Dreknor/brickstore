<?php

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;

test('invoice pdf view can be rendered', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->create(['store_id' => $store->id]);
    OrderItem::factory()->count(3)->create(['order_id' => $order->id]);

    $invoice = Invoice::factory()->create([
        'store_id' => $store->id,
        'order_id' => $order->id,
    ]);

    $view = view('invoices.pdf', [
        'invoice' => $invoice,
        'store' => $store,
        'order' => $order,
        'items' => $order->items,
    ]);

    $html = $view->render();

    expect($html)->toContain('RECHNUNG');
    expect($html)->toContain($invoice->invoice_number);
    expect($html)->toContain($invoice->customer_name);
});

test('invoice pdf shows items correctly', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);
    $order = Order::factory()->create(['store_id' => $store->id]);

    $item = OrderItem::factory()->create([
        'order_id' => $order->id,
        'item_name' => 'Test Brick Item',
        'condition' => 'N',
    ]);

    $invoice = Invoice::factory()->create([
        'store_id' => $store->id,
        'order_id' => $order->id,
    ]);

    $view = view('invoices.pdf', [
        'invoice' => $invoice,
        'store' => $store,
        'order' => $order,
        'items' => $order->items,
    ]);

    $html = $view->render();

    expect($html)->toContain('Test Brick Item');
    expect($html)->toContain('Neu');
});

