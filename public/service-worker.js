try {
    importScripts('/firebase-messaging-sw.js');
} catch (error) {
    console.error('SalonOS background notifications could not be initialized.', error);
}

const CACHE_NAME = 'salonos-pwa-v2';
const STATIC_ASSETS = [
    '/offline.html',
    '/favicon.ico',
    '/images/brand/logo-small.webp',
    '/images/brand/logo-mark.webp',
    '/images/brand/logo-full.webp',
    '/images/brand/pwa-icon-192.png',
    '/images/brand/pwa-icon-512.png',
    '/images/salon/premium-salon-hero.webp',
];

self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(async cache => {
                await cache.addAll(STATIC_ASSETS);

                try {
                    const manifestResponse = await fetch('/build/manifest.json', { cache: 'no-store' });
                    if (! manifestResponse.ok) return;

                    const manifest = await manifestResponse.json();
                    const assets = Object.values(manifest)
                        .filter(entry => entry && typeof entry.file === 'string')
                        .map(entry => entry.file)
                        .filter(file => typeof file === 'string' && /^assets\/[a-z0-9_.-]*-[a-z0-9_-]{8,}\.(?:js|css|woff2?)$/i.test(file))
                        .map(file => `/build/${file}`);

                    await cache.addAll(assets);
                } catch (error) {
                    console.warn('SalonOS PWA could not pre-cache versioned assets.', error);
                }
            })
    );
    self.skipWaiting();
});

self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(keys => Promise.all(
            keys
                .filter(key => (key.startsWith('salonos-pwa-') && key !== CACHE_NAME) || key === 'five-star-salon-static-v1')
                .map(key => caches.delete(key))
        ))
    );
    self.clients.claim();
});

self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) return;
    if (url.pathname === '/service-worker.js' || url.pathname === '/firebase-messaging-sw.js') return;

    if (/^\/build\/assets\/[a-z0-9_.-]*-[a-z0-9_-]{8,}\.(?:js|css|woff2?)$/i.test(url.pathname)) {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(response => {
                if (response.ok) {
                    const copy = response.clone();
                    return caches.open(CACHE_NAME).then(cache => cache.put(request, copy)).then(() => response);
                }
                return response;
            }))
        );
        return;
    }

    if (STATIC_ASSETS.includes(url.pathname)) {
        event.respondWith(
            caches.match(request).then(cached => cached || fetch(request).then(response => {
                if (response.ok) {
                    const copy = response.clone();
                    return caches.open(CACHE_NAME).then(cache => cache.put(request, copy)).then(() => response);
                }
                return response;
            }))
        );
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html'))
        );
    }
});
