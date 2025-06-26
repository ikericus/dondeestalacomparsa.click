const CACHE_NAME = 'comparsa-v1.2';
const urlsToCache = [
  '/',
  '/index.php',
  '/script.js',
  '/translations.js',
  '/translations.json',
  '/manifest.json',
  '/icon.png',
  '/king.png',
  '/user.png',
  '/inicio.png',
  '/final.png',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
  'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
  'https://cdn.jsdelivr.net/npm/bulma@1.0.0/css/bulma.min.css',
  'https://tile.openstreetmap.org/15/16553/12091.png', // Tiles de ejemplo para Pamplona
  'https://tile.openstreetmap.org/15/16552/12091.png',
  'https://tile.openstreetmap.org/15/16554/12091.png'
];

// Instalación del SW
self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Cache abierto');
        return cache.addAll(urlsToCache);
      })
      .catch(error => {
        console.log('Error cacheando recursos:', error);
        // Continuar aunque falle el cache
        return Promise.resolve();
      })
  );
});

// Activación del SW
self.addEventListener('activate', event => {
  event.waitUntil(
    caches.keys().then(cacheNames => {
      return Promise.all(
        cacheNames.map(cacheName => {
          if (cacheName !== CACHE_NAME) {
            console.log('Borrando cache antiguo:', cacheName);
            return caches.delete(cacheName);
          }
        })
      );
    })
  );
});

// Estrategia de cache
self.addEventListener('fetch', event => {
  const { request } = event;
  const url = new URL(request.url);

  // Para las API calls, intentar primero la red
  if (url.pathname.includes('/api.php')) {
    event.respondWith(
      fetch(request)
        .then(response => {
          // Si la respuesta es ok, guardar en cache para uso offline
          if (response.ok) {
            const responseClone = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(request, responseClone);
            });
          }
          return response;
        })
        .catch(() => {
          // Si falla la red, intentar cache
          return caches.match(request);
        })
    );
    return;
  }

  // Para tiles de mapa, cache first
  if (url.hostname === 'tile.openstreetmap.org') {
    event.respondWith(
      caches.match(request)
        .then(response => {
          if (response) {
            return response;
          }
          return fetch(request).then(response => {
            if (response.ok) {
              const responseClone = response.clone();
              caches.open(CACHE_NAME).then(cache => {
                cache.put(request, responseClone);
              });
            }
            return response;
          });
        })
    );
    return;
  }

  // Para todo lo demás, cache first con fallback a red
  event.respondWith(
    caches.match(request)
      .then(response => {
        // Si está en cache, devolverlo
        if (response) {
          return response;
        }
        // Si no, ir a la red
        return fetch(request)
          .then(response => {
            // Si la respuesta es válida, guardarla en cache
            if (response.ok) {
              const responseClone = response.clone();
              caches.open(CACHE_NAME).then(cache => {
                cache.put(request, responseClone);
              });
            }
            return response;
          })
          .catch(() => {
            // Si todo falla, mostrar página offline básica
            if (request.destination === 'document') {
              return new Response(`
                <!DOCTYPE html>
                <html>
                <head>
                  <title>Sin conexión - Comparsa</title>
                  <meta charset="UTF-8">
                  <meta name="viewport" content="width=device-width, initial-scale=1">
                  <style>
                    body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                    .offline { color: #cc3333; }
                  </style>
                </head>
                <body>
                  <h1 class="offline">📱 Sin conexión</h1>
                  <p>No hay conexión a internet. Algunas funciones pueden no estar disponibles.</p>
                  <button onclick="window.location.reload()">Reintentar</button>
                </body>
                </html>
              `, {
                headers: { 'Content-Type': 'text/html' }
              });
            }
          });
      })
  );
});