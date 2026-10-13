// Lets an already-opened screen keep playing offline — play is plain Alpine, no Livewire round-trip.
const CACHE = 'soundboard-v1';

const CACHE_FIRST_PATHS = ['/build/', '/storage/', '/icons/'];

// Episodes are huge and fetched with Range requests (which the Cache API can't store), so the
// browser streams them straight from the server and they are never available offline.
const VIDEO_PATH = /\.(mp4|webm|m4v)$/i;

function isCacheFirst(url) {
    if (VIDEO_PATH.test(url.pathname)) {
        return false;
    }

    return CACHE_FIRST_PATHS.some((path) => url.pathname.startsWith(path))
        || url.pathname === '/favicon.svg';
}

function isOfflinePlayablePage(url) {
    return /^\/screens\/\d+\/(play|sounds\/\d+\/play)$/.test(url.pathname);
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

    if (request.mode === 'navigate' && isOfflinePlayablePage(url)) {
        event.respondWith(
            fetch(request).then((response) => {
                // A login redirect or an error page must never stand in for the screen offline.
                if (response.ok && !response.redirected) {
                    const copy = response.clone();
                    caches.open(CACHE).then((cache) => cache.put(request, copy));
                }

                return response;
            }).catch(() => caches.match(request)),
        );
    }
});
