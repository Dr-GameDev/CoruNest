const CACHE_NAME = 'corunest-v1';
const urlsToCache = [
    '/',
    '/offline.html',
    '/css/app.css',
    '/js/app.js',
    '/images/icons/icon-192.png',
    '/images/icons/icon-512.png'
];

// Install event
self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME)
            .then((cache) => cache.addAll(urlsToCache))
    );
});

// Fetch event - Network first, then cache
self.addEventListener('fetch', (event) => {
    // Skip cross-origin requests
    if (!event.request.url.startsWith(self.location.origin)) {
        return;
    }

    // Handle API requests with network-first strategy
    if (event.request.url.includes('/api/')) {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    // Clone the response
                    const responseClone = response.clone();

                    // Cache successful responses
                    if (response.status === 200) {
                        caches.open(CACHE_NAME)
                            .then((cache) => cache.put(event.request, responseClone));
                    }

                    return response;
                })
                .catch(() => {
                    // Return cached version if network fails
                    return caches.match(event.request)
                        .then((response) => response || caches.match('/offline.html'));
                })
        );
    } else {
        // Handle page requests with cache-first strategy
        event.respondWith(
            caches.match(event.request)
                .then((response) => {
                    // Return cached version or fetch from network
                    return response || fetch(event.request)
                        .then((fetchResponse) => {
                            // Cache the new response
                            const responseClone = fetchResponse.clone();
                            caches.open(CACHE_NAME)
                                .then((cache) => cache.put(event.request, responseClone));

                            return fetchResponse;
                        })
                        .catch(() => {
                            // Return offline page for navigation requests
                            if (event.request.mode === 'navigate') {
                                return caches.match('/offline.html');
                            }
                        });
                })
        );
    }
});

// Background sync for donations when offline
self.addEventListener('sync', (event) => {
    if (event.tag === 'donation-sync') {
        event.waitUntil(syncOfflineDonations());
    }
});

async function syncOfflineDonations() {
    // Get offline donations from IndexedDB
    const offlineDonations = await getOfflineDonations();

    for (const donation of offlineDonations) {
        try {
            await fetch('/api/donations', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': donation.csrfToken
                },
                body: JSON.stringify(donation.data)
            });

            // Remove from offline storage after successful sync
            await removeOfflineDonation(donation.id);
        } catch (error) {
            console.error('Failed to sync donation:', error);
        }
    }
}