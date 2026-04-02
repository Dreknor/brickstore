/**
 * BrickStore – Web Push Notification Manager
 *
 * Registriert den Service Worker und abonniert/kündigt Push-Benachrichtigungen.
 * Wird im globalen App-Layout geladen.
 */

const PUSH_SUBSCRIBE_URL   = '/push/subscribe';
const PUSH_UNSUBSCRIBE_URL = '/push/unsubscribe';

/**
 * Konvertiert einen URL-safe Base64-String in ein Uint8Array (für VAPID applicationServerKey).
 */
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}

/**
 * Liest das CSRF-Token aus dem Meta-Tag.
 */
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

/**
 * Sendet die Subscription an den Laravel-Backend-Endpunkt.
 */
async function sendSubscriptionToServer(subscription, url) {
    const subJson = subscription.toJSON();
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept': 'application/json',
        },
        body: JSON.stringify({
            endpoint: subJson.endpoint,
            keys: {
                p256dh: subJson.keys?.p256dh,
                auth:   subJson.keys?.auth,
            },
        }),
    });

    if (!response.ok) {
        throw new Error(`Server antwortete mit ${response.status}`);
    }

    return response.json();
}

/**
 * Gibt den aktuellen Abonnementstatus zurück.
 */
async function getPushSubscriptionStatus(registration) {
    const existing = await registration.pushManager.getSubscription();
    return existing !== null;
}

/**
 * Abonniert Push-Benachrichtigungen.
 */
async function subscribeToPush(registration, vapidPublicKey) {
    const subscription = await registration.pushManager.subscribe({
        userVisibleOnly:      true,
        applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
    });

    await sendSubscriptionToServer(subscription, PUSH_SUBSCRIBE_URL);
    return subscription;
}

/**
 * Kündigt Push-Benachrichtigungen.
 */
async function unsubscribeFromPush(registration) {
    const subscription = await registration.pushManager.getSubscription();
    if (!subscription) return;

    await sendSubscriptionToServer(subscription, PUSH_UNSUBSCRIBE_URL);
    await subscription.unsubscribe();
}

/**
 * Aktualisiert alle Push-Toggle-Buttons im DOM.
 */
function updatePushButtons(isSubscribed) {
    document.querySelectorAll('[data-push-toggle]').forEach((btn) => {
        btn.dataset.pushActive = isSubscribed ? '1' : '0';
        btn.setAttribute('aria-pressed', String(isSubscribed));

        const labelOn  = btn.querySelector('[data-push-label-on]');
        const labelOff = btn.querySelector('[data-push-label-off]');

        if (labelOn)  labelOn.classList.toggle('hidden', !isSubscribed);
        if (labelOff) labelOff.classList.toggle('hidden', isSubscribed);
    });
}

/**
 * Hauptinitialisierung – wird beim DOMContentLoaded aufgerufen.
 */
async function initPushNotifications() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        // Browser unterstützt Push nicht
        console.info('[Push] Browser unterstützt Web Push nicht.');
        return;
    }

    const vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content');
    if (!vapidPublicKey) {
        console.warn('[Push] Kein VAPID Public Key gefunden.');
        return;
    }

    let registration;
    try {
        registration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });
        await navigator.serviceWorker.ready;
    } catch (err) {
        console.error('[Push] Service Worker Registrierung fehlgeschlagen:', err);
        return;
    }

    // Aktuellen Status ermitteln und Buttons aktualisieren
    const isSubscribed = await getPushSubscriptionStatus(registration);
    updatePushButtons(isSubscribed);

    // Click-Handler für alle Toggle-Buttons
    document.querySelectorAll('[data-push-toggle]').forEach((btn) => {
        btn.addEventListener('click', async () => {
            // Notification-Erlaubnis einholen falls nötig
            if (Notification.permission === 'denied') {
                alert('Push-Benachrichtigungen sind im Browser blockiert. Bitte erlaube sie in den Browser-Einstellungen.');
                return;
            }

            if (Notification.permission === 'default') {
                const permission = await Notification.requestPermission();
                if (permission !== 'granted') return;
            }

            btn.disabled = true;
            try {
                const active = btn.dataset.pushActive === '1';
                if (active) {
                    await unsubscribeFromPush(registration);
                    updatePushButtons(false);
                } else {
                    await subscribeToPush(registration, vapidPublicKey);
                    updatePushButtons(true);
                }
            } catch (err) {
                console.error('[Push] Fehler beim Umschalten:', err);
                alert('Fehler beim Verwalten der Push-Benachrichtigungen: ' + err.message);
            } finally {
                btn.disabled = false;
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', initPushNotifications);

