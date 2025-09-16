<!-- resources/views/layouts/app.blade.php -->
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/alpinejs/3.13.3/cdn.min.js" defer></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    
    <title>@yield('title', 'CoruNest - Organise. Fund. Mobilise.')</title>
    <meta name="description" content="@yield('description', 'CoruNest helps Cape Town NGOs manage donations, campaigns, and volunteers efficiently.')">
    
    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1f2937">
    <link rel="manifest" href="/manifest.json">
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:300,400,500,600,700&display=swap" rel="stylesheet" />
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b border-gray-200" x-data="{ mobileOpen: false }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-2xl font-bold text-gray-900">
                        CoruNest
                    </a>
                    <span class="ml-2 text-sm text-gray-500 hidden sm:inline">
                        Organise. Fund. Mobilise.
                    </span>
                </div>
                
                <!-- Desktop Navigation -->
                <div class="hidden md:flex items-center space-x-8">
                    <a href="{{ route('campaigns.index') }}" 
                       class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        Campaigns
                    </a>
                    <a href="{{ route('events.index') }}" 
                       class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                        Events
                    </a>
                    
                    @auth
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('admin.dashboard') }}" 
                               class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">
                                Admin Dashboard
                            </a>
                        @else
                            <a href="{{ route('dashboard') }}" 
                               class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                My Account
                            </a>
                        @endif
                        
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" 
                                    class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                Logout
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" 
                           class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                            Login
                        </a>
                        <a href="{{ route('register') }}" 
                           class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-blue-700">
                            Register
                        </a>
                    @endauth
                </div>
                
                <!-- Mobile menu button -->
                <div class="md:hidden flex items-center">
                    <button @click="mobileOpen = !mobileOpen" 
                            class="text-gray-400 hover:text-gray-500 focus:outline-none focus:text-gray-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Mobile menu -->
        <div class="md:hidden" x-show="mobileOpen" x-transition>
            <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-white border-t border-gray-200">
                <a href="{{ route('campaigns.index') }}" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900">
                    Campaigns
                </a>
                <a href="{{ route('events.index') }}" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900">
                    Events
                </a>
                
                @auth
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="block px-3 py-2 text-base font-medium bg-blue-600 text-white rounded-md">
                            Admin Dashboard
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900">
                            My Account
                        </a>
                    @endif
                    
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full text-left px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900">
                            Logout
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="block px-3 py-2 text-base font-medium text-gray-600 hover:text-gray-900">
                        Login
                    </a>
                    <a href="{{ route('register') }}" class="block px-3 py-2 text-base font-medium bg-blue-600 text-white rounded-md">
                        Register
                    </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative max-w-7xl mx-auto mt-4" 
             x-data="{ show: true }" x-show="show" x-transition>
            <span class="block sm:inline">{{ session('success') }}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                <svg class="fill-current h-6 w-6 text-green-500" role="button" viewBox="0 0 20 20">
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative max-w-7xl mx-auto mt-4" 
             x-data="{ show: true }" x-show="show" x-transition>
            <span class="block sm:inline">{{ session('error') }}</span>
            <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                <svg class="fill-current h-6 w-6 text-red-500" role="button" viewBox="0 0 20 20">
                    <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                </svg>
            </span>
        </div>
    @endif

    <!-- Main Content -->
    <main>
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div class="col-span-1 md:col-span-2">
                    <h3 class="text-lg font-semibold mb-4">CoruNest</h3>
                    <p class="text-gray-400 mb-4">
                        Empowering Cape Town NGOs with transparent, efficient donation and volunteer management.
                    </p>
                    <p class="text-sm text-gray-500">
                        Built with ❤️ for social impact organizations.
                    </p>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="{{ route('campaigns.index') }}" class="text-gray-400 hover:text-white">Browse Campaigns</a></li>
                        <li><a href="{{ route('events.index') }}" class="text-gray-400 hover:text-white">Volunteer Events</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">About Us</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Contact</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="text-sm font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-sm">
                        <li><a href="#" class="text-gray-400 hover:text-white">Privacy Policy</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-gray-400 hover:text-white">POPIA Compliance</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="mt-8 pt-8 border-t border-gray-800 text-center text-sm text-gray-400">
                <p>&copy; {{ date('Y') }} CoruNest. All rights reserved.</p>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>

<!-- resources/views/home.blade.php -->
@extends('layouts.app')

@section('title', 'CoruNest - Organise. Fund. Mobilise.')
@section('description', 'Join Cape Town\'s premier NGO platform. Support local causes, volunteer for events, and make a real difference in your community.')

