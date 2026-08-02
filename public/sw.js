const VERSION = 'v4';
const SHELL_CACHE = `alas-shell-${VERSION}`;
const SHELL = [
  '/login',
  '/offline.html',
  '/offline-sync.js',
  '/site.webmanifest',
  '/images/alas-logo.png',
  '/images/alas-logo-192.png',
  '/vendor/livewire/livewire.js',
];

const isCacheable = (response) => response && response.ok && response.type === 'basic';

async function cacheShell() {
  const cache = await caches.open(SHELL_CACHE);
  await Promise.all(SHELL.map(async (url) => {
    try {
      const response = await fetch(url, { credentials: 'same-origin' });
      if (isCacheable(response)) await cache.put(url, response);
    } catch (_) {
      // The install still succeeds when one optional asset cannot be reached.
    }
  }));
}

async function cacheStaticAssets(urls) {
  const cache = await caches.open(SHELL_CACHE);
  await Promise.all(urls.map(async (url) => {
    try {
      const response = await fetch(url, { credentials: 'same-origin' });
      if (isCacheable(response)) await cache.put(url, response);
    } catch (_) {
      // A missing optional asset must not prevent offline access.
    }
  }));
}

self.addEventListener('install', (event) => {
  event.waitUntil(cacheShell().then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.filter((key) => key.startsWith('alas-') && key !== SHELL_CACHE).map((key) => caches.delete(key)));
    await self.clients.claim();
  })());
});

self.addEventListener('message', (event) => {
  if (event.data?.type === 'CACHE_STATIC_ASSETS') {
    event.waitUntil(cacheStaticAssets(event.data.urls || []));
  }
});

self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  if (request.method !== 'GET' || url.origin !== self.location.origin) return;

  if (request.mode === 'navigate') {
    event.respondWith((async () => {
      try {
        // Laravel/Livewire documents contain short-lived CSRF and component
        // snapshots. Caching them causes "This page has expired" loops.
        return await fetch(request);
      } catch (_) {
        return await caches.match('/offline.html');
      }
    })());
    return;
  }

  event.respondWith((async () => {
    const cached = await caches.match(request, { ignoreSearch: url.pathname.startsWith('/vendor/livewire/') });
    if (cached) return cached;

    try {
      const response = await fetch(request);
      if (isCacheable(response)) (await caches.open(SHELL_CACHE)).put(request, response.clone());
      return response;
    } catch (_) {
      return new Response('', { status: 503, statusText: 'Offline' });
    }
  })());
});

self.addEventListener('push', (event) => {
  const payload = event.data?.json() || { title: 'Business Manager', body: 'You have a new notification.', url: '/' };
  event.waitUntil(self.registration.showNotification(payload.title, {
    body: payload.body,
    icon: payload.icon || '/images/alas-logo-192.png',
    badge: '/images/alas-logo-192.png',
    tag: payload.tag,
    data: { url: payload.url || '/' },
  }));
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil((async () => {
    const target = new URL(event.notification.data?.url || '/', self.location.origin).href;
    const clients = await self.clients.matchAll({ type: 'window', includeUncontrolled: true });
    const existing = clients.find((client) => client.url === target);
    if (existing) return existing.focus();
    return self.clients.openWindow(target);
  })());
});
