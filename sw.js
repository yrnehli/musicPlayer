self.addEventListener('install', e => {
	e.waitUntil(
		caches.open('music-store').then(cache => cache.addAll(['/'])),
	);
});

self.addEventListener('fetch', e => {
	e.respondWith(
		caches.match(e.request).then(response => response || fetch(e.request)),
	);
});