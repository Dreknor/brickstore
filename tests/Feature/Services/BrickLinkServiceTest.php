<?php

use App\Models\Store;
use App\Models\User;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create([
        'user_id' => $this->user->id,
        'bl_consumer_key' => 'test_consumer_key',
        'bl_consumer_secret' => 'test_consumer_secret',
        'bl_token' => 'test_token',
        'bl_token_secret' => 'test_token_secret',
    ]);
    $this->service = new BrickLinkService;
});

it('throws exception when store credentials are incomplete', function () {
    $store = Store::factory()->create([
        'user_id' => $this->user->id,
        'bl_consumer_key' => null,
        'bl_consumer_secret' => null,
        'bl_token' => null,
        'bl_token_secret' => null,
    ]);

    expect(fn () => $this->service->fetchOrders($store))
        ->toThrow(\Exception::class, 'Incomplete BrickLink OAuth credentials');
});

it('fetches orders successfully', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                [
                    'order_id' => '12345',
                    'date_ordered' => '2025-12-04T10:00:00Z',
                    'buyer_name' => 'Test Buyer',
                    'status' => 'PAID',
                ],
            ],
        ], 200),
    ]);

    $orders = $this->service->fetchOrders($this->store);

    expect($orders)->toBeArray()
        ->and($orders)->toHaveCount(1)
        ->and($orders[0]['order_id'])->toBe('12345')
        ->and($orders[0]['buyer_name'])->toBe('Test Buyer');
});

it('fetches orders with status filter', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                [
                    'order_id' => '12345',
                    'status' => 'PAID',
                ],
            ],
        ], 200),
    ]);

    $orders = $this->service->fetchOrders($this->store, 'PAID');

    expect($orders)->toBeArray()
        ->and($orders)->toHaveCount(1);

    Http::assertSent(function ($request) {
        return str_contains($request->url(), 'status=PAID');
    });
});

it('fetches a single order by id', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                'order_id' => '12345',
                'buyer_name' => 'Test Buyer',
                'status' => 'PAID',
            ],
        ], 200),
    ]);

    $order = $this->service->fetchOrder($this->store, '12345');

    expect($order)->toBeArray()
        ->and($order['order_id'])->toBe('12345')
        ->and($order['buyer_name'])->toBe('Test Buyer');
});

it('fetches order items successfully', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [[
                [
                    'inventory_id' => '1',
                    'item' => [
                        'no' => '3001',
                        'type' => 'PART',
                    ],
                    'quantity' => 10,
                ],
                [
                    'inventory_id' => '2',
                    'item' => [
                        'no' => '3002',
                        'type' => 'PART',
                    ],
                    'quantity' => 5,
                ],
            ]],
        ], 200),
    ]);

    $items = $this->service->fetchOrderItems($this->store, '12345');

    expect($items)->toBeArray()
        ->and($items)->toHaveCount(2)
        ->and($items[0]['inventory_id'])->toBe('1')
        ->and($items[1]['inventory_id'])->toBe('2');
});

it('updates order status successfully', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                'order_id' => '12345',
                'status' => 'SHIPPED',
            ],
        ], 200),
    ]);

    $order = $this->service->updateOrderStatus($this->store, '12345', 'SHIPPED');

    expect($order)->toBeArray()
        ->and($order['status'])->toBe('SHIPPED');

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && str_contains($request->url(), 'orders/12345');
    });
});

it('handles 204 no content response when updating order status', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response('', 204),
    ]);

    $result = $this->service->updateOrderStatus($this->store, '12345', 'SHIPPED');

    // updateOrderStatus returns $response['data'] which is null for 204 responses
    // This gets coalesced to an empty array by the ?? [] operator
    expect($result)->toBeArray()
        ->and($result)->toBeEmpty();

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT'
            && str_contains($request->url(), 'orders/12345');
    });
});

