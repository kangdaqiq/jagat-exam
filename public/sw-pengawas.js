const CACHE_NAME = 'examuna-pengawas-pwa-v1';
const STATIC_ASSETS = [
    '/manifest-pengawas.json',
    '/images/logo/examuna-logo.png',
    '/images/logo/examuna-logo-white.png',
    '/images/logo/examuna.png'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            return cache.addAll(STATIC_ASSETS);
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => {
            return Promise.all(
                keys
                    .filter((key) => key !== CACHE_NAME)
                    .map((key) => caches.delete(key))
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;

    // Hanya tangani GET request
    if (req.method !== 'GET') {
        return;
    }

    const url = new URL(req.url);

    // Abaikan request Livewire / API agar selalu real-time
    if (url.pathname.startsWith('/livewire')) {
        return;
    }

    // Strategi Cache-First untuk aset statis (gambar, font, css, js)
    if (
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/fonts/') ||
        url.pathname.startsWith('/vendor/')
    ) {
        event.respondWith(
            caches.match(req).then((cached) => {
                return (
                    cached ||
                    fetch(req).then((res) => {
                        const clone = res.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
                        return res;
                    })
                );
            })
        );
        return;
    }

    // Strategi Network-First untuk halaman HTML /pengawas
    event.respondWith(
        fetch(req)
            .then((res) => {
                const clone = res.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
                return res;
            })
            .catch(() => caches.match(req))
    );
});

