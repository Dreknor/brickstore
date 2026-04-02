// BrickStore Service Worker – Push Notifications

self.addEventListener('push', function (event) {
    let data = {};

    if (event.data) {
        try {
            data = event.data.json();
        } catch (e) {
            data = {
                title: 'BrickStore',
                body: event.data.text(),
            };
        }
    }

    const title   = data.title  || 'BrickStore';
    const options = {
        body:    data.body  || 'Neue Benachrichtigung',
        icon:    data.icon  || '/favicon.ico',
        badge:   data.badge || '/favicon.ico',
        data: {
            url: data.url || '/',
        },
        requireInteraction: true,
        vibrate: [200, 100, 200],
    };

    event.waitUntil(
        self.registration.showNotification(title, options)
    );
});

self.addEventListener('notificationclick', function (event) {
    event.notification.close();

    const url = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (windowClients) {
            // Wenn schon ein Tab mit der App offen ist, fokussiere ihn
            for (const client of windowClients) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            // Sonst neuen Tab öffnen
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        })
    );
});

