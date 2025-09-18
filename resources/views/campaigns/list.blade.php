{{-- resources/views/campaigns/list.blade.php --}}
@extends('layouts.app')

@section('title', 'Browse Campaigns - CoruNest')
@section('description', 'Discover and support active fundraising campaigns making a real impact in Cape Town communities.')

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <!-- Page Header -->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900 mb-4">Browse Campaigns</h1>
            <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                Discover and support active fundraising campaigns making a real impact in Cape Town communities.
            </p>
        </div>

        <!-- Filters & Search -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8" x-data="campaignFilters()">
            <div class="grid md:grid-cols-4 gap-4 items-end">
                <!-- Search -->
                <div class="md:col-span-2">
                    <label for="search" class="block text-sm font-medium text-gray-700 mb-2">
                        Search Campaigns
                    </label>
                    <div class="relative">
                        <input type="text" 
                               id="search"
                               x-model="search"
                               @input.debounce.300ms="updateFilters()"
                               placeholder="Search by title, description, or category..."
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                    </div>
                </div>

                <!-- Category Filter -->
                <div>
                    <label for="category" class="block text-sm font-medium text-gray-700 mb-2">
                        Category
                    </label>
                    <select id="category" 
                            x-model="category"
                            @change="updateFilters()"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="">All Categories</option>
                        @foreach($categories as $key => $name)
                            <option value="{{ $key }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sort -->
                <div>
                    <label for="sort" class="block text-sm font-medium text-gray-700 mb-2">
                        Sort By
                    </label>
                    <select id="sort" 
                            x-model="sort"
                            @change="updateFilters()"
                            class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                        <option value="latest">Most Recent</option>
                        <option value="ending_soon">Ending Soon</option>
                        <option value="progress">Least Funded</option>
                        <option value="featured">Featured First</option>
                    </select>
                </div>
            </div>

            <!-- Active Filters -->
            <div x-show="hasActiveFilters()" class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-200">
                <span class="text-sm text-gray-500">Active filters:</span>
                
                <template x-if="search">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 text-emerald-800 text-xs rounded-full">
                        <span x-text="'Search: ' + search"></span>
                        <button @click="search = ''; updateFilters()" class="text-emerald-600 hover:text-emerald-800">×</button>
                    </span>
                </template>
                
                <template x-if="category">
                    <span class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-100 text-emerald-800 text-xs rounded-full">
                        <span x-text="'Category: ' + getCategoryName(category)"></span>
                        <button @click="category = ''; updateFilters()" class="text-emerald-600 hover:text-emerald-800">×</button>
                    </span>
                </template>
                
                <button @click="clearAllFilters()" class="text-sm text-gray-500 hover:text-gray-700 underline ml-2">
                    Clear all
                </button>
            </div>
        </div>

        <!-- Results Count -->
        <div class="flex justify-between items-center mb-6">
            <p class="text-gray-600">
                Showing {{ $campaigns->count() }} of {{ $campaigns->total() }} campaigns
            </p>
            
            <!-- View Toggle -->
            <div class="flex rounded-lg border border-gray-300" x-data="{ view: 'grid' }">
                <button @click="view = 'grid'" 
                        :class="view === 'grid' ? 'bg-emerald-50 text-emerald-700' : 'text-gray-500 hover:text-gray-700'"
                        class="px-3 py-1 rounded-l-lg border-r border-gray-300 transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </button>
                <button @click="view = 'list'" 
                        :class="view === 'list' ? 'bg-emerald-50 text-emerald-700' : 'text-gray-500 hover:text-gray-700'"
                        class="px-3 py-1 rounded-r-lg transition-colors">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Campaigns Grid/List -->
        <div x-data="{ view: 'grid' }">
            <!-- Grid View -->
            <div x-show="view === 'grid'" class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                @forelse($campaigns as $campaign)
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
                            
                            @if($campaign->featured)
                                <div class="absolute top-3 left-3">
                                    <span class="bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-medium">
                                        Featured
                                    </span>
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
                                <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-emerald-600 transition-colors">
                                    {{ $campaign->title }}
                                </a>
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
                                        {{ number_format($campaign->donor_count) }}
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
                @empty
                    <div class="col-span-full text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No campaigns found</h3>
                        <p class="mt-1 text-gray-500">Try adjusting your search criteria or check back later for new campaigns.</p>
                    </div>
                @endforelse
            </div>

            <!-- List View -->
            <div x-show="view === 'list'" class="space-y-6 mb-12">
                @forelse($campaigns as $campaign)
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-md transition-shadow">
                        <div class="flex gap-6">
                            <!-- Campaign Image -->
                            <div class="w-48 h-32 bg-gray-200 rounded-lg overflow-hidden flex-shrink-0">
                                @if($campaign->main_image)
                                    <img src="{{ asset('storage/' . $campaign->main_image) }}" 
                                         alt="{{ $campaign->title }}"
                                         class="w-full h-full object-cover">
                                @else
                                    <div class="w-full h-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center">
                                        <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </div>
                                @endif
                            </div>

                            <!-- Campaign Content -->
                            <div class="flex-1">
                                <div class="flex justify-between items-start mb-2">
                                    <div class="flex items-center gap-3">
                                        <span class="bg-emerald-100 text-emerald-800 text-sm font-medium px-2 py-1 rounded">
                                            {{ ucfirst($campaign->category) }}
                                        </span>
                                        @if($campaign->featured)
                                            <span class="bg-yellow-100 text-yellow-800 text-sm font-medium px-2 py-1 rounded">
                                                Featured
                                            </span>
                                        @endif
                                    </div>
                                    @if($campaign->days_remaining)
                                        <span class="text-sm text-gray-500">
                                            {{ $campaign->days_remaining }} days left
                                        </span>
                                    @endif
                                </div>

                                <h3 class="text-xl font-bold text-gray-900 mb-2">
                                    <a href="{{ route('campaigns.show', $campaign) }}" class="hover:text-emerald-600 transition-colors">
                                        {{ $campaign->title }}
                                    </a>
                                </h3>

                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    {{ $campaign->summary }}
                                </p>

                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-6">
                                        <div>
                                            <div class="text-lg font-bold text-gray-900">
                                                {{ $campaign->formatted_current_amount }}
                                            </div>
                                            <div class="text-sm text-gray-500">
                                                of {{ $campaign->formatted_target_amount }}
                                            </div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-emerald-600">
                                                {{ number_format($campaign->progress_percentage, 0) }}%
                                            </div>
                                            <div class="text-sm text-gray-500">funded</div>
                                        </div>
                                        <div>
                                            <div class="text-lg font-semibold text-gray-700">
                                                {{ number_format($campaign->donor_count) }}
                                            </div>
                                            <div class="text-sm text-gray-500">donors</div>
                                        </div>
                                    </div>

                                    <div class="flex gap-2">
                                        <a href="{{ route('campaigns.show', $campaign) }}" 
                                           class="px-4 py-2 border border-gray-300 text-gray-700 rounded-lg font-medium hover:bg-gray-50 transition-colors">
                                            View Details
                                        </a>
                                        <a href="{{ route('campaigns.donate', $campaign) }}" 
                                           class="px-4 py-2 bg-emerald-600 text-white rounded-lg font-medium hover:bg-emerald-700 transition-colors">
                                            Donate
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                        <h3 class="mt-2 text-lg font-medium text-gray-900">No campaigns found</h3>
                        <p class="mt-1 text-gray-500">Try adjusting your search criteria or check back later for new campaigns.</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Pagination -->
        @if($campaigns->hasPages())
            <div class="flex justify-center">
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
                    {{ $campaigns->links() }}
                </div>
            </div>
        @endif
    </div>
</div>

<script>
function campaignFilters() {
    return {
        search: '{{ $filters['search'] ?? '' }}',
        category: '{{ $filters['category'] ?? '' }}',
        sort: '{{ $filters['sort'] ?? 'latest' }}',

        categories: @json($categories),

        hasActiveFilters() {
            return this.search || this.category || this.sort !== 'latest';
        },

        getCategoryName(key) {
            return this.categories[key] || key;
        },

        updateFilters() {
            const params = new URLSearchParams();
            
            if (this.search) params.set('search', this.search);
            if (this.category) params.set('category', this.category);
            if (this.sort && this.sort !== 'latest') params.set('sort', this.sort);
            
            const url = new URL(window.location);
            url.search = params.toString();
            
            window.location.href = url.toString();
        },

        clearAllFilters() {
            this.search = '';
            this.category = '';
            this.sort = 'latest';
            this.updateFilters();
        }
    }
}
</script>
@endsection