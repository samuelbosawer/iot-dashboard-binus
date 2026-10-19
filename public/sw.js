/* Taman Pintar IoT service worker: offline shell + asset caching. Bump VERSION to drop old caches. */
const VERSION = 'v1';
const STATIC_CACHE = `taman-pintar-static-${VERSION}`;
const PAGE_CACHE = `taman-pintar-pages-${VERSION}`;
const OFFLINE_URL = '/offline.html';

const PRECACHE = [
    OFFLINE_URL,
    '/favicon.svg',
    '/icon-192.png',
    '/icon-512.png',
    '/icon-maskable-192.png',
    '/apple-touch-icon.png',
];

/** Public pages whose last copy may be shown while offline. */
const CACHEABLE_PAGES = ['/', '/prediksi'];

/** Versioned third-party assets (Font Awesome, Chart.js). */
const CDN_HOSTS = ['cdnjs.cloudflare.com'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((key) => ![STATIC_CACHE, PAGE_CACHE].includes(key)).map((key) => caches.delete(key))))
            .then(() => self.clients.claim()),
    );
});

const cacheFirst = async (request) => {
    const cached = await caches.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (response.ok || response.type === 'opaque') {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, response.clone());
    }
    return response;
};

const networkFirstPage = async (request, url) => {
    try {
        const response = await fetch(request);
        if (response.ok && CACHEABLE_PAGES.includes(url.pathname)) {
            const cache = await caches.open(PAGE_CACHE);
            cache.put(url.pathname, response.clone());
        }
        return response;
    } catch (error) {
        return (await caches.match(url.pathname)) || caches.match(OFFLINE_URL);
    }
};

self.addEventListener('fetch', (event) => {
    const { request } = event;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);
    const isSameOrigin = url.origin === self.location.origin;

    if (request.mode === 'navigate' && isSameOrigin) {
        event.respondWith(networkFirstPage(request, url));
        return;
    }

    // Live sensor data, CSV downloads and admin pages always go to the network.
    if (isSameOrigin && (url.pathname.startsWith('/build/') || PRECACHE.includes(url.pathname))) {
        event.respondWith(cacheFirst(request));
        return;
    }

    if (CDN_HOSTS.includes(url.hostname)) {
        event.respondWith(cacheFirst(request));
    }
});
