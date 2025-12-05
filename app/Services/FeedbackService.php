<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Feedback;
use App\Models\Order;
use App\Services\BrickLink\BrickLinkService;
use Illuminate\Support\Facades\Log;

class FeedbackService
{
    public function __construct(
        private BrickLinkService $brickLinkService
    ) {}

    /**
     * Sync feedback from BrickLink for a specific order
     */
    public function syncFeedback(Order $order): void
    {
        try {
            $feedbackData = $this->brickLinkService->fetchOrderFeedback($order->store, $order->bricklink_order_id);

            foreach ($feedbackData as $feedbackdata) {
                $feedback = Feedback::updateOrCreate(
                    [
                        'order_id' => $order->id,
                        'feedback_id' => $feedbackdata['feedback_id'],
                    ],
                    [
                        'rating' => $feedbackdata['rating'],
                        'comment' => $feedbackdata['comment'],
                        'reply' => $feedbackdata['reply'] ?? null,
                        'rating_of_bs' => $feedbackdata['rating_of_bs'] ?? null,
                        'date_rated' => $feedbackdata['date_rated'] ?? null,
                        'from' => $feedbackdata['from'] ?? null,
                        'to' => $feedbackdata['to'] ?? null,
                    ]
                );
            }



        } catch (\Exception $e) {
            Log::error('Failed to sync feedback', [
                'orderId' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }



    /**
     * Post feedback to BrickLink
     */
    public function postFeedback(Order $order, int $rating, string $comment): Feedback
    {
        try {
            Log::info('Posting feedback to BrickLink', [
                'orderId' => $order->id,
                'bricklinkOrderId' => $order->bricklink_order_id,
                'rating' => $rating,
                'storeId' => $order->store_id,
                'storeName' => $order->store?->name,
            ]);

            // Post to BrickLink API
            $response = $this->brickLinkService->postOrderFeedback(
                $order->store,
                $order->bricklink_order_id,
                $rating,
                $comment
            );

            // Create local feedback record
            $feedback = Feedback::create([
                'order_id' => $order->id,
                'direction' => 'to_buyer',
                'rating' => $rating,
                'comment' => $comment,
                'can_edit' => $response['can_edit'] ?? false,
                'can_reply' => $response['can_reply'] ?? false,
                'feedback_date' => now(),
            ]);

            Log::info('Feedback posted successfully', [
                'orderId' => $order->id,
                'feedbackId' => $feedback->id,
            ]);

            return $feedback;
        } catch (\Exception $e) {
            Log::error('Failed to post feedback', [
                'orderId' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
