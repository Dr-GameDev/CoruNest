// Service Worker for CoruNest PWA
const CACHE_NAME = 'corunest-v1.0.0';
const OFFLINE_URL = '/offline';

// Files to cache for offline functionality
const STATIC_CACHE_FILES = [
  '/',
  '/campaigns',
  '/events',
  '/offline',
  '/manifest.json',
  // Add your CSS/JS files here
  '/build/assets/app.css',
  '/build/assets/app.js',
  // Icons
  '/icons/icon-192x192.png',
  '/icons/icon-512x512.png',
];

// API endpoints to cache
const API_CACHE_PATTERNS = [
  /^https:\/\/api\.corunest\.org\/campaigns/,
  /^https:\/\/api\.corunest\.org\/events/,
];

// Install event - cache static assets
self.addEventListener('install', event => {
  console.log('Service Worker: Installing...');
  
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        console.log('Service Worker: Caching static files');
        return cache.addAll(STATIC_CACHE_FILES);
      })
      .then(() => {
        console.log('Service Worker: Static files cached');
        // Force activation of new service worker
        return self.skipWaiting();
      })
      .catch(error => {
        console.error('Service Worker: Failed to cache static files', error);
      })
  );
});

// Activate event - clean up old caches
self.addEventListener('activate', event => {
  console.log('Service Worker: Activating...');
  
  event.waitUntil(
    caches.keys()
      .then(cacheNames => {
        return Promise.all(
          cacheNames.map(cacheName => {
            if (cacheName !== CACHE_NAME) {
              console.log('Service Worker: Deleting old cache', cacheName);
              return caches.delete(cacheName);
            }
          })
        );
      })
      .then(() => {
        console.log('Service Worker: Activated');
        // Take control of all clients
        return self.clients.claim();
      })
  );
});

// Fetch event - serve from cache when offline
self.addEventListener('fetch', event => {
  const request = event.request;
  const url = new URL(request.url);
  
  // Skip non-GET requests and chrome-extension requests
  if (request.method !== 'GET' || url.protocol === 'chrome-extension:') {
    return;
  }
  
  // Handle navigation requests
  if (request.mode === 'navigate') {
    event.respondWith(handleNavigationRequest(request));
    return;
  }
  
  // Handle API requests
  if (isApiRequest(request)) {
    event.respondWith(handleApiRequest(request));
    return;
  }
  
  // Handle static assets
  event.respondWith(handleStaticRequest(request));
});

// Handle navigation requests (pages)
async function handleNavigationRequest(request) {
  try {
    // Try to fetch from network first
    const networkResponse = await fetch(request);
    
    // If successful, cache the response for future offline access
    if (networkResponse.ok) {
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: Network failed, serving from cache', request.url);
    
    // Try to serve from cache
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // If not in cache, serve offline page
    const offlineResponse = await caches.match(OFFLINE_URL);
    if (offlineResponse) {
      return offlineResponse;
    }
    
    // Last resort - basic offline response
    return new Response(
      `
      <!DOCTYPE html>
      <html>
        <head>
          <title>CoruNest - Offline</title>
          <meta name="viewport" content="width=device-width, initial-scale=1">
          <style>
            body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
            .offline-icon { font-size: 64px; color: #10b981; margin-bottom: 20px; }
            h1 { color: #374151; }
            p { color: #6b7280; margin: 20px 0; }
            .retry-btn { 
              background: #10b981; color: white; border: none; 
              padding: 12px 24px; border-radius: 8px; cursor: pointer; 
            }
          </style>
        </head>
        <body>
          <div class="offline-icon">📱</div>
          <h1>You're Offline</h1>
          <p>Please check your internet connection and try again.</p>
          <button class="retry-btn" onclick="window.location.reload()">Retry</button>
        </body>
      </html>
      `,
      {
        headers: { 'Content-Type': 'text/html' },
        status: 200
      }
    );
  }
}

// Handle API requests with cache-first strategy for GET requests
async function handleApiRequest(request) {
  try {
    // For API requests, try network first
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      // Cache successful responses
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: API request failed, serving from cache', request.url);
    
    // Serve from cache if network fails
    const cachedResponse = await caches.match(request);
    if (cachedResponse) {
      return cachedResponse;
    }
    
    // Return offline API response
    return new Response(
      JSON.stringify({
        error: 'Offline',
        message: 'This feature requires an internet connection',
        cached: false
      }),
      {
        headers: { 'Content-Type': 'application/json' },
        status: 503
      }
    );
  }
}

// Handle static asset requests
async function handleStaticRequest(request) {
  // Try cache first for static assets
  const cachedResponse = await caches.match(request);
  if (cachedResponse) {
    return cachedResponse;
  }
  
  try {
    // If not in cache, fetch from network
    const networkResponse = await fetch(request);
    
    if (networkResponse.ok) {
      // Cache the response
      const cache = await caches.open(CACHE_NAME);
      cache.put(request, networkResponse.clone());
    }
    
    return networkResponse;
  } catch (error) {
    console.log('Service Worker: Static asset failed to load', request.url);
    
    // For images, return a placeholder
    if (request.headers.get('Accept')?.includes('image/')) {
      return new Response(
        `<svg xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 200 200">
          <rect width="200" height="200" fill="#f3f4f6"/>
          <text x="100" y="100" text-anchor="middle" dy=".3em" fill="#9ca3af">Image Offline</text>
        </svg>`,
        { headers: { 'Content-Type': 'image/svg+xml' } }
      );
    }
    
    // For other assets, let it fail
    throw error;
  }
}

// Check if request is an API request
function isApiRequest(request) {
  const url = new URL(request.url);
  
  // Check for API patterns
  return API_CACHE_PATTERNS.some(pattern => pattern.test(request.url)) ||
         url.pathname.startsWith('/api/') ||
         request.headers.get('Accept')?.includes('application/json');
}

// Background sync for offline form submissions
self.addEventListener('sync', event => {
  console.log('Service Worker: Background sync triggered', event.tag);
  
  if (event.tag === 'donation-sync') {
    event.waitUntil(syncOfflineDonations());
  }
  
  if (event.tag === 'volunteer-sync') {
    event.waitUntil(syncOfflineVolunteers());
  }
});

// Sync offline donations when connection is restored
async function syncOfflineDonations() {
  try {
    // Get offline donations from IndexedDB
    const offlineDonations = await getOfflineData('donations');
    
    for (const donation of offlineDonations) {
      try {
        const response = await fetch('/donations', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': donation.csrfToken
          },
          body: JSON.stringify(donation.data)
        });
        
        if (response.ok) {
          // Remove from offline storage
          await removeOfflineData('donations', donation.id);
          console.log('Service Worker: Synced offline donation', donation.id);
        }
      } catch (error) {
        console.error('Service Worker: Failed to sync donation', error);
      }
    }
  } catch (error) {
    console.error('Service Worker: Background sync failed', error);
  }
}

