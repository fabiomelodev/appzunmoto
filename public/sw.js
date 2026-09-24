// Service worker for browser push notifications (shifts + chat messages).
self.addEventListener('push', (event) => {
    let data = {};
    try {
        data = event.data ? event.data.json() : {};
    } catch (e) {
        data = { title: 'ZunMoto', body: event.data ? event.data.text() : '' };
    }

    const title = data.title || 'ZunMoto';
    const options = {
        body: data.body || '',
        icon: data.icon || '/assets/favicon.png',
        badge: '/assets/favicon.png',
        data: data.data || {},
        tag: data.tag,
        renotify: data.renotify,
        requireInteraction: data.requireInteraction,
    };

    // Skip the system notification when the user is already looking at the
    // page it points to (e.g. inside that chat) — the page updates live.
    const targetUrl = options.data && options.data.url;
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            const alreadyViewing = targetUrl && windowClients.some((client) => {
                if (client.visibilityState !== 'visible') return false;
                try {
                    return new URL(client.url).pathname === new URL(targetUrl).pathname;
                } catch (e) {
                    return false;
                }
            });

            if (!alreadyViewing) {
                return self.registration.showNotification(title, options);
            }
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if (client.url === url && 'focus' in client) {
                    return client.focus();
                }
            }
            if (clients.openWindow) {
                return clients.openWindow(url);
            }
        }),
    );
});
