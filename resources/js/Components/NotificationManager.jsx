import React, { useEffect } from 'react';
import { LocalNotifications } from '@capacitor/local-notifications';
import { PushNotifications } from '@capacitor/push-notifications';
import { useMobile } from './MobileDetector';

const NotificationManager = ({ children }) => {
    const { isCapacitor } = useMobile();

    useEffect(() => {
        if (isCapacitor) {
            setupNotifications();
        }
    }, [isCapacitor]);

    const setupNotifications = async () => {
        try {
            // Request permissions
            const permission = await LocalNotifications.requestPermissions();
            
            if (permission.display === 'granted') {
                // Setup push notifications
                await setupPushNotifications();
                
                // Setup local notification listeners
                LocalNotifications.addListener('localNotificationReceived', (notification) => {
                    console.log('Local notification received:', notification);
                });

                LocalNotifications.addListener('localNotificationActionPerformed', (action) => {
                    console.log('Local notification action performed:', action);
                    handleNotificationAction(action);
                });
            }
        } catch (error) {
            console.error('Error setting up notifications:', error);
        }
    };

    const setupPushNotifications = async () => {
        try {
            const permission = await PushNotifications.requestPermissions();
            
            if (permission.receive === 'granted') {
                await PushNotifications.register();
                
                PushNotifications.addListener('registration', (token) => {
                    console.log('Push registration success, token:', token.value);
                    // Send token to Laravel backend
                    sendTokenToBackend(token.value);
                });

                PushNotifications.addListener('pushNotificationReceived', (notification) => {
                    console.log('Push notification received:', notification);
                    showLocalNotification(notification);
                });

                PushNotifications.addListener('pushNotificationActionPerformed', (action) => {
                    console.log('Push notification action performed:', action);
                    handleNotificationAction(action);
                });
            }
        } catch (error) {
            console.error('Error setting up push notifications:', error);
        }
    };

    const sendTokenToBackend = async (token) => {
        try {
            await fetch('/api/device/register-token', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                },
                body: JSON.stringify({ 
                    token,
                    platform: 'mobile',
                    app_version: await App.getInfo().then(info => info.version)
                }),
            });
        } catch (error) {
            console.error('Error registering push token:', error);
        }
    }

    static async scheduleEventReminder(event, hoursBeforeEvent = 24) {
        const reminderTime = new Date(event.starts_at);
        reminderTime.setHours(reminderTime.getHours() - hoursBeforeEvent);

        if (reminderTime > new Date()) {
            await LocalNotifications.schedule({
                notifications: [
                    {
                        title: 'Volunteer Event Reminder',
                        body: `Don't forget: ${event.title} starts in ${hoursBeforeEvent} hours!`,
                        id: event.id * 100 + hoursBeforeEvent,
                        schedule: { at: reminderTime },
                        sound: 'default',
                        extra: {
                            event_id: event.id,
                            type: 'event_reminder'
                        }
                    }
                ]
            });
        }
    }

    static async scheduleDonationThankYou(donation, delayMinutes = 5) {
        const thankYouTime = new Date();
        thankYouTime.setMinutes(thankYouTime.getMinutes() + delayMinutes);

        await LocalNotifications.schedule({
            notifications: [
                {
                    title: 'Thank You! 🙏',
                    body: `Your R${donation.amount} donation is making a difference in Cape Town!`,
                    id: donation.id + 50000,
                    schedule: { at: thankYouTime },
                    sound: 'default',
                    extra: {
                        donation_id: donation.id,
                        type: 'donation_thank_you'
                    }
                }
            ]
        });
    }
}

// Initialize when app loads
if (window.Capacitor) {
    document.addEventListener('DOMContentLoaded', () => {
        CapacitorIntegration.initialize();
    });
}

Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify({ token, platform: 'mobile' }),
            });
        } catch (error) {
            console.error('Error sending token to backend:', error);
        }
    };

    const showLocalNotification = async (notification) => {
        await LocalNotifications.schedule({
            notifications: [
                {
                    title: notification.title,
                    body: notification.body,
                    id: Date.now(),
                    schedule: { at: new Date(Date.now() + 1000) },
                    sound: 'default',
                    actionTypeId: 'DONATION_ACTION',
                    actions: [
                        {
                            id: 'view',
                            title: 'View',
                        },
                        {
                            id: 'dismiss',
                            title: 'Dismiss',
                        }
                    ],
                    extra: notification.data,
                }
            ]
        });
    };

    const handleNotificationAction = (action) => {
        if (action.actionId === 'view' && action.notification?.extra?.url) {
            window.location.href = action.notification.extra.url;
        }
    };

    // Expose methods to schedule notifications
    window.scheduleNotification = async ({ title, body, delay = 0, data = {} }) => {
        if (!isCapacitor) return;

        await LocalNotifications.schedule({
            notifications: [
                {
                    title,
                    body,
                    id: Date.now(),
                    schedule: { at: new Date(Date.now() + delay) },
                    sound: 'default',
                    extra: data,
                }
            ]
        });
    };

    return children;
};

export default NotificationManager;