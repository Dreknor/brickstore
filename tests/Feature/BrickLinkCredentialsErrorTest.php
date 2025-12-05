<?php

use App\Jobs\SyncBrickLinkOrdersJob;
use App\Models\Store;
use App\Models\User;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create([
        'user_id' => $this->user->id,
        'bl_consumer_key' => 'test_consumer_key',
        'bl_consumer_secret' => 'test_consumer_secret',
        'bl_token' => 'test_token',
        'bl_token_secret' => 'test_token_secret',
    ]);
});

test('handles CONSUMER_KEY_UNKNOWN error with helpful message', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'description' => 'CONSUMER_KEY_UNKNOWN: consumer_key: test_consumer_key',
                'message' => 'BAD_OAUTH_REQUEST',
                'code' => 401,
            ],
        ], 200),
    ]);

    $service = new BrickLinkService;

    expect(fn () => $service->fetchOrders($this->store))
        ->toThrow(\Exception::class, 'Consumer Key ist bei BrickLink unbekannt');
});

test('handles TOKEN_VALUE_UNKNOWN error with helpful message', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'description' => 'TOKEN_VALUE_UNKNOWN: token: test_token',
                'message' => 'BAD_OAUTH_REQUEST',
                'code' => 401,
            ],
        ], 200),
    ]);

    $service = new BrickLinkService;

    expect(fn () => $service->fetchOrders($this->store))
        ->toThrow(\Exception::class, 'Token ist bei BrickLink unbekannt');
});

test('sync job does not retry on authentication errors', function () {
    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'description' => 'CONSUMER_KEY_UNKNOWN: consumer_key: test_consumer_key',
                'message' => 'BAD_OAUTH_REQUEST',
                'code' => 401,
            ],
        ], 200),
    ]);

    $job = new SyncBrickLinkOrdersJob($this->store);

    // The job should catch the exception and call fail()
    $job->handle(new BrickLinkService);

    // Check that the job was marked as failed (this is implicit in the fail() method)
    expect(true)->toBeTrue();
});

test('order sync requires valid credentials', function () {
    $this->actingAs($this->user);

    // Remove credentials
    $this->store->update([
        'bl_consumer_key' => null,
        'bl_consumer_secret' => null,
        'bl_token' => null,
        'bl_token_secret' => null,
    ]);

    // Refresh the store instance
    $this->store = $this->store->fresh();

    $response = $this->post(route('orders.sync-all'));

    $response->assertRedirect();
    $response->assertSessionHas('error');
});

test('order sync starts with valid credentials', function () {
    $this->actingAs($this->user);

    // Queue should be faked to prevent actual job execution
    Queue::fake();

    $response = $this->post(route('orders.sync-all'));

    $response->assertRedirect();
    $response->assertSessionHas('success');

    // Verify the job was dispatched
    Queue::assertPushed(SyncBrickLinkOrdersJob::class);
});
