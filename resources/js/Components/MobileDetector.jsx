import React, { createContext, useContext, useEffect, useState } from 'react';
import { App } from '@capacitor/app';
import { StatusBar } from '@capacitor/status-bar';
import { SplashScreen } from '@capacitor/splash-screen';

const MobileContext = createContext();

export const useMobile = () => useContext(MobileContext);

export const MobileProvider = ({ children }) => {
    const [isMobile, setIsMobile] = useState(false);
    const [isCapacitor, setIsCapacitor] = useState(false);
    const [deviceInfo, setDeviceInfo] = useState(null);

    useEffect(() => {
        const checkMobile = () => {
            const userAgent = navigator.userAgent || navigator.vendor || window.opera;
            setIsMobile(/android|webos|iphone|ipad|ipod|blackberry|iemobile|opera mini/i.test(userAgent));
        };

        const checkCapacitor = async () => {
            try {
                const info = await App.getInfo();
                setIsCapacitor(true);
                setDeviceInfo(info);
                
                // Configure mobile-specific settings
                await configureMobileApp();
            } catch (error) {
                setIsCapacitor(false);
            }
        };

        checkMobile();
        checkCapacitor();
    }, []);

    const configureMobileApp = async () => {
        try {
            // Hide splash screen
            await SplashScreen.hide();

            // Configure status bar
            await StatusBar.setStyle({ style: 'DARK' });
            await StatusBar.setBackgroundColor({ color: '#10b981' });

            // Setup app state listeners
            App.addListener('appStateChange', ({ isActive }) => {
                console.log('App state changed. Is active:', isActive);
            });

            App.addListener('backButton', ({ canGoBack }) => {
                if (!canGoBack) {
                    App.exitApp();
                } else {
                    window.history.back();
                }
            });

        } catch (error) {
            console.error('Error configuring mobile app:', error);
        }
    };

    const exitApp = async () => {
        if (isCapacitor) {
            await App.exitApp();
        }
    };

    const minimizeApp = async () => {
        if (isCapacitor) {
            await App.minimizeApp();
        }
    };

    const value = {
        isMobile,
        isCapacitor,
        deviceInfo,
        exitApp,
        minimizeApp,
    };

    return (
        <MobileContext.Provider value={value}>
            {children}
        </MobileContext.Provider>
    );
};
