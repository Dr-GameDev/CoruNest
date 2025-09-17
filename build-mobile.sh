#!/bin/bash

echo "🚀 Building CoruNest for Mobile Production..."

# Install dependencies
echo "📦 Installing dependencies..."
composer install --no-dev --optimize-autoloader
npm ci --production

# Build frontend assets
echo "🔨 Building frontend assets..."
npm run build

# Optimize Laravel
echo "⚡ Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Generate app key if not exists
php artisan key:generate --force

# Run database migrations
echo "🗄️ Running database migrations..."
php artisan migrate --force

# Generate PWA icons
echo "🖼️ Generating PWA icons..."
npm run pwa:generate-icons

# Sync Capacitor
echo "📱 Syncing Capacitor..."
npx cap sync

# Build for Android
echo "🤖 Building Android app..."
npx cap build android --prod

# Build for iOS (if on macOS)
if [[ "$OSTYPE" == "darwin"* ]]; then
    echo "🍎 Building iOS app..."
    npx cap build ios --prod
fi

echo "✅ Mobile build complete!"
echo "📱 Android: android/app/build/outputs/apk/release/app-release.apk"
echo "🍎 iOS: Open ios/App/App.xcworkspace in Xcode"