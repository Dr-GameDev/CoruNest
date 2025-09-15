// capacitor.config.ts
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'org.corunest.app',
    appName: 'CoruNest',
    webDir: 'public',
    bundledWebRuntime: false,
    server: {
        url: process.env.NODE_ENV === 'production'
            ? 'https://yourdomain.com'
            : 'http://localhost:8000',
        cleartext: true
    },
    plugins: {
        PushNotifications: {
            presentationOptions: ["badge", "sound", "alert"]
        },
        LocalNotifications: {
            smallIcon: "ic_stat_icon_config_sample",
            iconColor: "#488AFF"
        },
        SplashScreen: {
            launchShowDuration: 2000,
            backgroundColor: "#0ea5e9",
            showSpinner: false
        }
    }
};

export default config;