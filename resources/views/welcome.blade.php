@extends('layouts.app')

@section('title', 'CoruNest - Organise. Fund. Mobilise.')

@section('content')
<div id="homepage">
    <!-- Hero Section -->
    <section class="relative bg-gradient-to-r from-emerald-600 to-emerald-800 text-white">
        <div class="absolute inset-0 bg-black bg-opacity-20"></div>
        <div class="relative container mx-auto px-4 py-24">
            <div class="text-center">
                <h1 class="text-5xl md:text-6xl font-bold mb-6">
                    Organise. Fund. Mobilise.
                </h1>
                <p class="text-xl md:text-2xl mb-8 max-w-3xl mx-auto opacity-90">
                    Empowering NGOs across Cape Town to create meaningful change through transparent donations and volunteer mobilisation.
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="{{ route('campaigns.list') }}" 
                       class="bg-white text-emerald-800 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors">
                        View Campaigns
                    </a>
                    <a href="{{ route('events.index') }}" 
                       class="border-2 border-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-emerald-800 transition-colors">
                        Volunteer Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
                <div class="stats-item" x-data="{ count: 0 }" x-intersect="$animate('.stats-counter', { from: { count: 0 }, to: { count: {{ $stats['total_campaigns'] }} }, duration: 2000 })">
                    <div class="text-4xl font-bold text-emerald-600 mb-2 stats-counter">{{ $stats['total_campaigns'] }}</div>
                    <div class="text-gray-600 font-medium">Active Campaigns</div>
                </div>
                <div class="stats-item" x-data="{ count: 0 }">
                    <div class="text-4xl font-bold text-emerald-600 mb-2">R{{ number_format($stats['total_raised'], 0) }}</div>
                    <div class="text-gray-600 font-medium">Raised</div>
                </div>
                <div class="stats-item">
                    <div class="text-4xl font-bold text-emerald-600 mb-2">{{ number_format($stats['total_donors']) }}</div>
                    <div class="text-gray-600 font-medium">Donors</div>
                </div>
                <div class="stats-item">
                    <div class="text-4xl font-bold text-emerald-600 mb-2">{{ $stats['active_campaigns'] }}</div>
                    <div class="text-gray-600 font-medium">Communities Served</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Campaigns -->
    <section class="py-20">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Featured Campaigns</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Support these urgent initiatives making a real impact in our communities
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8" x-data="featuredCampaigns()">
                @foreach($featuredCampaigns as $campaign)
                <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow duration-300"
                     x-data="{ progress: 0 }"
                     x-intersect="progress = {{ $campaign->progress_percentage }}">
                    
                    <!-- Campaign Image -->
                    <div class="aspect-video bg-gray-200 overflow-hidden">
                        @if($campaign->main_image)
                            <img src="{{ asset('storage/' . $campaign->main_image) }}" 
                                 alt="{{ $campaign->title }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center">
                                <svg class="w-16 h-16 text-white" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                        @endif
                    </div>

                    <!-- Campaign Content -->
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="bg-emerald-100 text-emerald-800 text-sm font-medium px-3 py-1 rounded-full">
                                {{ ucfirst($campaign->category) }}
                            </span>
                            @if($campaign->days_remaining)
                            <span class="text-sm text-gray-500">
                                {{ $campaign->days_remaining }} days left
                            </span>
                            @endif
                        </div>

                        <h3 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2">
                            {{ $campaign->title }}
                        </h3>

                        <p class="text-gray-600 mb-4 line-clamp-3">
                            {{ $campaign->summary }}
                        </p>

                        <!-- Progress Bar -->
                        <div class="mb-4">
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-sm font-medium text-gray-700">Progress</span>
                                <span class="text-sm font-medium text-emerald-600" x-text="progress.toFixed(0) + '%'">
                                    {{ number_format($campaign->progress_percentage, 0) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-emerald-600 h-2 rounded-full transition-all duration-1000 ease-out"
                                     :style="{ width: progress + '%' }"></div>
                            </div>
                        </div>

                        <!-- Campaign Stats -->
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <div class="text-2xl font-bold text-gray-900">
                                    {{ $campaign->formatted_current_amount }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    of {{ $campaign->formatted_target_amount }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-lg font-semibold text-gray-700">
                                    {{ $campaign->donor_count }}
                                </div>
                                <div class="text-sm text-gray-500">donors</div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-3">
                            <a href="{{ route('campaigns.donate', $campaign) }}" 
                               class="flex-1 bg-emerald-600 text-white text-center py-3 px-4 rounded-lg font-semibold hover:bg-emerald-700 transition-colors">
                                Donate Now
                            </a>
                            <a href="{{ route('campaigns.show', $campaign) }}" 
                               class="flex-1 border border-gray-300 text-gray-700 text-center py-3 px-4 rounded-lg font-semibold hover:bg-gray-50 transition-colors">
                                Learn More
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="text-center mt-12">
                <a href="{{ route('campaigns.list') }}" 
                   class="inline-flex items-center bg-gray-900 text-white px-8 py-4 rounded-lg font-semibold hover:bg-gray-800 transition-colors">
                    View All Campaigns
                    <svg class="ml-2 w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
    </section>

    <!-- Recent Campaigns Grid -->
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold text-gray-900 mb-4">Recent Campaigns</h2>
                <p class="text-xl text-gray-600">
                    More ways to make a difference in your community
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                @foreach($recentCampaigns as $campaign)
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                    <div class="aspect-video bg-gray-200">
                        @if($campaign->main_image)
                            <img src="{{ asset('storage/' . $campaign->main_image) }}" 
                                 alt="{{ $campaign->title }}"
                                 class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full bg-gradient-to-br from-gray-400 to-gray-600"></div>
                        @endif
                    </div>
                    
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-xs bg-gray-100 text-gray-800 px-2 py-1 rounded">
                                {{ ucfirst($campaign->category) }}
                            </span>
                            <span class="text-xs text-gray-500">
                                {{ $campaign->created_at->diffForHumans() }}
                            </span>
                        </div>

                        <h3 class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2">
                            {{ $campaign->title }}
                        </h3>

                        <p class="text-gray-600 text-sm mb-4 line-clamp-2">
                            {{ $campaign->summary }}
                        </p>

                        <div class="flex justify-between items-center">
                            <div class="text-sm">
                                <span class="font-semibold text-emerald-600">
                                    {{ number_format($campaign->progress_percentage, 0) }}% funded
                                </span>
                            </div>
                            <a href="{{ route('campaigns.show', $campaign) }}" 
                               class="text-emerald-600 text-sm font-medium hover:text-emerald-700">
                                View Campaign →
                            </a>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="py-20 bg-emerald-600 text-white">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-4xl font-bold mb-6">Ready to Make a Difference?</h2>
            <p class="text-xl mb-8 max-w-2xl mx-auto opacity-90">
                Join thousands of Cape Town residents creating positive change in their communities
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('campaigns.list') }}" 
                   class="bg-white text-emerald-600 px-8 py-4 rounded-lg font-semibold text-lg hover:bg-gray-100 transition-colors">
                    Start Donating
                </a>
                <a href="{{ route('events.index') }}" 
                   class="border-2 border-white px-8 py-4 rounded-lg font-semibold text-lg hover:bg-white hover:text-emerald-600 transition-colors">
                    Become a Volunteer
                </a>
            </div>
        </div>
    </section>
</div>

<script>
function featuredCampaigns() {
    return {
        // Add any Alpine.js functionality here
        init() {
            // Initialize animations or interactions
        }
    }
}
</script>
@endsection