<?php

use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Support\Facades\Log;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\mock;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->withBrickLinkCredentials()->create([
        'user_id' => $this->user->id,
        'is_setup_complete' => true,
    ]);
    $this->order = Order::factory()->create([
        'store_id' => $this->store->id,
        'status' => 'Paid',
        'tracking_number' => null,
    ]);
});

it('can update shipping information with tracking number', function () {
    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderShipping')
        ->once()
        ->with($this->store, $this->order->bricklink_order_id, [
            'tracking_no' => 'TRACK123456',
        ])
        ->andReturn([]);

    actingAs($this->user)
        ->post(route('orders.update-shipping', $this->order), [
            'tracking_number' => 'TRACK123456',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Sendungsverfolgung erfolgreich aktualisiert');

    $this->order->refresh();
    expect($this->order->tracking_number)->toBe('TRACK123456');
});

it('can update shipping information with tracking number and link', function () {
    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderShipping')
        ->once()
        ->with($this->store, $this->order->bricklink_order_id, [
            'tracking_no' => 'TRACK123456',
            'tracking_link' => 'https://tracking.example.com/TRACK123456',
        ])
        ->andReturn([]);

    actingAs($this->user)
        ->post(route('orders.update-shipping', $this->order), [
            'tracking_number' => 'TRACK123456',
            'tracking_link' => 'https://tracking.example.com/TRACK123456',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->order->refresh();
    expect($this->order->tracking_number)->toBe('TRACK123456');
    expect($this->order->tracking_link)->toBe('https://tracking.example.com/TRACK123456');
});

it('validates tracking number is required', function () {
    actingAs($this->user)
        ->post(route('orders.update-shipping', $this->order), [
            'tracking_number' => '',
        ])
        ->assertSessionHasErrors('tracking_number');
});

it('validates tracking link is a valid url', function () {
    actingAs($this->user)
        ->post(route('orders.update-shipping', $this->order), [
            'tracking_number' => 'TRACK123',
            'tracking_link' => 'not-a-url',
        ])
        ->assertSessionHasErrors('tracking_link');
});

it('can mark order as shipped with tracking number', function () {
    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderStatus')
        ->once()
        ->with($this->store, $this->order->bricklink_order_id, 'Shipped')
        ->andReturn([]);

    $mockService->shouldReceive('updateOrderShipping')
        ->once()
        ->with($this->store, $this->order->bricklink_order_id, [
            'tracking_no' => 'TRACK123456',
        ])
        ->andReturn([]);

    actingAs($this->user)
        ->post(route('orders.ship', $this->order), [
            'tracking_number' => 'TRACK123456',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Bestellung erfolgreich als versendet markiert');

    $this->order->refresh();
    expect($this->order->status)->toBe('Shipped');
    expect($this->order->tracking_number)->toBe('TRACK123456');
    expect($this->order->shipped_date)->not->toBeNull();
    expect($this->order->date_status_changed)->not->toBeNull();
});

it('can mark order as shipped with tracking number and link', function () {
    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderStatus')->once()->andReturn([]);
    $mockService->shouldReceive('updateOrderShipping')
        ->once()
        ->with($this->store, $this->order->bricklink_order_id, [
            'tracking_no' => 'TRACK123456',
            'tracking_link' => 'https://tracking.example.com/TRACK123456',
        ])
        ->andReturn([]);

    actingAs($this->user)
        ->post(route('orders.ship', $this->order), [
            'tracking_number' => 'TRACK123456',
            'tracking_link' => 'https://tracking.example.com/TRACK123456',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->order->refresh();
    expect($this->order->status)->toBe('Shipped');
    expect($this->order->tracking_number)->toBe('TRACK123456');
    expect($this->order->tracking_link)->toBe('https://tracking.example.com/TRACK123456');
});

it('handles BrickLink API errors gracefully when updating shipping', function () {
    Log::shouldReceive('error')->once();

    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderShipping')
        ->once()
        ->andThrow(new \Exception('BrickLink API Error'));

    actingAs($this->user)
        ->post(route('orders.update-shipping', $this->order), [
            'tracking_number' => 'TRACK123456',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->order->refresh();
    expect($this->order->tracking_number)->toBeNull();
});

it('handles BrickLink API errors gracefully when marking as shipped', function () {
    Log::shouldReceive('error')->once();

    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderStatus')
        ->once()
        ->andThrow(new \Exception('BrickLink API Error'));

    actingAs($this->user)
        ->post(route('orders.ship', $this->order), [
            'tracking_number' => 'TRACK123456',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->order->refresh();
    expect($this->order->status)->toBe('Paid');
});

it('requires authorization to update shipping', function () {
    $otherUser = User::factory()->create();

    actingAs($otherUser)
        ->post(route('orders.update-shipping', $this->order), [
            'tracking_number' => 'TRACK123456',
        ])
        ->assertForbidden();
});

it('requires authorization to mark as shipped', function () {
    $otherUser = User::factory()->create();

    actingAs($otherUser)
        ->post(route('orders.ship', $this->order), [
            'tracking_number' => 'TRACK123456',
        ])
        ->assertForbidden();
});