// Sync offline volunteer signups
async function syncOfflineVolunteers() {
  try {
    const offlineVolunteers = await getOfflineData('volunteers');
    
    for (const volunteer of offlineVolunteers) {
      try {
        const response = await fetch('/volunteers', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': volunteer.csrfToken
          },
          body: JSON.stringify(volunteer.data)
        });
        
        if (response.ok) {
          await removeOfflineData('volunteers', volunteer.id);
          console.log('Service Worker: Synced offline volunteer signup', volunteer.id);
        }
      } catch (error) {
        console.error('Service Worker: Failed to sync volunteer signup', error);
      }
    }
  } catch (error) {
    console.error('Service Worker: Volunteer sync failed', error);
  }
}

// Push notification handler
self.addEventListener('push', event => {
  console.log('Service Worker: Push notification received');
  
  if (!event.data) return;
  
  const data = event.data.json();
  const options = {
    body: data.body || 'New update from CoruNest',
    icon: '/icons/icon-192x192.png',
    badge: '/icons/icon-72x72.png',
    image: data.image,
    data: {
      url: data.url || '/'
    },
    actions: [
      {
        action: 'open',
        title: 'View',
        icon: '/icons/action-view.png'
      },
      {
        action: 'dismiss',
        title: 'Dismiss',
        icon: '/icons/action-dismiss.png'
      }
    ],
    requireInteraction: data.requireInteraction || false,
    silent: false,
    vibrate: [200, 100, 200]
  };
  
  event.waitUntil(
    self.registration.showNotification(data.title || 'CoruNest', options)
  );
});

// Handle notification clicks
self.addEventListener('notificationclick', event => {
  console.log('Service Worker: Notification clicked', event.action);
  
  event.notification.close();
  
  if (event.action === 'dismiss') {
    return;
  }
  
  const url = event.notification.data?.url || '/';
  
  event.waitUntil(
    clients.matchAll({ type: 'window' })
      .then(clientList => {
        // Check if app is already open
        for (const client of clientList) {
          if (client.url === url && 'focus' in client) {
            return client.focus();
          }
        }
        
        // Open new window if app is not open
        if (clients.openWindow) {
          return clients.openWindow(url);
        }
      })
  );
});

// Helper functions for IndexedDB operations (you'll need to implement these)
async function getOfflineData(store) {
  // Implementation would use IndexedDB to retrieve offline data
  return [];
}

async function removeOfflineData(store, id) {
  // Implementation would use IndexedDB to remove synced data
  return true;
}