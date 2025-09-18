<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'CoruNest - NGO Donation & Volunteer Platform')</title>
    <meta name="description" content="@yield('description', 'Empowering NGOs across Cape Town through transparent donations and volunteer mobilisation. Organise. Fund. Mobilise.')">

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#10b981">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/png" sizes="32x32" href="/icons/icon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/icons/icon-16x16.png">
    <link rel="apple-touch-icon" href="/icons/icon-192x192.png">

    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('styles')
</head>
<body class="font-sans antialiased bg-white" x-data="{ mobileMenuOpen: false }">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200 sticky top-0 z-50">
        <div class="container mx-auto px-4">
            <div class="flex justify-between items-center h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="{{ route('campaigns.index') }}" class="flex items-center">
                        <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold text-gray-900">CoruNest</span>
                    </a>
                </div>

                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('campaigns.index') }}" 
                       class="text-gray-700 hover:text-emerald-600 font-medium transition-colors
                              {{ request()->routeIs('campaigns.*') ? 'text-emerald-600' : '' }}">
                        Campaigns
                    </a>
                    <a href="{{ route('events.index') }}" 
                       class="text-gray-700 hover:text-emerald-600 font-medium transition-colors
                              {{ request()->routeIs('events.*') ? 'text-emerald-600' : '' }}">
                        Events
                    </a>
                    <a href="#" 
                       class="text-gray-700 hover:text-emerald-600 font-medium transition-colors">
                        About
                    </a>
                    <a href="#" 
                       class="text-gray-700 hover:text-emerald-600 font-medium transition-colors">
                        Contact
                    </a>
                </div>

                <!-- Auth & Mobile Menu -->
                <div class="flex items-center space-x-4">
                    @auth
                        <div class="hidden md:flex items-center space-x-4">
                            @if(auth()->user()->canAccessAdmin())
                                <a href="{{ route('admin.dashboard') }}" 
                                   class="text-gray-700 hover:text-emerald-600 font-medium">
                                    Admin
                                </a>
                            @endif
                            <a href="{{ route('donations.history') }}" 
                               class="text-gray-700 hover:text-emerald-600 font-medium">
                                My Donations
                            </a>
                            <a href="{{ route('volunteers.history') }}" 
                               class="text-gray-700 hover:text-emerald-600 font-medium">
                                My Volunteers
                            </a>
                            <div x-data="{ open: false }" class="relative">
                                <button @click="open = !open" 
                                        class="flex items-center text-gray-700 hover:text-emerald-600 font-medium">
                                    {{ auth()->user()->name }}
                                    <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                </button>
                                <div x-show="open" 
                                     x-transition
                                     @click.outside="open = false"
                                     class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-50">
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Profile</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            Sign out
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="hidden md:flex items-center space-x-4">
                            <a href="{{ route('login') }}" 
                               class="text-gray-700 hover:text-emerald-600 font-medium">
                                Sign In
                            </a>
                            <a href="{{ route('register') }}" 
                               class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-700 transition-colors">
                                Sign Up
                            </a>
                        </div>
                    @endauth

                    <!-- Mobile menu button -->
                    <button @click="mobileMenuOpen = !mobileMenuOpen" 
                            class="md:hidden text-gray-700 hover:text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            <path x-show="mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" 
                 x-transition
                 class="md:hidden border-t border-gray-200 py-4">
                <div class="flex flex-col space-y-4">
                    <a href="{{ route('campaigns.index') }}" 
                       class="text-gray-700 hover:text-emerald-600 font-medium">
                        Campaigns
                    </a>
                    <a href="{{ route('events.index') }}" 
                       class="text-gray-700 hover:text-emerald-600 font-medium">
                        Events
                    </a>
                    <a href="#" class="text-gray-700 hover:text-emerald-600 font-medium">About</a>
                    <a href="#" class="text-gray-700 hover:text-emerald-600 font-medium">Contact</a>
                    
                    @auth
                        <hr class="border-gray-200">
                        @if(auth()->user()->canAccessAdmin())
                            <a href="{{ route('admin.dashboard') }}" 
                               class="text-gray-700 hover:text-emerald-600 font-medium">
                                Admin Dashboard
                            </a>
                        @endif
                        <a href="{{ route('donations.history') }}" 
                           class="text-gray-700 hover:text-emerald-600 font-medium">
                            My Donations
                        </a>
                        <a href="{{ route('volunteers.history') }}" 
                           class="text-gray-700 hover:text-emerald-600 font-medium">
                            My Volunteers
                        </a>
                        <a href="{{ route('profile.edit') }}" 
                           class="text-gray-700 hover:text-emerald-600 font-medium">
                            Profile
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-700 hover:text-emerald-600 font-medium">
                                Sign out
                            </button>
                        </form>
                    @else
                        <hr class="border-gray-200">
                        <a href="{{ route('login') }}" 
                           class="text-gray-700 hover:text-emerald-600 font-medium">
                            Sign In
                        </a>
                        <a href="{{ route('register') }}" 
                           class="bg-emerald-600 text-white px-4 py-2 rounded-lg font-medium hover:bg-emerald-700 transition-colors inline-block text-center">
                            Sign Up
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div x-data="{ show: true }" 
             x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 relative">
            <div class="container mx-auto flex justify-between items-center">
                <span>{{ session('success') }}</span>
                <button @click="show = false" class="text-emerald-600 hover:text-emerald-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if(session('error'))
        <div x-data="{ show: true }" 
             x-show="show"
             x-init="setTimeout(() => show = false, 7000)"
             class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 relative">
            <div class="container mx-auto flex justify-between items-center">
                <span>{{ session('error') }}</span>
                <button @click="show = false" class="text-red-600 hover:text-red-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    @if(session('info'))
        <div x-data="{ show: true }" 
             x-show="show"
             x-init="setTimeout(() => show = false, 5000)"
             class="bg-blue-50 border border-blue-200 text-blue-800 px-4 py-3 relative">
            <div class="container mx-auto flex justify-between items-center">
                <span>{{ session('info') }}</span>
                <button @click="show = false" class="text-blue-600 hover:text-blue-800">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    @endif

    <!-- Main Content -->
    <main class="min-h-screen">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="container mx-auto px-4 py-12">
            <div class="grid md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-emerald-600 rounded-lg flex items-center justify-center mr-3">
                            <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        </div>
                        <span class="text-xl font-bold">CoruNest</span>
                    </div>
                    <p class="text-gray-400 mb-4">
                        Empowering NGOs across Cape Town through transparent donations and volunteer mobilisation.
                    </p>
                    <div class="text-sm text-gray-500">
                        <p>Organise. Fund. Mobilise.</p>
                    </div>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Get Involved</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="{{ route('campaigns.list') }}" class="hover:text-white transition-colors">View Campaigns</a></li>
                        <li><a href="{{ route('events.index') }}" class="hover:text-white transition-colors">Volunteer</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Start a Campaign</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Partner with Us</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Support</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">FAQ</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Contact Us</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Terms of Service</a></li>
                    </ul>
                </div>

                <div>
                    <h3 class="text-lg font-semibold mb-4">Connect</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="#" class="hover:text-white transition-colors">Facebook</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Twitter</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">Instagram</a></li>
                        <li><a href="#" class="hover:text-white transition-colors">LinkedIn</a></li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-500">
                <p>&copy; {{ date('Y') }} CoruNest. All rights reserved. Built with ❤️ in Cape Town.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')

    <!-- PWA Install Prompt -->
    <div x-data="pwaInstall()" x-show="showInstallPrompt" 
         class="fixed bottom-4 left-4 right-4 bg-emerald-600 text-white p-4 rounded-lg shadow-lg z-50 md:left-auto md:right-4 md:w-96">
        <div class="flex items-center justify-between">
            <div>
                <h4 class="font-semibold">Install CoruNest</h4>
                <p class="text-sm opacity-90">Get quick access to campaigns and events</p>
            </div>
            <div class="flex space-x-2 ml-4">
                <button @click="install()" 
                        class="bg-white text-emerald-600 px-3 py-1 rounded text-sm font-medium">
                    Install
                </button>
                <button @click="dismiss()" 
                        class="text-white opacity-75 hover:opacity-100">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <script>
        // PWA Install Functionality
        function pwaInstall() {
            return {
                showInstallPrompt: false,
                deferredPrompt: null,

                init() {
                    window.addEventListener('beforeinstallprompt', (e) => {
                        e.preventDefault();
                        this.deferredPrompt = e;
                        this.showInstallPrompt = true;
                    });
                },

                async install() {
                    if (this.deferredPrompt) {
                        this.deferredPrompt.prompt();
                        const { outcome } = await this.deferredPrompt.userChoice;
                        
                        if (outcome === 'accepted') {
                            console.log('PWA installed');
                        }
                        
                        this.deferredPrompt = null;
                        this.showInstallPrompt = false;
                    }
                },

                dismiss() {
                    this.showInstallPrompt = false;
                }
            }
        }
    </script>
</body>
</html>