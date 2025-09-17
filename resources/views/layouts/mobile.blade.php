<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>{{ config('app.name', 'CoruNest') }} - @yield('title', 'Organise. Fund. Mobilise.')</title>
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#10b981">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="CoruNest">
    
    <!-- Capacitor Meta Tags -->
    <meta name="format-detection" content="telephone=no">
    <meta name="msapplication-tap-highlight" content="no">
    
    <!-- PWA Links -->
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/icons/apple-icon-180.png') }}">
    
    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if(app()->environment('production'))
        @vite('resources/js/capacitor-plugins.js')
    @endif
    
    <style>
        /* Mobile-specific styles */
        .mobile-safe-area {
            padding-top: env(safe-area-inset-top);
            padding-bottom: env(safe-area-inset-bottom);
            padding-left: env(safe-area-inset-left);
            padding-right: env(safe-area-inset-right);
        }
        
        .mobile-toast {
            position: fixed;
            bottom: 2rem;
            left: 1rem;
            right: 1rem;
            background: rgba(16, 185, 129, 0.95);
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            backdrop-filter: blur(10px);
            z-index: 1000;
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from { transform: translateY(100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .offline-banner {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            padding: 0.75rem 1rem;
            text-align: center;
            font-size: 0.875rem;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .offline-banner i {
            margin-right: 0.5rem;
        }
        
        .mobile-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--gray-200);
            position: sticky;
            top: 0;
            z-index: 40;
            padding: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .mobile-bottom-nav {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-top: 1px solid var(--gray-200);
            padding: env(safe-area-inset-bottom) 0 0;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            z-index: 50;
        }
        
        .mobile-bottom-nav a {
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
            text-decoration: none;
            color: var(--gray-600);
            font-size: 0.75rem;
            transition: color 0.2s;
        }
        
        .mobile-bottom-nav a.active,
        .mobile-bottom-nav a:hover {
            color: var(--primary-600);
        }
        
        .mobile-bottom-nav i {
            font-size: 1.25rem;
        }
        
        .mobile-fab {
            position: fixed;
            bottom: 5rem;
            right: 1rem;
            width: 3.5rem;
            height: 3.5rem;
            background: linear-gradient(135deg, var(--primary-600), var(--primary-500));
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
            z-index: 45;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .mobile-fab:active {
            transform: scale(0.95);
        }
        
        .mobile-fab:hover {
            box-shadow: 0 15px 35px rgba(16, 185, 129, 0.4);
        }
        
        @media (max-width: 768px) {
            body {
                padding-bottom: 5rem; /* Space for bottom nav */
            }
            
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .campaign-card,
            .event-card {
                margin-bottom: 1rem;
            }
            
            .donation-form {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                background: white;
                border-radius: 1rem 1rem 0 0;
                padding: 1.5rem;
                box-shadow: 0 -10px 25px rgba(0, 0, 0, 0.1);
                transform: translateY(100%);
                transition: transform 0.3s ease;
                z-index: 60;
            }
            
            .donation-form.open {
                transform: translateY(0);
            }
        }
    </style>
</head>
<body class="font-sans antialiased mobile-safe-area" x-data="{ 
    showDonationForm: false,
    isOffline: !navigator.onLine 
}" x-init="
    window.addEventListener('online', () => isOffline = false);
    window.addEventListener('offline', () => isOffline = true);
">
    <!-- Offline Banner -->
    <div x-show="isOffline" class="offline-banner">
        <i class="fas fa-wifi-slash"></i>
        You're offline. Some features may be limited.
    </div>
    
    <!-- Mobile Header -->
    <div class="mobile-header">
        <a href="/" class="flex items-center gap-2">
            <i class="fas fa-heart text-primary-600"></i>
            <span class="font-bold text-lg">CoruNest</span>
        </a>
        
        <div class="flex items-center gap-3">
            @guest
                <a href="{{ route('login') }}" class="btn btn-sm btn-outline">Login</a>
            @else
                <div class="relative" x-data="{ showMenu: false }">
                    <button @click="showMenu = !showMenu" class="flex items-center gap-2 text-sm">
                        <div class="w-8 h-8 bg-primary-100 rounded-full flex items-center justify-center">
                            {{ substr(auth()->user()->name, 0, 1) }}
                        </div>
                    </button>
                    
                    <div x-show="showMenu" @click.away="showMenu = false" 
                         class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border">
                        <a href="{{ route('admin.dashboard') }}" class="block px-4 py-2 text-sm hover:bg-gray-50">Dashboard</a>
                        <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-gray-50">Profile</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-50">Logout</button>
                        </form>
                    </div>
                </div>
            @endguest
        </div>
    </div>
    
    <!-- Main Content -->
    <main>
        @yield('content')
    </main>
    
    <!-- Mobile FAB -->
    <button @click="showDonationForm = true" class="mobile-fab md:hidden">
        <i class="fas fa-heart"></i>
    </button>
    
    <!-- Mobile Bottom Navigation -->
    <nav class="mobile-bottom-nav md:hidden">
        <a href="{{ route('home') }}" class="{{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="{{ route('campaigns.index') }}" class="{{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
            <i class="fas fa-bullhorn"></i>
            <span>Campaigns</span>
        </a>
        <a href="{{ route('events.index') }}" class="{{ request()->routeIs('events.*') ? 'active' : '' }}">
            <i class="fas fa-calendar"></i>
            <span>Events</span>
        </a>
        <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.*') ? 'active' : '' }}">
            <i class="fas fa-chart-line"></i>
            <span>Admin</span>
        </a>
    </nav>
    
    <!-- Mobile Donation Form -->
    <div x-show="showDonationForm" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="transform translate-y-full"
         x-transition:enter-end="transform translate-y-0"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="transform translate-y-0"
         x-transition:leave-end="transform translate-y-full"
         class="donation-form md:hidden"
         @click.away="showDonationForm = false">
        
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Quick Donation</h3>
            <button @click="showDonationForm = false" class="text-gray-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div x-data="mobileDonationForm()">
            @include('components.mobile-donation-form')
        </div>
    </div>
    
    @stack('scripts')
    
    <!-- Mobile-specific JavaScript -->
    <script>
        // Prevent zoom on double tap
        let lastTouchEnd = 0;
        document.addEventListener('touchend', function (event) {
            const now = (new Date()).getTime();
            if (now - lastTouchEnd <= 300) {
                event.preventDefault();
            }
            lastTouchEnd = now;
        }, false);
        
        // Handle mobile keyboard
        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', () => {
                document.documentElement.style.setProperty('--vh', window.visualViewport.height * 0.01 + 'px');
            });
        }
        
        // Haptic feedback for mobile interactions
        function vibrate(pattern = 10) {
            if ('vibrate' in navigator && window.Capacitor) {
                navigator.vibrate(pattern);
            }
        }
        
        // Add vibration to buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.btn, .amount-btn, .mobile-fab')) {
                vibrate();
            }
        });
        
        // Install app prompt
        let deferredPrompt;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            showInstallBanner();
        });
        
        function showInstallBanner() {
            const banner = document.createElement('div');
            banner.className = 'install-banner';
            banner.innerHTML = `
                <div class="flex items-center justify-between p-4 bg-primary-50 border-b">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-mobile-alt text-primary-600"></i>
                        <div>
                            <div class="font-semibold text-sm">Install CoruNest</div>
                            <div class="text-xs text-gray-600">Get the full app experience</div>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button onclick="installApp()" class="btn btn-sm btn-primary">Install</button>
                        <button onclick="dismissInstallBanner()" class="text-gray-500">✕</button>
                    </div>
                </div>
            `;
            
            document.body.insertBefore(banner, document.body.firstChild);
        }
        
        async function installApp() {
            if (deferredPrompt) {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                deferredPrompt = null;
                dismissInstallBanner();
            }
        }
        
        function dismissInstallBanner() {
            const banner = document.querySelector('.install-banner');
            if (banner) {
                banner.remove();
            }
        }
    </script>
</body>
</html>
