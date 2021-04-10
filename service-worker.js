// self.addEventListener('activate', e => {
// 	e.waitUntil(
// 		caches.keys().then(cacheNames => {
// 			return Promise.all(
// 				cacheNames.map(cacheName => caches.delete(cacheName))
// 			);
// 	  	})
// 	);
// });

// self.addEventListener('install', e => {
// 	e.waitUntil(
// 		caches.open('music-cache').then(cache => cache.addAll(['/'])),
// 	);
// });

// self.addEventListener('fetch', e => {
// 	e.respondWith(
// 		caches.match(e.request).then(response => response || fetch(e.request)),
// 	);
// });