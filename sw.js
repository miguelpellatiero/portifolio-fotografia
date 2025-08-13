// Service Worker for Photographer Portfolio
const CACHE_NAME = 'photographer-portfolio-v1.1.0';
const urlsToCache = [
    './',
    './index.html',
    './styles.css',
    './script.js',
    './assets/hero-image.jpg',
    './assets/photographer.jpg',
    './assets/favicon.ico',
    './assets/placeholder.jpg',
    './manifest.json',
    // Portfolio images
    './assets/portfolio/wedding1.jpg',
    './assets/portfolio/portrait1.jpg',
    './assets/portfolio/landscape1.jpg',
    './assets/portfolio/event1.jpg'
];

// Network first for API calls, cache first for assets
const API_CACHE = 'api-cache-v1';
const ASSET_CACHE = 'assets-cache-v1';

// Install Service Worker
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then(cache => {
                console.log('Opened cache');
                return cache.addAll(urlsToCache);
            })
            .catch(err => {
                console.log('Cache failed:', err);
            })
    );
});

// Fetch requests with advanced caching strategies
self.addEventListener('fetch', event => {
    const { request } = event;
    const url = new URL(request.url);

    // API calls - Network first, cache fallback
    if (url.pathname.startsWith('/api/') || url.pathname.startsWith('/backend/')) {
        event.respondWith(networkFirstStrategy(request));
        return;
    }

    // Images - Cache first, network fallback
    if (request.destination === 'image') {
        event.respondWith(cacheFirstStrategy(request));
        return;
    }

    // HTML, CSS, JS - Stale while revalidate
    if (request.destination === 'document' || 
        request.destination === 'style' || 
        request.destination === 'script') {
        event.respondWith(staleWhileRevalidateStrategy(request));
        return;
    }

    // Default: Cache first
    event.respondWith(cacheFirstStrategy(request));
});

// Cache strategies
async function networkFirstStrategy(request) {
    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(API_CACHE);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        const cachedResponse = await caches.match(request);
        if (cachedResponse) {
            return cachedResponse;
        }
        throw error;
    }
}

async function cacheFirstStrategy(request) {
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
        return cachedResponse;
    }

    try {
        const networkResponse = await fetch(request);
        if (networkResponse.ok) {
            const cache = await caches.open(ASSET_CACHE);
            cache.put(request, networkResponse.clone());
        }
        return networkResponse;
    } catch (error) {
        // Return placeholder image for failed image requests
        if (request.destination === 'image') {
            return caches.match('./assets/placeholder.jpg');
        }
        throw error;
    }
}

async function staleWhileRevalidateStrategy(request) {
    const cachedResponse = await caches.match(request);
    
    const fetchPromise = fetch(request).then(networkResponse => {
        if (networkResponse.ok) {
            const cache = caches.open(CACHE_NAME);
            cache.then(c => c.put(request, networkResponse.clone()));
        }
        return networkResponse;
    });

    return cachedResponse || fetchPromise;
}

// Update Service Worker
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cacheName => {
                    if (cacheName !== CACHE_NAME) {
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
});
