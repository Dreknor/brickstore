<?php

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\InvoiceService;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create(['user_id' => $this->user->id]);
});

test('invoice is created from order with shipping_name as customer name', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'buyer_name' => 'John Buyer',
        'shipping_name' => 'Jane Receiver',
        'buyer_email' => 'buyer@example.com',
        'shipping_address1' => 'Street 123',
        'shipping_city' => 'Berlin',
        'shipping_postal_code' => '10115',
        'shipping_country' => 'DE',
        'subtotal' => 100.00,
        'shipping_cost' => 10.00,
        'grand_total' => 110.00,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->customer_name)->toBe('Jane Receiver')
        ->and($invoice->customer_email)->toBe('buyer@example.com')
        ->and($invoice->customer_address1)->toBe('Street 123')
        ->and($invoice->customer_city)->toBe('Berlin')
        ->and($invoice->shipping_cost)->toBe('10.00');
});

test('invoice falls back to buyer_name when shipping_name is null', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'buyer_name' => 'John Buyer',
        'shipping_name' => null,
        'subtotal' => 100.00,
        'shipping_cost' => 5.00,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->customer_name)->toBe('John Buyer');
});

test('invoice uses vat_amount and vat_rate when available', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'subtotal' => 100.00,
        'shipping_cost' => 10.00,
        'vat_amount' => 20.90,
        'vat_rate' => 19.00,
        'tax' => 0,
        'grand_total' => 130.90,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->tax_amount)->toBe('20.90')
        ->and($invoice->tax_rate)->toBe('19.00');
});

test('invoice falls back to tax when vat_amount is zero', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'subtotal' => 100.00,
        'shipping_cost' => 10.00,
        'vat_amount' => 0,
        'vat_rate' => 0,
        'tax' => 15.00,
        'grand_total' => 125.00,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->tax_amount)->toBe('15.00');
});

test('invoice uses final_total when available', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'subtotal' => 100.00,
        'shipping_cost' => 10.00,
        'grand_total' => 110.00,
        'final_total' => 100.00, // After credit/coupon
        'credit' => 10.00,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->total)->toBe('100.00');
});

test('invoice falls back to grand_total when final_total is zero', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'subtotal' => 100.00,
        'shipping_cost' => 10.00,
        'grand_total' => 110.00,
        'final_total' => 0,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->total)->toBe('110.00');
});

test('invoice for small business has zero tax', function () {
    $this->store->update(['is_small_business' => true]);

    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'subtotal' => 100.00,
        'shipping_cost' => 10.00,
        'vat_amount' => 20.90,
        'vat_rate' => 19.00,
        'grand_total' => 110.00,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->tax_amount)->toBe('0.00')
        ->and($invoice->tax_rate)->toBe('0.00')
        ->and($invoice->is_small_business)->toBeTrue();
});

test('invoice calculates tax rate when not provided', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'subtotal' => 100.00,
        'shipping_cost' => 10.00,
        'tax' => 20.90, // 19% of 110.00
        'vat_amount' => 0,
        'vat_rate' => 0,
        'grand_total' => 130.90,
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->tax_rate)->toBe('19.00'); // Should calculate ~19%
});

test('invoice copies all shipping address fields', function () {
    $order = Order::factory()->create([
        'store_id' => $this->store->id,
        'shipping_name' => 'Test Customer',
        'shipping_address1' => 'Main Street 123',
        'shipping_address2' => 'Apartment 4B',
        'shipping_city' => 'Munich',
        'shipping_state' => 'Bavaria',
        'shipping_postal_code' => '80331',
        'shipping_country' => 'DE',
    ]);

    $service = app(InvoiceService::class);
    $invoice = $service->createInvoiceFromOrder($order);

    expect($invoice->customer_address1)->toBe('Main Street 123')
        ->and($invoice->customer_address2)->toBe('Apartment 4B')
        ->and($invoice->customer_city)->toBe('Munich')
        ->and($invoice->customer_state)->toBe('Bavaria')
        ->and($invoice->customer_postal_code)->toBe('80331')
        ->and($invoice->customer_country)->toBe('DE');
});