it('updates order shipping information successfully', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                'order_id' => '12345',
                'shipping' => [
                    'tracking_no' => 'TRACK123',
                    'tracking_link' => 'https://tracking.example.com/TRACK123',
                ],
            ],
        ], 200),
    ]);

    $shippingData = [
        'tracking_no' => 'TRACK123',
        'tracking_link' => 'https://tracking.example.com/TRACK123',
    ];

    $order = $this->service->updateOrderShipping($this->store, '12345', $shippingData);

    expect($order)->toBeArray()
        ->and($order['shipping']['tracking_no'])->toBe('TRACK123');
});

it('fetches order feedback successfully', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                'from' => [
                    'rating' => 0,
                    'comment' => 'Great seller!',
                ],
                'to' => [
                    'rating' => 0,
                    'comment' => 'Thank you!',
                ],
            ],
        ], 200),
    ]);

    $feedback = $this->service->fetchOrderFeedback($this->store, '12345');

    expect($feedback)->toBeArray()
        ->and($feedback)->toHaveKey('from')
        ->and($feedback)->toHaveKey('to')
        ->and($feedback['from']['comment'])->toBe('Great seller!');
});

it('posts order feedback successfully', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                'rating' => 0,
                'comment' => 'Great buyer!',
            ],
        ], 200),
    ]);

    $feedback = $this->service->postOrderFeedback($this->store, '12345', 0, 'Great buyer!');

    expect($feedback)->toBeArray()
        ->and($feedback['rating'])->toBe(0)
        ->and($feedback['comment'])->toBe('Great buyer!');

    Http::assertSent(function ($request) {
        return $request->method() === 'POST'
            && str_contains($request->url(), 'feedback');
    });
});

it('sends order message by appending to remarks', function () {
    // First request fetches existing order with remarks
    // Second request updates the order with new remarks
    Http::fake([
        'api.bricklink.com/*/orders/12345' => Http::sequence()
            ->push([
                'meta' => ['code' => 200],
                'data' => [
                    'order_id' => '12345',
                    'remarks' => 'Previous message',
                ],
            ], 200)
            ->push([
                'meta' => ['code' => 200],
                'data' => [
                    'order_id' => '12345',
                    'remarks' => 'Previous message

--- 2025-12-04 14:00:00 ---
New message',
                ],
            ], 200),
    ]);

    $result = $this->service->sendOrderMessage($this->store, '12345', 'New message');

    expect($result)->toBeArray()
        ->and($result['remarks'])->toContain('Previous message')
        ->and($result['remarks'])->toContain('New message');

    Http::assertSent(function ($request) {
        return $request->method() === 'PUT';
    });
});

it('fetches colors successfully', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                [
                    'color_id' => 1,
                    'color_name' => 'White',
                    'color_code' => 'FFFFFF',
                ],
                [
                    'color_id' => 2,
                    'color_name' => 'Black',
                    'color_code' => '000000',
                ],
            ],
        ], 200),
    ]);

    $colors = $this->service->fetchColors($this->store);

    expect($colors)->toBeArray()
        ->and($colors)->toHaveCount(2)
        ->and($colors[0]['color_name'])->toBe('White');
});

it('fetches catalog item image successfully', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                'type' => 'PART',
                'no' => '3001',
                'thumbnail_url' => 'https://img.bricklink.com/ItemImage/PN/1/3001.png',
            ],
        ], 200),
    ]);

    $image = $this->service->fetchCatalogItemImage($this->store, 'PART', '3001', 1);

    expect($image)->toBeArray()
        ->and($image['thumbnail_url'])->toContain('3001.png');
});

it('handles rate limiting with retries', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::sequence()
            ->push([], 429)
            ->push([
                'meta' => ['code' => 200],
                'data' => [],
            ], 200),
    ]);

    $orders = $this->service->fetchOrders($this->store);

    expect($orders)->toBeArray();
});

