// Lets an already-opened screen keep playing offline — play is plain Alpine, no Livewire round-trip.
const CACHE = 'soundboard-v1';

const CACHE_FIRST_PATHS = ['/build/', '/storage/', '/icons/'];

function isCacheFirst(url) {
    return CACHE_FIRST_PATHS.some((path) => url.pathname.startsWith(path))
        || url.pathname === '/favicon.svg';
}

self.addEventListener('install', () => {
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(
            keys.filter((key) => key !== CACHE).map((key) => caches.delete(key)),
        )).then(() => self.clients.claim()),
    );
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') {
        return;
    }

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (isCacheFirst(url)) {
        event.respondWith(
            caches.match(request).then((cached) => cached || fetch(request).then((response) => {
                if (response.ok) {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                }

                return response;
            })),
        );

        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).then((response) => {
                const copy = response.clone();
                caches.open(CACHE).then((cache) => cache.put(request, copy));

                return response;
            }).catch(() => caches.match(request).then((cached) => cached || caches.match('/dashboard'))),
        );
    }
});
