@extends('layouts.app')

@section('title', $campaign->title)

@section('content')
<div class="campaign-detail" x-data="campaignDetail({{ $campaign->id }})">
    <!-- Hero Section -->
    <section class="campaign-hero">
        <div class="container mx-auto px-4 py-8">
            <div class="grid md:grid-cols-2 gap-8 items-center">
                <div>
                    <div class="campaign-meta mb-4">
                        <span class="category-badge">{{ ucfirst($campaign->category) }}</span>
                        <span class="created-date">{{ $campaign->created_at->format('M d, Y') }}</span>
                    </div>
                    
                    <h1 class="text-4xl font-bold mb-4">{{ $campaign->title }}</h1>
                    <p class="text-xl text-gray-600 mb-6">{{ $campaign->summary }}</p>
                    
                    <div class="campaign-stats grid grid-cols-3 gap-4 mb-6">
                        <div class="stat">
                            <div class="stat-value">R{{ number_format($campaign->current_amount) }}</div>
                            <div class="stat-label">Raised</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">{{ $campaign->donors_count }}</div>
                            <div class="stat-label">Donors</div>
                        </div>
                        <div class="stat">
                            <div class="stat-value">{{ $campaign->days_left }}</div>
                            <div class="stat-label">Days Left</div>
                        </div>
                    </div>
                    
                    <div class="progress-section mb-6">
                        <div class="progress-info flex justify-between mb-2">
                            <span class="raised-amount text-primary-600 font-semibold">
                                R{{ number_format($campaign->current_amount) }}
                            </span>
                            <span class="target-amount text-gray-500">
                                of R{{ number_format($campaign->target_amount) }}
                            </span>
                        </div>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: {{ $campaign->progress_percentage }}%"></div>
                        </div>
                        <div class="progress-percentage mt-1 text-sm text-gray-500">
                            {{ $campaign->progress_percentage }}% funded
                        </div>
                    </div>
                    
                    <button @click="showDonationModal = true" class="btn btn-primary btn-lg">
                        <i class="fas fa-heart"></i>
                        Donate Now
                    </button>
                </div>
                
                <div class="campaign-image">
                    @if($campaign->image_path)
                        <img src="{{ asset('storage/' . $campaign->image_path) }}" 
                             alt="{{ $campaign->title }}" 
                             class="w-full h-80 object-cover rounded-lg shadow-lg">
                    @else
                        <div class="campaign-image-placeholder"></div>
                    @endif
                </div>
            </div>
        </div>
    </section>
    
    <!-- Campaign Content -->
    <section class="campaign-content py-12 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8">
                <div class="md:col-span-2">
                    <div class="card">
                        <h2 class="text-2xl font-bold mb-4">About This Campaign</h2>
                        <div class="prose max-w-none">
                            {!! nl2br(e($campaign->body)) !!}
                        </div>
                    </div>
                    
                    <!-- Recent Donations -->
                    <div class="card mt-8">
                        <h3 class="text-xl font-bold mb-4">Recent Donations</h3>
                        <div class="donations-list">
                            @forelse($campaign->recent_donations as $donation)
                                <div class="donation-item">
                                    <div class="donor-avatar">
                                        {{ substr($donation->donor_name ?? 'Anonymous', 0, 1) }}
                                    </div>
                                    <div class="donation-info">
                                        <div class="donor-name">
                                            {{ $donation->donor_name ?? 'Anonymous Donor' }}
                                        </div>
                                        <div class="donation-time">
                                            {{ $donation->created_at->diffForHumans() }}
                                        </div>
                                    </div>
                                    <div class="donation-amount">
                                        R{{ number_format($donation->amount) }}
                                    </div>
                                </div>
                            @empty
                                <p class="text-gray-500">No donations yet. Be the first to contribute!</p>
                            @endforelse
                        </div>
                    </div>
                </div>
                
                <!-- Donation Form Sidebar -->
                <div class="donation-sidebar">
                    @include('components.donation-form', ['campaign' => $campaign])
                    
                    <!-- Share Campaign -->
                    <div class="card mt-6">
                        <h4 class="font-semibold mb-3">Share This Campaign</h4>
                        <div class="share-buttons">
                            <a href="#" class="share-btn facebook">
                                <i class="fab fa-facebook-f"></i>
                            </a>
                            <a href="#" class="share-btn twitter">
                                <i class="fab fa-twitter"></i>
                            </a>
                            <a href="#" class="share-btn whatsapp">
                                <i class="fab fa-whatsapp"></i>
                            </a>
                            <a href="#" class="share-btn email">
                                <i class="fas fa-envelope"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

@include('components.donation-modal')
@endsection

@push('scripts')
<script>
    function campaignDetail(campaignId) {
        return {
            campaignId: campaignId,
            showDonationModal: false,
            
            init() {
                // Track page view
                this.trackView();
            },
            
            trackView() {
                fetch(`/api/campaigns/${this.campaignId}/view`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Content-Type': 'application/json'
                    }
                });
            }
        }
    }
</script>
@endpush