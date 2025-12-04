<?php

use App\Models\Order;
use App\Models\Store;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

it('redirects to setup wizard when user has no store', function () {
    $user = User::factory()->create();

    actingAs($user)
        ->get(route('orders.index'))
        ->assertRedirect(route('store.setup-wizard'));
});

it('redirects to setup wizard when store is not set up', function () {
    $user = User::factory()->create();
    Store::factory()->create([
        'user_id' => $user->id,
        'is_setup_complete' => false,
    ]);

    actingAs($user)
        ->get(route('orders.index'))
        ->assertRedirect(route('store.setup-wizard'));
});

it('displays orders page when store is set up', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'is_setup_complete' => true,
    ]);

    actingAs($user)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('Bestellungen');
});

it('displays empty state when no orders exist', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'is_setup_complete' => true,
    ]);

    actingAs($user)
        ->get(route('orders.index'))
        ->assertOk()
        ->assertSee('Keine Bestellungen gefunden');
});

it('displays orders when they exist', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create([
        'user_id' => $user->id,
        'is_setup_complete' => true,
    ]);

    Order::factory()->count(3)->create([
        'store_id' => $store->id,
    ]);

    $response = actingAs($user)
        ->get(route('orders.index'));

    $response->assertOk();
    expect($response->getContent())->toContain('Bestellungen');
});