it('throws exception on authentication failure', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 401,
                'description' => 'SIGNATURE_INVALID',
                'message' => 'Invalid signature',
            ],
        ], 200),
    ]);

    expect(fn () => $this->service->fetchOrders($this->store))
        ->toThrow(\Exception::class, 'authentication failed');
});

it('throws exception on 403 forbidden', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response('Forbidden', 403),
    ]);

    expect(fn () => $this->service->fetchOrders($this->store))
        ->toThrow(\Exception::class, '403 Forbidden');
});

it('parses remarks to messages correctly', function () {
    Http::fake([
        'api.bricklink.com/*/orders/12345/messages' => Http::response([
            'meta' => [
                'code' => 404,
                'description' => 'INVALID_URI',
            ],
        ], 200),
        'api.bricklink.com/*/orders/12345' => Http::response([
            'meta' => ['code' => 200],
            'data' => [
                'order_id' => '12345',
                'remarks' => '--- 2025-12-04 10:00:00 ---
First message

--- 2025-12-04 12:00:00 ---
Second message',
                'date_ordered' => '2025-12-04T09:00:00Z',
            ],
        ], 200),
    ]);

    $messages = $this->service->fetchOrderMessages($this->store, '12345');

    expect($messages)->toBeArray()
        ->and($messages)->toHaveCount(2)
        ->and($messages[0]['body'])->toBe('First message')
        ->and($messages[1]['body'])->toBe('Second message')
        ->and($messages[0]['date_sent'])->toBe('2025-12-04 10:00:00');
});

it('parses remarks without timestamps correctly', function () {
    Http::fake([
        'api.bricklink.com/*/orders/12345/messages' => Http::response([
            'meta' => [
                'code' => 404,
                'description' => 'INVALID_URI',
            ],
        ], 200),
        'api.bricklink.com/*/orders/12345' => Http::response([
            'meta' => ['code' => 200],
            'data' => [
                'order_id' => '12345',
                'remarks' => 'Simple remark without timestamp',
                'date_ordered' => '2025-12-04T09:00:00Z',
            ],
        ], 200),
    ]);

    $messages = $this->service->fetchOrderMessages($this->store, '12345');

    expect($messages)->toBeArray()
        ->and($messages)->toHaveCount(1)
        ->and($messages[0]['subject'])->toBe('Order Remarks')
        ->and($messages[0]['body'])->toBe('Simple remark without timestamp')
        ->and($messages[0]['from'])->toBe('seller');
});

it('handles empty remarks correctly', function () {
    Http::fake([
        'api.bricklink.com/*/orders/12345/messages' => Http::response([
            'meta' => [
                'code' => 404,
                'description' => 'INVALID_URI',
            ],
        ], 200),
        'api.bricklink.com/*/orders/12345' => Http::response([
            'meta' => ['code' => 200],
            'data' => [
                'order_id' => '12345',
                'remarks' => '',
                'date_ordered' => '2025-12-04T09:00:00Z',
            ],
        ], 200),
    ]);

    $messages = $this->service->fetchOrderMessages($this->store, '12345');

    expect($messages)->toBeArray()
        ->and($messages)->toBeEmpty();
});

it('fetches order items with empty data array', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [],
        ], 200),
    ]);

    $items = $this->service->fetchOrderItems($this->store, '12345');

    expect($items)->toBeArray()
        ->and($items)->toBeEmpty();
});

it('fetches orders with empty status filter', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [],
        ], 200),
    ]);

    $orders = $this->service->fetchOrders($this->store, '');

    expect($orders)->toBeArray();

    Http::assertSent(function ($request) {
        return ! str_contains($request->url(), 'status=');
    });
});

it('handles API error codes correctly', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 500,
                'description' => 'Internal Server Error',
                'message' => 'Something went wrong',
            ],
        ], 200),
    ]);

    expect(fn () => $this->service->fetchOrders($this->store))
        ->toThrow(\Exception::class, 'BrickLink API error (code 500)');
});

