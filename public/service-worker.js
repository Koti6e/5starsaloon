const CACHE_NAME = 'five-star-salon-static-v1';
const STATIC_ASSETS = [
  '/',
  '/offline.html',
  '/favicon.ico',
  '/images/brand/logo-small.webp',
  '/images/brand/logo-mark.webp',
  '/images/brand/logo-full.webp',
  '/images/salon/premium-salon-hero.webp'
];

self.addEventListener('install', (event) => {
  event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS)));
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.pathname.startsWith('/admin') || url.pathname.startsWith('/staff') || url.pathname.startsWith('/login')) {
    return;
  }

  event.respondWith(
    fetch(request)
      .then((response) => response)
      .catch(() => caches.match(request).then((cached) => cached || caches.match('/offline.html')))
  );
});
