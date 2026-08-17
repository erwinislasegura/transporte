'use strict';

const CACHE_PREFIX = 'bgv-enterprise-';
const CACHE_VERSION = 'pwa-20260817-v1';
const STATIC_CACHE = `${CACHE_PREFIX}${CACHE_VERSION}`;

const scopedUrl = (path) => new URL(path, self.registration.scope).href;
const STATIC_ASSETS = [
  'offline.html',
  'manifest.webmanifest',
  'assets/styles.css?v=20260817-pwa',
  'assets/app.js?v=20260817-pwa',
  'assets/icons/icon-192.png',
  'assets/icons/icon-512.png',
  'assets/icons/icon-maskable-512.png',
  'assets/icons/apple-touch-icon.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(STATIC_CACHE)
      .then((cache) => cache.addAll(STATIC_ASSETS.map(scopedUrl)))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(
        keys.filter((key) => key.startsWith(CACHE_PREFIX) && key !== STATIC_CACHE)
          .map((key) => caches.delete(key))
      ))
      .then(() => self.clients.claim())
  );
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  if (request.method !== 'GET') return;

  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(scopedUrl('offline.html')))
    );
    return;
  }

  const url = new URL(request.url);
  const isLocalStatic = url.origin === self.location.origin
    && (url.pathname.includes('/assets/') || url.pathname.endsWith('/manifest.webmanifest'));
  const isPublicCdn = ['cdn.jsdelivr.net', 'fonts.googleapis.com', 'fonts.gstatic.com'].includes(url.hostname);

  if (!isLocalStatic && !isPublicCdn) return;

  event.respondWith(
    caches.match(request).then((cached) => {
      const network = fetch(request).then((response) => {
        if (response.ok || response.type === 'opaque') {
          const copy = response.clone();
          caches.open(STATIC_CACHE).then((cache) => cache.put(request, copy));
        }
        return response;
      });
      return cached || network;
    })
  );
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') self.skipWaiting();
});