it('handles network errors with retries', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::sequence()
            ->push([], 500)
            ->push([], 500)
            ->push([
                'meta' => ['code' => 200],
                'data' => [],
            ], 200),
    ]);

    $orders = $this->service->fetchOrders($this->store);

    expect($orders)->toBeArray();
})->skip('Network retries on 5xx not implemented yet');

it('sends order message when no existing remarks', function () {
    Http::fake([
        'api.bricklink.com/*/orders/12345' => Http::sequence()
            ->push([
                'meta' => ['code' => 200],
                'data' => [
                    'order_id' => '12345',
                    'remarks' => '',
                ],
            ], 200)
            ->push([
                'meta' => ['code' => 200],
                'data' => [
                    'order_id' => '12345',
                    'remarks' => '--- 2025-12-04 14:00:00 ---
New message',
                ],
            ], 200),
    ]);

    $result = $this->service->sendOrderMessage($this->store, '12345', 'New message');

    expect($result)->toBeArray()
        ->and($result['remarks'])->toContain('New message')
        ->and($result['remarks'])->not->toContain('Previous message');
});

it('handles missing data in API response', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
        ], 200),
    ]);

    $orders = $this->service->fetchOrders($this->store);

    expect($orders)->toBeArray()
        ->and($orders)->toBeEmpty();
});

it('sends order message even when fetching existing remarks fails', function () {
    Http::fake([
        'api.bricklink.com/*/orders/12345' => Http::sequence()
            ->push([
                'meta' => [
                    'code' => 404,
                    'description' => 'NOT_FOUND',
                    'message' => 'Order not found',
                ],
            ], 200)
            ->push([
                'meta' => ['code' => 200],
                'data' => [
                    'order_id' => '12345',
                    'remarks' => '--- 2025-12-04 14:00:00 ---
New message',
                ],
            ], 200)
            ->whenEmpty(Http::response([
                'meta' => ['code' => 200],
                'data' => [
                    'order_id' => '12345',
                    'remarks' => '--- 2025-12-04 14:00:00 ---
New message',
                ],
            ], 200)),
    ]);

    $result = $this->service->sendOrderMessage($this->store, '12345', 'New message');

    expect($result)->toBeArray()
        ->and($result['remarks'])->toContain('New message');
});

it('handles catalog item image with color id zero', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                'type' => 'SET',
                'no' => '10294',
                'thumbnail_url' => 'https://img.bricklink.com/ItemImage/SN/0/10294.png',
            ],
        ], 200),
    ]);

    $image = $this->service->fetchCatalogItemImage($this->store, 'SET', '10294', 0);

    expect($image)->toBeArray()
        ->and($image['thumbnail_url'])->toContain('10294.png');
});

it('validates all required credentials are present', function () {
    $stores = [
        Store::factory()->create([
            'user_id' => $this->user->id,
            'bl_consumer_key' => '',
            'bl_consumer_secret' => 'secret',
            'bl_token' => 'token',
            'bl_token_secret' => 'token_secret',
        ]),
        Store::factory()->create([
            'user_id' => $this->user->id,
            'bl_consumer_key' => 'key',
            'bl_consumer_secret' => '',
            'bl_token' => 'token',
            'bl_token_secret' => 'token_secret',
        ]),
        Store::factory()->create([
            'user_id' => $this->user->id,
            'bl_consumer_key' => 'key',
            'bl_consumer_secret' => 'secret',
            'bl_token' => '',
            'bl_token_secret' => 'token_secret',
        ]),
        Store::factory()->create([
            'user_id' => $this->user->id,
            'bl_consumer_key' => 'key',
            'bl_consumer_secret' => 'secret',
            'bl_token' => 'token',
            'bl_token_secret' => '',
        ]),
    ];

    foreach ($stores as $store) {
        expect(fn () => $this->service->fetchOrders($store))
            ->toThrow(\Exception::class, 'Incomplete BrickLink OAuth credentials');
    }
});
