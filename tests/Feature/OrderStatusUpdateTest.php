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
        'status' => 'Pending',
    ]);
});

it('can update order status successfully', function () {
    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderStatus')
        ->once()
        ->with($this->store, $this->order->bricklink_order_id, 'Paid')
        ->andReturn([]);

    actingAs($this->user)
        ->post(route('orders.update-status', $this->order), [
            'status' => 'Paid',
        ])
        ->assertRedirect()
        ->assertSessionHas('success', 'Bestellstatus erfolgreich aktualisiert');

    $this->order->refresh();
    expect($this->order->status)->toBe('Paid');
    expect($this->order->date_status_changed)->not->toBeNull();
});

it('validates status is required', function () {
    actingAs($this->user)
        ->post(route('orders.update-status', $this->order), [
            'status' => '',
        ])
        ->assertSessionHasErrors('status');
});

it('validates status is valid', function () {
    actingAs($this->user)
        ->post(route('orders.update-status', $this->order), [
            'status' => 'InvalidStatus',
        ])
        ->assertSessionHasErrors('status');
});

it('accepts all valid BrickLink status values', function (string $status) {
    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderStatus')
        ->once()
        ->andReturn([]);

    actingAs($this->user)
        ->post(route('orders.update-status', $this->order), [
            'status' => $status,
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $this->order->refresh();
    expect($this->order->status)->toBe($status);
})->with([
    'Pending',
    'Updated',
    'Processing',
    'Ready',
    'Paid',
    'Packed',
    'Shipped',
    'Received',
    'Completed',
    'Cancelled',
    'Purged',
    'NPB',
    'NPX',
]);

it('handles BrickLink API errors gracefully', function () {
    Log::shouldReceive('error')->once();

    $mockService = mock(BrickLinkService::class);
    $mockService->shouldReceive('updateOrderStatus')
        ->once()
        ->andThrow(new \Exception('BrickLink API Error'));

    actingAs($this->user)
        ->post(route('orders.update-status', $this->order), [
            'status' => 'Paid',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->order->refresh();
    expect($this->order->status)->toBe('Pending');
});

it('requires authorization to update status', function () {
    $otherUser = User::factory()->create();

    actingAs($otherUser)
        ->post(route('orders.update-status', $this->order), [
            'status' => 'Paid',
        ])
        ->assertForbidden();
});
