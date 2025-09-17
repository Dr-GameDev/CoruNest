// capacitor.config.ts
import { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
    appId: 'org.corunest.app',
    appName: 'CoruNest',
    webDir: 'public',
    bundledWebRuntime: false,
    server: {
        androidScheme: 'https'
    },
    plugins: {
        SplashScreen: {
            launchShowDuration: 2000,
            backgroundColor: "#10b981",
            showSpinner: false,
            androidSpinnerStyle: "large",
            iosSpinnerStyle: "small",
            splashFullScreen: true,
            splashImmersive: true
        },
        StatusBar: {
            style: "dark",
            backgroundColor: "#10b981"
        },
        PushNotifications: {
            presentationOptions: ["badge", "sound", "alert"]
        },
        LocalNotifications: {
            smallIcon: "ic_stat_icon_config_sample",
            iconColor: "#10b981"
        }
    }
};

export default config;