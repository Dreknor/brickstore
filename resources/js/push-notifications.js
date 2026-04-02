/**
 * BrickStore – Web Push Notification Manager
 */

const PUSH_SUBSCRIBE_URL   = '/push/subscribe';
const PUSH_UNSUBSCRIBE_URL = '/push/unsubscribe';

/** Zustand */
let _swRegistration = null;   // wird asynchron befüllt
let _vapidPublicKey = null;

// ─────────────────────────────────────────────
// Hilfsfunktionen
// ─────────────────────────────────────────────

function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64  = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const rawData = atob(base64);
    return Uint8Array.from([...rawData].map((c) => c.charCodeAt(0)));
}

function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
}

function updatePushButtons(isSubscribed) {
    document.querySelectorAll('[data-push-toggle]').forEach((btn) => {
        btn.dataset.pushActive = isSubscribed ? '1' : '0';
        btn.setAttribute('aria-pressed', String(isSubscribed));

        btn.querySelector('[data-push-label-on]')?.classList.toggle('hidden', !isSubscribed);
        btn.querySelector('[data-push-label-off]')?.classList.toggle('hidden', isSubscribed);
    });
}

async function sendToServer(subscription, url) {
    const s = subscription.toJSON();
    const r = await fetch(url, {
        method:  'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            'Accept':       'application/json',
        },
        body: JSON.stringify({
            endpoint: s.endpoint,
            keys: { p256dh: s.keys?.p256dh, auth: s.keys?.auth },
        }),
    });
    if (!r.ok) throw new Error(`Server: ${r.status}`);
    return r.json();
}

/** Wartet darauf dass der SW bereit ist (max. 10 Sek.) */
async function getSwRegistration() {
    if (_swRegistration) return _swRegistration;

    const timeout = new Promise((_, reject) =>
        setTimeout(() => reject(new Error('SW-Timeout')), 10000)
    );
    const reg = await Promise.race([navigator.serviceWorker.ready, timeout]);
    _swRegistration = reg;
    return reg;
}

// ─────────────────────────────────────────────
// Click-Handler – sofort anhängen
// ─────────────────────────────────────────────

function attachClickHandlers() {
    document.querySelectorAll('[data-push-toggle]').forEach((btn) => {
        // Verhindert doppelte Registrierung (z. B. durch Turbo/Livewire)
        if (btn.dataset.pushHandlerAttached) return;
        btn.dataset.pushHandlerAttached = '1';

        btn.addEventListener('click', async () => {
            if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                alert('Dein Browser unterstützt leider keine Push-Benachrichtigungen.');
                return;
            }

            if (Notification.permission === 'denied') {
                alert('Push-Benachrichtigungen sind im Browser blockiert.\nBitte erlaube sie in den Browser-Einstellungen.');
                return;
            }

            btn.disabled = true;

            try {
                // Erlaubnis einholen (falls noch nicht geschehen)
                if (Notification.permission === 'default') {
                    const p = await Notification.requestPermission();
                    if (p !== 'granted') { btn.disabled = false; return; }
                }

                const reg = await getSwRegistration();

                if (btn.dataset.pushActive === '1') {
                    // Abonnement kündigen
                    const sub = await reg.pushManager.getSubscription();
                    if (sub) {
                        await sendToServer(sub, PUSH_UNSUBSCRIBE_URL);
                        await sub.unsubscribe();
                    }
                    updatePushButtons(false);
                } else {
                    // Neu abonnieren
                    const sub = await reg.pushManager.subscribe({
                        userVisibleOnly:      true,
                        applicationServerKey: urlBase64ToUint8Array(_vapidPublicKey),
                    });
                    await sendToServer(sub, PUSH_SUBSCRIBE_URL);
                    updatePushButtons(true);
                }
            } catch (err) {
                console.error('[Push] Fehler:', err);
                alert('Fehler bei den Push-Benachrichtigungen:\n' + err.message);
            } finally {
                btn.disabled = false;
            }
        });
    });
}

// ─────────────────────────────────────────────
// SW-Registrierung im Hintergrund
// ─────────────────────────────────────────────

async function registerServiceWorker() {
    try {
        _swRegistration = await navigator.serviceWorker.register('/sw.js', { scope: '/' });

        // Aktuellen Abonnementstatus ermitteln und Buttons aktualisieren
        const activeReg = await navigator.serviceWorker.ready;
        _swRegistration = activeReg;

        const existing = await activeReg.pushManager.getSubscription();
        updatePushButtons(existing !== null);
    } catch (err) {
        console.error('[Push] SW-Registrierung fehlgeschlagen:', err);
    }
}

// ─────────────────────────────────────────────
// Initialisierung
// ─────────────────────────────────────────────

function init() {
    if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
        console.info('[Push] Browser unterstützt Web Push nicht.');
        return;
    }

    _vapidPublicKey = document.querySelector('meta[name="vapid-public-key"]')?.getAttribute('content');
    if (!_vapidPublicKey) {
        console.warn('[Push] Kein VAPID Public Key gefunden (meta[name="vapid-public-key"]).');
        return;
    }

    // 1. Click-Handler SOFORT anhängen (kein Warten auf SW)
    attachClickHandlers();

    // 2. SW im Hintergrund registrieren + Status aktualisieren
    registerServiceWorker();
}

// ES-Module sind deferred → DOM ist beim Ausführen bereits bereit.
// Zur Sicherheit: readyState prüfen.
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}