@section('content')
<!-- Hero Section -->
<div class="bg-gradient-to-r from-blue-600 to-purple-600 text-white">
    <div class="max-w-7xl mx-auto py-16 px-4 sm:py-24 sm:px-6 lg:px-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-6xl font-bold mb-6">
                Organise. Fund. Mobilise.
            </h1>
            <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
                Empowering Cape Town NGOs with transparent donation management and volunteer coordination.
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('campaigns.index') }}" 
                   class="bg-white text-blue-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-50 transition">
                    Browse Campaigns
                </a>
                <a href="{{ route('events.index') }}" 
                   class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-blue-600 transition">
                    Volunteer Now
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Stats Section -->
<div class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-3xl md:text-4xl font-bold text-blue-600 mb-2">
                    R{{ number_format($stats['total_raised'], 0) }}
                </div>
                <div class="text-gray-600">Total Raised</div>
            </div>
            <div>
                <div class="text-3xl md:text-4xl font-bold text-green-600 mb-2">
                    {{ $stats['active_campaigns'] }}
                </div>
                <div class="text-gray-600">Active Campaigns</div>
            </div>
            <div>
                <div class="text-3xl md:text-4xl font-bold text-purple-600 mb-2">
                    {{ $stats['total_donors'] }}
                </div>
                <div class="text-gray-600">Generous Donors</div>
            </div>
            <div>
                <div class="text-3xl md:text-4xl font-bold text-orange-600 mb-2">
                    {{ $stats['total_campaigns'] }}
                </div>
                <div class="text-gray-600">Total Campaigns</div>
            </div>
        </div>
    </div>
</div>

<!-- Featured Campaigns -->
<div class="bg-gray-50 py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                Featured Campaigns
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Support these urgent causes making a real impact in Cape Town communities.
            </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            @forelse($featuredCampaigns as $campaign)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition">
                    @if($campaign->image_path)
                        <img src="{{ Storage::url($campaign->image_path) }}" 
                             alt="{{ $campaign->title }}" 
                             class="w-full h-48 object-cover">
                    @else
                        <div class="w-full h-48 bg-gradient-to-r from-blue-400 to-purple-500"></div>
                    @endif
                    
                    <div class="p-6">
                        <h3 class="text-xl font-semibold mb-2 text-gray-900">
                            {{ $campaign->title }}
                        </h3>
                        <p class="text-gray-600 mb-4">
                            {{ Str::limit($campaign->summary, 120) }}
                        </p>
                        
                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm text-gray-600 mb-1">
                                <span>R{{ number_format($campaign->current_amount, 0) }} raised</span>
                                <span>{{ number_format($campaign->progress_percentage, 1) }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full" 
                                     style="width: {{ min($campaign->progress_percentage, 100) }}%"></div>
                            </div>
                            <div class="text-sm text-gray-500 mt-1">
                                of R{{ number_format($campaign->target_amount, 0) }} goal
                            </div>
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <a href="{{ route('campaigns.show', $campaign->slug) }}" 
                               class="text-blue-600 hover:text-blue-800 font-medium">
                                Learn More →
                            </a>
                            <a href="{{ route('donations.create', $campaign) }}" 
                               class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                                Donate
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-span-3 text-center py-12">
                    <p class="text-gray-500 text-lg">No featured campaigns available at the moment.</p>
                    <a href="{{ route('campaigns.index') }}" 
                       class="text-blue-600 hover:text-blue-800 font-medium mt-2 inline-block">
                        Browse All Campaigns →
                    </a>
                </div>
            @endforelse
        </div>
        
        @if($featuredCampaigns->count() > 0)
            <div class="text-center mt-12">
                <a href="{{ route('campaigns.index') }}" 
                   class="bg-blue-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-blue-700 transition">
                    View All Campaigns
                </a>
            </div>
        @endif
    </div>
</div>

<!-- How It Works -->
<div class="bg-white py-16">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">
                How It Works
            </h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Making impact simple and transparent for donors, volunteers, and NGOs.
            </p>
        </div>
        
        <div class="grid md:grid-cols-3 gap-8">
            <div class="text-center">
                <div class="bg-blue-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-4">Discover</h3>
                <p class="text-gray-600">
                    Browse verified campaigns and events from trusted Cape Town NGOs working on causes you care about.
                </p>
            </div>
            
            <div class="text-center">
                <div class="bg-green-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-4">Support</h3>
                <p class="text-gray-600">
                    Make secure donations or sign up to volunteer for events. Every contribution makes a real difference.
                </p>
            </div>
            
            <div class="text-center">
                <div class="bg-purple-100 w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold mb-4">Track Impact</h3>
                <p class="text-gray-600">
                    Follow campaign progress, receive updates, and see exactly how your contributions are making an impact.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection