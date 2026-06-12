/* Tafakari Digital Hub — Service Worker v1 */
'use strict';

const CACHE_NAME  = 'tafakari-v1';
const OFFLINE_URL = '/offline';

const PRECACHE = [
  '/',
  '/offline',
  '/public/crtp-logo.jpg',
  '/pwa-icon.php?size=192',
];

// ── Install: pre-cache shell ───────────────────────────────────────────────
self.addEventListener('install', function(e) {
  e.waitUntil(
    caches.open(CACHE_NAME).then(function(cache) {
      return Promise.allSettled(
        PRECACHE.map(url => cache.add(url).catch(() => null))
      );
    })
  );
  self.skipWaiting();
});

// ── Activate: delete old caches ────────────────────────────────────────────
self.addEventListener('activate', function(e) {
  e.waitUntil(
    caches.keys().then(function(keys) {
      return Promise.all(
        keys.filter(k => k !== CACHE_NAME).map(k => caches.delete(k))
      );
    })
  );
  self.clients.claim();
});

// ── Fetch strategy ─────────────────────────────────────────────────────────
self.addEventListener('fetch', function(e) {
  var req = e.request;
  if (req.method !== 'GET') return;

  var url = new URL(req.url);

  // Only handle same-origin requests
  if (url.origin !== self.location.origin) return;

  // Skip admin routes — always network only
  if (url.pathname.startsWith('/admin') || url.pathname.startsWith('/api')) return;

  var isStaticAsset = (
    url.pathname.startsWith('/public/') ||
    url.pathname.startsWith('/pwa-icon') ||
    /\.(png|jpg|jpeg|gif|svg|ico|webp|woff2?|ttf|otf)(\?.*)?$/.test(url.pathname)
  );

  if (isStaticAsset) {
    // Cache-first: serve from cache, update in background
    e.respondWith(
      caches.match(req).then(function(cached) {
        var networkFetch = fetch(req).then(function(res) {
          if (res && res.ok) {
            caches.open(CACHE_NAME).then(function(c) { c.put(req, res.clone()); });
          }
          return res;
        });
        return cached || networkFetch;
      })
    );
  } else {
    // Network-first: try network, fall back to cache, then offline page
    e.respondWith(
      fetch(req)
        .then(function(res) {
          if (res && res.ok) {
            var clone = res.clone();
            caches.open(CACHE_NAME).then(function(c) { c.put(req, clone); });
          }
          return res;
        })
        .catch(function() {
          return caches.match(req).then(function(cached) {
            if (cached) return cached;
            // For navigation requests, serve offline page
            if (req.mode === 'navigate') return caches.match(OFFLINE_URL);
            return new Response('Offline', { status: 503, statusText: 'Service Unavailable' });
          });
        })
    );
  }
});

// ── Background sync (future) ───────────────────────────────────────────────
self.addEventListener('message', function(e) {
  if (e.data && e.data.type === 'SKIP_WAITING') self.skipWaiting();
});
