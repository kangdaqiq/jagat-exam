const CACHE_NAME = 'examuna-pengawas-pwa-v2';
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

    if (req.method !== 'GET') {
        return;
    }

    if (req.cache === 'only-if-cached' && req.mode !== 'same-origin') {
        return;
    }

    const url = new URL(req.url);

    // Abaikan request lintas domain atau non-http(s)
    if (url.origin !== self.location.origin) {
        return;
    }

    // Abaikan request Livewire / API agar selalu real-time
    if (url.pathname.startsWith('/livewire')) {
        return;
    }

    // Strategi Cache-First untuk aset statis (gambar, font, css, js, manifest)
    if (
        url.pathname === '/manifest-pengawas.json' ||
        url.pathname.startsWith('/images/') ||
        url.pathname.startsWith('/build/') ||
        url.pathname.startsWith('/fonts/') ||
        url.pathname.startsWith('/vendor/')
    ) {
        event.respondWith(
            caches.match(req).then((cached) => {
                if (cached) {
                    return cached;
                }
                return fetch(req)
                    .then((res) => {
                        if (res && res.ok && res.type === 'basic') {
                            const clone = res.clone();
                            caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
                        }
                        return res;
                    })
                    .catch(() => Response.error());
            })
        );
        return;
    }

    // Hanya tangani halaman /pengawas agar rute lain tidak tersangkut cache pengawas
    if (!url.pathname.startsWith('/pengawas')) {
        return;
    }

    // Strategi Network-First untuk halaman /pengawas dengan fallback Response yang aman
    event.respondWith(
        fetch(req)
            .then((res) => {
                if (res && res.ok && res.type === 'basic') {
                    const clone = res.clone();
                    caches.open(CACHE_NAME).then((cache) => cache.put(req, clone));
                }
                return res;
            })
            .catch(async () => {
                const cached = await caches.match(req);
                if (cached) {
                    return cached;
                }
                return new Response(
                    '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Koneksi Terputus</title></head><body style="font-family:sans-serif;padding:2rem;text-align:center;"><h2>Koneksi ke server terputus</h2><p>Periksa jaringan Anda lalu muat ulang halaman.</p><button onclick="location.reload()" style="padding:.6rem 1.2rem;cursor:pointer;">Muat Ulang</button></body></html>',
                    {
                        status: 503,
                        statusText: 'Service Unavailable',
                        headers: { 'Content-Type': 'text/html; charset=utf-8' }
                    }
                );
            })
    );
});

