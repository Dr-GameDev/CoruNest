import React, { createContext, useContext, useEffect, useState } from 'react';

const OfflineContext = createContext();

export const useOffline = () => useContext(OfflineContext);

export const OfflineProvider = ({ children }) => {
    const [isOnline, setIsOnline] = useState(navigator.onLine);
    const [offlineQueue, setOfflineQueue] = useState([]);

    useEffect(() => {
        const handleOnline = () => {
            setIsOnline(true);
            processOfflineQueue();
        };

        const handleOffline = () => {
            setIsOnline(false);
            showOfflineNotification();
        };

        window.addEventListener('online', handleOnline);
        window.addEventListener('offline', handleOffline);

        return () => {
            window.removeEventListener('online', handleOnline);
            window.removeEventListener('offline', handleOffline);
        };
    }, []);

    const showOfflineNotification = () => {
        if ('serviceWorker' in navigator && 'PushManager' in window) {
            // Show toast notification
            const toast = document.createElement('div');
            toast.className = 'offline-toast';
            toast.textContent = 'You\'re offline. Don\'t worry, your actions will be saved and synced when you reconnect.';
            document.body.appendChild(toast);

            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 5000);
        }
    };

    const addToOfflineQueue = (action) => {
        const queueItem = {
            id: Date.now(),
            action,
            timestamp: new Date().toISOString(),
        };

        setOfflineQueue(prev => [...prev, queueItem]);
        
        // Store in IndexedDB for persistence
        storeOfflineAction(queueItem);
    };

    const storeOfflineAction = (action) => {
        const request = indexedDB.open('CoruNestOffline', 1);

        request.onupgradeneeded = (event) => {
            const db = event.target.result;
            
            if (!db.objectStoreNames.contains('donations')) {
                db.createObjectStore('donations', { keyPath: 'id' });
            }
            
            if (!db.objectStoreNames.contains('volunteers')) {
                db.createObjectStore('volunteers', { keyPath: 'id' });
            }
        };

        request.onsuccess = (event) => {
            const db = event.target.result;
            const transaction = db.transaction([action.action.type], 'readwrite');
            const store = transaction.objectStore(action.action.type);
            store.add(action);
        };
    };

    const processOfflineQueue = async () => {
        if (offlineQueue.length === 0) return;

        for (const item of offlineQueue) {
            try {
                await processOfflineAction(item);
                setOfflineQueue(prev => prev.filter(q => q.id !== item.id));
            } catch (error) {
                console.error('Failed to process offline action:', error);
            }
        }

        // Trigger background sync if available
        if ('serviceWorker' in navigator && 'sync' in window.ServiceWorkerRegistration.prototype) {
            const registration = await navigator.serviceWorker.ready;
            await registration.sync.register('donation-sync');
            await registration.sync.register('volunteer-sync');
        }
    };

    const processOfflineAction = async (item) => {
        const { action } = item;
        
        switch (action.type) {
            case 'donations':
                return fetch('/api/donate', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': action.csrfToken,
                    },
                    body: JSON.stringify(action.data),
                });
                
            case 'volunteers':
                return fetch('/api/volunteer', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': action.csrfToken,
                    },
                    body: JSON.stringify(action.data),
                });
                
            default:
                throw new Error(`Unknown action type: ${action.type}`);
        }
    };

    const submitOfflineForm = (type, data) => {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        
        addToOfflineQueue({
            type,
            data,
            csrfToken,
        });

        // Show success message
        const toast = document.createElement('div');
        toast.className = 'success-toast';
        toast.textContent = 'Saved! This will be submitted when you\'re back online.';
        document.body.appendChild(toast);

        setTimeout(() => {
            if (document.body.contains(toast)) {
                document.body.removeChild(toast);
            }
        }, 3000);
    };

    const value = {
        isOnline,
        offlineQueue: offlineQueue.length,
        submitOfflineForm,
    };

    return (
        <OfflineContext.Provider value={value}>
            {children}
        </OfflineContext.Provider>
    );
};