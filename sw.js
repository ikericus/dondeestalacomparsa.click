const CACHE_NAME = 'comparsa-minimal-v1.0';

// Instalación: sin pre-cache
self.addEventListener('install', event => {
  console.log('SW: Instalando (sin pre-cache)...');
  event.waitUntil(self.skipWaiting());
});

// Activación: limpiar caches antiguos
self.addEventListener('activate', event => {
  console.log('SW: Activando...');
  event.waitUntil(self.clients.claim());
});

// Fetch: cache on demand
self.addEventListener('fetch', event => {
  const { request } = event;
  
  // Solo GET
  if (request.method !== 'GET') {
    return;
  }
  
  const url = new URL(request.url);
  
  // Solo cachear recursos estáticos
  if (url.pathname.endsWith('.js') || 
      url.pathname.endsWith('.css') || 
      url.pathname.endsWith('.json') ||
      url.pathname.endsWith('.png')) {
    
    event.respondWith(
      caches.open(CACHE_NAME).then(cache => {
        return cache.match(request).then(response => {
          if (response) {
            console.log('SW: Desde cache:', request.url);
            return response;
          }
          
          console.log('SW: Descargando:', request.url);
          return fetch(request).then(fetchResponse => {
            if (fetchResponse.ok) {
              cache.put(request, fetchResponse.clone());
            }
            return fetchResponse;
          });
        });
      })
    );
    return;
  }
  
  // Todo lo demás: directo a la red
  event.respondWith(fetch(request));
});