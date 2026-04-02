<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PushSubscription;
use App\Models\Store;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Illuminate\Support\Facades\Log;

class PushNotificationService
{
    private WebPush $webPush;

    public function __construct()
    {
        $auth = [
            'VAPID' => [
                'subject'    => config('services.webpush.vapid_subject'),
                'publicKey'  => config('services.webpush.vapid_public_key'),
                'privateKey' => config('services.webpush.vapid_private_key'),
            ],
        ];

        $this->webPush = new WebPush($auth);
        $this->webPush->setReuseVAPIDHeaders(true);
    }

    /**
     * Sendet Push-Benachrichtigung an alle Abonnenten des Store-Besitzers bei neuer Bestellung.
     */
    public function notifyNewOrder(Store $store, Order $order): void
    {
        $user = $store->user;
        if (! $user) {
            return;
        }

        $subscriptions = PushSubscription::where('user_id', $user->id)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title'  => '🛒 Neue Bestellung eingegangen!',
            'body'   => sprintf(
                'Bestellung #%s von %s – %s %s',
                $order->bricklink_order_id,
                $order->buyer_name ?: $order->buyer_username ?: 'Unbekannt',
                number_format((float) $order->grand_total, 2, ',', '.'),
                $order->currency_code ?? 'EUR'
            ),
            'url'    => url('/orders/' . $order->id),
            'icon'   => url('/favicon.ico'),
            'badge'  => url('/favicon.ico'),
        ]);

        $invalidEndpoints = [];

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint'        => $sub->endpoint,
                'keys'            => [
                    'p256dh' => $sub->p256dh,
                    'auth'   => $sub->auth,
                ],
            ]);

            $this->webPush->queueNotification($subscription, $payload);
        }

        foreach ($this->webPush->flush() as $report) {
            $endpoint = $report->getRequest()->getUri()->__toString();

            if (! $report->isSuccess()) {
                Log::warning('Push-Benachrichtigung fehlgeschlagen', [
                    'endpoint'   => $endpoint,
                    'reason'     => $report->getReason(),
                    'statusCode' => $report->getResponse()?->getStatusCode(),
                ]);

                // Ungültige/abgelaufene Subscriptions entfernen
                if ($report->isSubscriptionExpired()) {
                    $invalidEndpoints[] = $endpoint;
                }
            }
        }

        if (! empty($invalidEndpoints)) {
            PushSubscription::whereIn('endpoint', $invalidEndpoints)->delete();
            Log::info('Abgelaufene Push-Subscriptions entfernt', ['count' => count($invalidEndpoints)]);
        }
    }
}

