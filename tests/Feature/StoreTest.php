<?php

use App\Models\Store;
use App\Models\User;

test('user can create store', function () {
    $user = User::factory()->create();

    $store = Store::factory()->create([
        'user_id' => $user->id,
    ]);

    expect($store)->toBeInstanceOf(Store::class)
        ->and($store->user_id)->toBe($user->id);
});

test('user can have one store', function () {
    $user = User::factory()->create();
    $store = Store::factory()->create(['user_id' => $user->id]);

    expect($user->store)->toBeInstanceOf(Store::class)
        ->and($user->store->id)->toBe($store->id);
});

test('user has store returns true when user has store', function () {
    $user = User::factory()->create();
    Store::factory()->create(['user_id' => $user->id]);

    expect($user->hasStore())->toBeTrue();
});

test('user has store returns false when user has no store', function () {
    $user = User::factory()->create();

    expect($user->hasStore())->toBeFalse();
});

test('store can have orders', function () {
    $store = Store::factory()
        ->hasOrders(3)
        ->create();

    expect($store->orders)->toHaveCount(3);
});

test('store encrypts sensitive credentials', function () {
    $store = Store::factory()
        ->withBrickLinkCredentials()
        ->create();

    expect($store->bl_consumer_key)->not->toBeNull()
        ->and($store->bl_consumer_secret)->not->toBeNull()
        ->and($store->bl_token)->not->toBeNull()
        ->and($store->bl_token_secret)->not->toBeNull();
});

test('store can check if it has bricklink credentials', function () {
    $storeWithCredentials = Store::factory()
        ->withBrickLinkCredentials()
        ->create();

    $storeWithoutCredentials = Store::factory()->create();

    expect($storeWithCredentials->hasBrickLinkCredentials())->toBeTrue()
        ->and($storeWithoutCredentials->hasBrickLinkCredentials())->toBeFalse();
});

test('store can check if it has smtp credentials', function () {
    $storeWithSmtp = Store::factory()
        ->withSmtpCredentials()
        ->create();

    $storeWithoutSmtp = Store::factory()->create();

    expect($storeWithSmtp->hasSmtpCredentials())->toBeTrue()
        ->and($storeWithoutSmtp->hasSmtpCredentials())->toBeFalse();
});

test('admin can view any store', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $store = Store::factory()->create();

    expect($admin->can('view', $store))->toBeTrue();
});

test('user can only view own store', function () {
    $user = User::factory()->create();
    $ownStore = Store::factory()->create(['user_id' => $user->id]);
    $otherStore = Store::factory()->create();

    expect($user->can('view', $ownStore))->toBeTrue()
        ->and($user->can('view', $otherStore))->toBeFalse();
});
