self.addEventListener('activate', e => {});
self.addEventListener('install', e => {});
self.addEventListener('fetch', e => {});
self.addEventListener('notificationclick', e => {
    e.preventDefault();
    e.notification.close();
    e.waitUntil(clients.matchAll({ type: "window" }).then(clients => {
		clients.forEach(client => client.postMessage(e.action));
    }));
});
