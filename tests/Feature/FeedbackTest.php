<?php

use App\Models\Feedback;
use App\Models\Order;
use App\Models\Store;
use App\Models\User;
use App\Services\BrickLink\BrickLinkService;
use App\Services\FeedbackService;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->store = Store::factory()->create([
        'user_id' => $this->user->id,
        'is_setup_complete' => true,
    ]);
    $this->order = Order::factory()->create([
        'store_id' => $this->store->id,
        'bricklink_order_id' => '12345',
        'status' => 'Shipped',
    ]);
});

it('can display feedback on order detail page', function () {
    $feedbackFrom = Feedback::factory()->create([
        'order_id' => $this->order->id,
        'direction' => 'from_buyer',
        'rating' => 0,
        'comment' => 'Great seller!',
    ]);

    actingAs($this->user)
        ->get(route('orders.show', $this->order))
        ->assertOk()
        ->assertSee('Bewertungen')
        ->assertSee('Great seller!');
});

it('can sync feedback from bricklink', function () {
    $mockService = $this->mock(BrickLinkService::class);
    $mockService->shouldReceive('fetchOrderFeedback')
        ->once()
        ->andReturn([
            'from' => [
                'rating' => 0,
                'comment' => 'Excellent transaction!',
                'rating_of_bs' => 'G',
                'can_edit' => false,
                'can_reply' => false,
            ],
        ]);

    $feedbackService = new FeedbackService($mockService);
    $feedbackService->syncFeedback($this->order);

    expect(Feedback::where('order_id', $this->order->id)->count())->toBe(1);
    expect(Feedback::where('order_id', $this->order->id)->first()->comment)
        ->toBe('Excellent transaction!');
});

it('can post feedback to bricklink', function () {
    $mockService = $this->mock(BrickLinkService::class);
    $mockService->shouldReceive('postOrderFeedback')
        ->once()
        ->andReturn([
            'can_edit' => false,
            'can_reply' => false,
        ]);

    $feedbackService = new FeedbackService($mockService);
    $feedback = $feedbackService->postFeedback($this->order, 0, 'Great buyer!');

    expect($feedback)->toBeInstanceOf(Feedback::class);
    expect($feedback->rating)->toBe(0);
    expect($feedback->comment)->toBe('Great buyer!');
    expect($feedback->direction)->toBe('to_buyer');
});

it('can submit feedback via form', function () {
    $mockService = $this->mock(BrickLinkService::class);
    $mockService->shouldReceive('postOrderFeedback')
        ->once()
        ->andReturn([
            'can_edit' => false,
            'can_reply' => false,
        ]);

    actingAs($this->user)
        ->post(route('orders.feedback.store', $this->order), [
            'rating' => 0,
            'comment' => 'Excellent buyer!',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Feedback::where('order_id', $this->order->id)->count())->toBe(1);
});

it('validates feedback form inputs', function () {
    $response = actingAs($this->user)
        ->post(route('orders.feedback.store', $this->order), [
            'rating' => 99, // Invalid rating
            'comment' => '',
        ]);

    $response->assertSessionHasErrors(['rating', 'comment']);
});

it('correctly converts string rating to integer', function () {
    $mockService = $this->mock(BrickLinkService::class);
    $mockService->shouldReceive('postOrderFeedback')
        ->once()
        ->andReturn([
            'can_edit' => false,
            'can_reply' => false,
        ]);

    actingAs($this->user)
        ->post(route('orders.feedback.store', $this->order), [
            'rating' => '0', // String input from form
            'comment' => 'Great buyer!',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    $feedback = Feedback::where('order_id', $this->order->id)->first();
    expect($feedback->rating)->toBe(0)
        ->and($feedback->rating)->toBeInt();
});

it('shows feedback form only when order is shipped and no feedback exists', function () {
    actingAs($this->user)
        ->get(route('orders.show', $this->order))
        ->assertOk()
        ->assertSee('Bewertung abgeben');

    // Create feedback
    Feedback::factory()->create([
        'order_id' => $this->order->id,
        'direction' => 'to_buyer',
    ]);

    actingAs($this->user)
        ->get(route('orders.show', $this->order))
        ->assertOk()
        ->assertDontSee('Bewertung abgeben');
});
