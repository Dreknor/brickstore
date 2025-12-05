<?php

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Http;

it('shows error when store is not found', function () {
    $this->artisan('bricklink:test', ['store_id' => 999])
        ->expectsOutput('Store with ID 999 not found!')
        ->assertExitCode(1);
});

it('shows warning when store has no credentials', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'bl_consumer_key' => null,
        'bl_consumer_secret' => null,
        'bl_token' => null,
        'bl_token_secret' => null,
    ]);

    $this->artisan('bricklink:test', ['store_id' => $store->id])
        ->expectsOutputToContain('⚠ Store has no BrickLink credentials configured')
        ->assertExitCode(0);
});

it('tests connection successfully with valid credentials', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Store',
        'bl_consumer_key' => 'test_consumer_key',
        'bl_consumer_secret' => 'test_consumer_secret',
        'bl_token' => 'test_token',
        'bl_token_secret' => 'test_token_secret',
    ]);

    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [
                [
                    'order_id' => '12345',
                    'status' => 'PAID',
                    'buyer_name' => 'Test Buyer',
                ],
            ],
        ], 200),
    ]);

    $this->artisan('bricklink:test', ['store_id' => $store->id])
        ->expectsOutputToContain('Testing BrickLink connection for store: Test Store')
        ->expectsOutputToContain('✓ BrickLink credentials found')
        ->expectsOutputToContain('✓ Successfully connected to BrickLink API')
        ->expectsOutputToContain('→ Found 1 orders')
        ->expectsOutputToContain('Order #12345: PAID - Test Buyer')
        ->assertExitCode(0);
});

it('handles API errors gracefully', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Store',
        'bl_consumer_key' => 'test_consumer_key',
        'bl_consumer_secret' => 'test_consumer_secret',
        'bl_token' => 'test_token',
        'bl_token_secret' => 'test_token_secret',
    ]);

    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 401,
                'description' => 'SIGNATURE_INVALID',
                'message' => 'Invalid signature',
            ],
        ], 200),
    ]);

    $this->artisan('bricklink:test', ['store_id' => $store->id])
        ->expectsOutputToContain('✗ BrickLink API Error:')
        ->expectsOutputToContain('authentication failed')
        ->assertExitCode(1);
});

it('shows no orders when API returns empty array', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'name' => 'Test Store',
        'bl_consumer_key' => 'test_consumer_key',
        'bl_consumer_secret' => 'test_consumer_secret',
        'bl_token' => 'test_token',
        'bl_token_secret' => 'test_token_secret',
    ]);

    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [],
        ], 200),
    ]);

    $this->artisan('bricklink:test', ['store_id' => $store->id])
        ->expectsOutputToContain('✓ Successfully connected to BrickLink API')
        ->expectsOutputToContain('→ Found 0 orders')
        ->assertExitCode(0);
});

it('tests all stores when no store_id is provided', function () {
    $user = User::factory()->create();

    $store1 = Store::factory()->create([
        'user_id' => $user->id,
        'name' => 'Store 1',
        'bl_consumer_key' => 'test_consumer_key',
        'bl_consumer_secret' => 'test_consumer_secret',
        'bl_token' => 'test_token',
        'bl_token_secret' => 'test_token_secret',
    ]);

    $store2 = Store::factory()->create([
        'user_id' => $user->id,
        'name' => 'Store 2',
        'bl_consumer_key' => 'test_consumer_key',
        'bl_consumer_secret' => 'test_consumer_secret',
        'bl_token' => 'test_token',
        'bl_token_secret' => 'test_token_secret',
    ]);

    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [],
        ], 200),
    ]);

    $this->artisan('bricklink:test')
        ->expectsOutputToContain('Testing BrickLink connection for store: Store 1')
        ->expectsOutputToContain('Testing BrickLink connection for store: Store 2')
        ->assertExitCode(0);
});

it('shows credential info in output', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'bl_consumer_key' => 'test_consumer_key_123',
        'bl_consumer_secret' => 'test_consumer_secret',
        'bl_token' => 'test_token_456',
        'bl_token_secret' => 'test_token_secret',
    ]);

    Http::fake([
        'api.bricklink.com/*' => Http::response([
            'meta' => [
                'code' => 200,
            ],
            'data' => [],
        ], 200),
    ]);

    $this->artisan('bricklink:test', ['store_id' => $store->id])
        ->expectsOutputToContain('→ Consumer Key: test_con...')
        ->expectsOutputToContain('→ Token: test_tok...')
        ->expectsOutputToContain('→ Consumer Secret Length:')
        ->expectsOutputToContain('→ Token Secret Length:')
        ->assertExitCode(0);
});
