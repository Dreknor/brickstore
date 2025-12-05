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

            Log::info('Syncing feedback for order', [
                'orderId' => $order->id,
                'bricklinkOrderId' => $order->bricklink_order_id,
                'feedbackData' => $feedbackData,
            ]);

            // Process "from" feedback (feedback from buyer)
            if (isset($feedbackData['from'])) {
                $this->syncFeedbackEntry($order, $feedbackData['from'], 'from_buyer');
            }

            // Process "to" feedback (feedback to buyer)
            if (isset($feedbackData['to'])) {
                $this->syncFeedbackEntry($order, $feedbackData['to'], 'to_buyer');
            }

            Log::info('Feedback synced successfully', [
                'orderId' => $order->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to sync feedback', [
                'orderId' => $order->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Sync a single feedback entry
     */
    private function syncFeedbackEntry(Order $order, array $data, string $direction): void
    {
        $feedbackData = [
            'order_id' => $order->id,
            'direction' => $direction,
            'rating' => $data['rating'] ?? null,
            'comment' => $data['comment'] ?? null,
            'rating_of_bs' => $data['rating_of_bs'] ?? null,
            'rating_of_td' => $data['rating_of_td'] ?? null,
            'rating_of_comm' => $data['rating_of_comm'] ?? null,
            'rating_of_ship' => $data['rating_of_ship'] ?? null,
            'rating_of_pack' => $data['rating_of_pack'] ?? null,
            'can_edit' => $data['can_edit'] ?? false,
            'can_reply' => $data['can_reply'] ?? false,
            'feedback_date' => isset($data['date_rated']) ? $data['date_rated'] : null,
        ];

        // Find or create feedback entry
        Feedback::updateOrCreate(
            [
                'order_id' => $order->id,
                'direction' => $direction,
            ],
            $feedbackData
        );
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
