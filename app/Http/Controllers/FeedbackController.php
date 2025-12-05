<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreFeedbackRequest;
use App\Models\Order;
use App\Services\FeedbackService;
use Illuminate\Http\RedirectResponse;

class FeedbackController extends Controller
{
    public function __construct(
        private FeedbackService $feedbackService
    ) {}

    /**
     * Sync feedback from BrickLink
     */
    public function sync(Order $order): RedirectResponse
    {
        try {
            $order->load('store');
            $this->feedbackService->syncFeedback($order);

            return redirect()
                ->back()
                ->with('success', 'Feedback erfolgreich synchronisiert.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Fehler beim Synchronisieren des Feedbacks: '.$e->getMessage());
        }
    }

    /**
     * Post feedback to BrickLink
     */
    public function store(StoreFeedbackRequest $request, Order $order): RedirectResponse
    {
        try {
            $order->load('store');
            $this->feedbackService->postFeedback(
                $order,
                $request->getRating(),
                $request->getComment()
            );

            return redirect()
                ->back()
                ->with('success', 'Feedback erfolgreich gesendet.');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Fehler beim Senden des Feedbacks: '.$e->getMessage());
        }
    }
}
