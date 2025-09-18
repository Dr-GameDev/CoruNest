@extends('layouts.app')

@section('title', 'Donate to ' . $campaign->title . ' - CoruNest')
@section('description', 'Support ' . $campaign->title . '. ' . Str::limit($campaign->summary, 150))

@section('content')
<div class="py-12 bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4">
        <div class="max-w-4xl mx-auto">
            <!-- Back to Campaign -->
            <div class="mb-6">
                <a href="{{ route('campaigns.show', $campaign) }}" 
                   class="inline-flex items-center text-emerald-600 hover:text-emerald-700 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Campaign
                </a>
            </div>

            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Donation Form -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="p-6 border-b border-gray-200">
                            <h1 class="text-2xl font-bold text-gray-900">Make a Donation</h1>
                            <p class="text-gray-600 mt-2">
                                Support <strong>{{ $campaign->title }}</strong>
                            </p>
                        </div>

                        <form id="donationForm" 
                              x-data="donationForm()" 
                              @submit.prevent="submitDonation()"
                              class="p-6 space-y-6">
                            @csrf
                            <input type="hidden" name="campaign_id" value="{{ $campaign->id }}">

                            <!-- Amount Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Donation Amount (ZAR)
                                </label>
                                <div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-4">
                                    @foreach($suggestedAmounts as $amount)
                                    <button type="button"
                                            @click="selectAmount({{ $amount }})"
                                            :class="selectedAmount === {{ $amount }} ? 
                                                'bg-emerald-600 text-white border-emerald-600' : 
                                                'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'"
                                            class="border-2 rounded-lg px-4 py-3 font-semibold transition-colors">
                                        R{{ number_format($amount) }}
                                    </button>
                                    @endforeach
                                </div>
                                
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">R</span>
                                    </div>
                                    <input type="number" 
                                           name="amount" 
                                           x-model="customAmount"
                                           @input="selectedAmount = parseFloat(customAmount) || 0"
                                           placeholder="Enter custom amount"
                                           min="10" 
                                           max="50000" 
                                           class="block w-full pl-8 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                                <p class="text-sm text-gray-500 mt-2">
                                    Minimum donation: R10. Maximum: R50,000
                                </p>
                                @error('amount')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Payment Provider -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-3">
                                    Payment Method
                                </label>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div class="relative">
                                        <input type="radio" 
                                               name="payment_provider" 
                                               value="yoco" 
                                               x-model="paymentProvider"
                                               id="yoco" 
                                               class="sr-only">
                                        <label for="yoco" 
                                               :class="paymentProvider === 'yoco' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300'"
                                               class="block border-2 rounded-lg p-4 cursor-pointer hover:bg-gray-50 transition-colors">
                                            <div class="flex items-center">
                                                <div class="flex-1">
                                                    <div class="font-semibold text-gray-900">Yoco</div>
                                                    <div class="text-sm text-gray-500">Credit/Debit Cards</div>
                                                </div>
                                                <div class="ml-3">
                                                    <svg x-show="paymentProvider === 'yoco'" class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </label>
                                    </div>

                                    <div class="relative">
                                        <input type="radio" 
                                               name="payment_provider" 
                                               value="ozow" 
                                               x-model="paymentProvider"
                                               id="ozow" 
                                               class="sr-only">
                                        <label for="ozow" 
                                               :class="paymentProvider === 'ozow' ? 'border-emerald-500 bg-emerald-50' : 'border-gray-300'"
                                               class="block border-2 rounded-lg p-4 cursor-pointer hover:bg-gray-50 transition-colors">
                                            <div class="flex items-center">
                                                <div class="flex-1">
                                                    <div class="font-semibold text-gray-900">Ozow</div>
                                                    <div class="text-sm text-gray-500">Internet Banking</div>
                                                </div>
                                                <div class="ml-3">
                                                    <svg x-show="paymentProvider === 'ozow'" class="w-5 h-5 text-emerald-600" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                @error('payment_provider')
                                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- Donor Information (for non-logged-in users) -->
                            @guest
                            <div class="space-y-4">
                                <h3 class="text-lg font-semibold text-gray-900">Your Information</h3>
                                <div class="grid md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="donor_name" class="block text-sm font-medium text-gray-700 mb-1">
                                            Full Name *
                                        </label>
                                        <input type="text" 
                                               name="donor_name" 
                                               id="donor_name"
                                               x-model="donorName"
                                               required
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        @error('donor_name')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="donor_email" class="block text-sm font-medium text-gray-700 mb-1">
                                            Email Address *
                                        </label>
                                        <input type="email" 
                                               name="donor_email" 
                                               id="donor_email"
                                               x-model="donorEmail"
                                               required
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                        @error('donor_email')
                                            <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>
                                <div>
                                    <label for="donor_phone" class="block text-sm font-medium text-gray-700 mb-1">
                                        Phone Number (Optional)
                                    </label>
                                    <input type="tel" 
                                           name="donor_phone" 
                                           id="donor_phone"
                                           x-model="donorPhone"
                                           placeholder="+27 21 123 4567"
                                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                            </div>
                            @endguest

                            <!-- Message -->
                            <div>
                                <label for="donor_message" class="block text-sm font-medium text-gray-700 mb-1">
                                    Message (Optional)
                                </label>
                                <textarea name="donor_message" 
                                          id="donor_message"
                                          x-model="donorMessage"
                                          rows="3"
                                          placeholder="Leave a message of support..."
                                          class="block w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                                <p class="text-sm text-gray-500 mt-1">Maximum 500 characters</p>
                            </div>

                            <!-- Options -->
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="anonymous" 
                                           id="anonymous"
                                           x-model="anonymous"
                                           class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                    <label for="anonymous" class="ml-2 text-sm text-gray-700">
                                        Make my donation anonymous
                                    </label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" 
                                           name="recurring" 
                                           id="recurring"
                                           x-model="recurring"
                                           class="h-4 w-4 text-emerald-600 focus:ring-emerald-500 border-gray-300 rounded">
                                    <label for="recurring" class="ml-2 text-sm text-gray-700">
                                        Make this a monthly donation
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="border-t border-gray-200 pt-6">
                                <button type="submit"
                                        :disabled="!canSubmit || processing"
                                        :class="canSubmit && !processing ? 
                                            'bg-emerald-600 hover:bg-emerald-700 text-white' : 
                                            'bg-gray-300 text-gray-500 cursor-not-allowed'"
                                        class="w-full py-4 px-6 rounded-lg font-semibold text-lg transition-colors flex items-center justify-center">
                                    
                                    <template x-if="processing">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </template>
                                    
                                    <span x-text="processing ? 'Processing...' : 'Donate R' + selectedAmount.toLocaleString()">
                                        Donate R{{ number_format($suggestedAmounts[0]) }}
                                    </span>
                                </button>
                                
                                <p class="text-xs text-gray-500 text-center mt-3">
                                    Secure payment powered by 
                                    <span x-text="paymentProvider === 'yoco' ? 'Yoco' : 'Ozow'">Yoco</span>
                                </p>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Campaign Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-md overflow-hidden sticky top-24">
                        <!-- Campaign Image -->
                        <div class="aspect-video bg-gray-200">
                            @if($campaign->main_image)
                                <img src="{{ asset('storage/' . $campaign->main_image) }}" 
                                     alt="{{ $campaign->title }}"
                                     class="w-full h-full object-cover">
                            @else
                                <div class="w-full h-full bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center">
                                    <svg class="w-12 h-12 text-white" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                </div>
                            @endif
                        </div>

                        <div class="p-6">
                            <h2 class="text-lg font-bold text-gray-900 mb-2 line-clamp-2">
                                {{ $campaign->title }}
                            </h2>

                            <p class="text-gray-600 text-sm mb-4 line-clamp-3">
                                {{ $campaign->summary }}
                            </p>

                            <!-- Progress -->
                            <div class="mb-4">
                                <div class="flex justify-between items-center mb-2">
                                    <span class="text-sm font-medium text-gray-700">Progress</span>
                                    <span class="text-sm font-medium text-emerald-600">
                                        {{ number_format($campaign->progress_percentage, 0) }}%
                                    </span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="bg-emerald-600 h-2 rounded-full" 
                                         style="width: {{ $campaign->progress_percentage }}%"></div>
                                </div>
                            </div>

                            <!-- Stats -->
                            <div class="grid grid-cols-2 gap-4 text-center border-t border-gray-200 pt-4">
                                <div>
                                    <div class="text-xl font-bold text-gray-900">
                                        {{ $campaign->formatted_current_amount }}
                                    </div>
                                    <div class="text-sm text-gray-500">raised</div>
                                </div>
                                <div>
                                    <div class="text-xl font-bold text-gray-900">
                                        {{ $campaign->donor_count }}
                                    </div>
                                    <div class="text-sm text-gray-500">donors</div>
                                </div>
                            </div>

                            <div class="text-center mt-4">
                                <div class="text-sm text-gray-500 mb-1">Goal</div>
                                <div class="text-lg font-semibold text-gray-900">
                                    {{ $campaign->formatted_target_amount }}
                                </div>
                                @if($campaign->days_remaining)
                                    <div class="text-sm text-gray-500 mt-1">
                                        {{ $campaign->days_remaining }} days remaining
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function donationForm() {
    return {
        selectedAmount: {{ $suggestedAmounts[0] }},
        customAmount: '',
        paymentProvider: 'yoco',
        donorName: '',
        donorEmail: '',
        donorPhone: '',
        donorMessage: '',
        anonymous: false,
        recurring: false,
        processing: false,

        get canSubmit() {
            return this.selectedAmount >= 10 && 
                   this.selectedAmount <= 50000 && 
                   this.paymentProvider &&
                   @guest
                   this.donorName.trim() && 
                   this.donorEmail.trim() &&
                   @endguest
                   !this.processing;
        },

        selectAmount(amount) {
            this.selectedAmount = amount;
            this.customAmount = amount.toString();
        },

        async submitDonation() {
            if (!this.canSubmit) return;
            
            this.processing = true;

            try {
                const formData = new FormData();
                formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
                formData.append('campaign_id', {{ $campaign->id }});
                formData.append('amount', this.selectedAmount);
                formData.append('payment_provider', this.paymentProvider);
                
                @guest
                formData.append('donor_name', this.donorName);
                formData.append('donor_email', this.donorEmail);
                formData.append('donor_phone', this.donorPhone);
                @endguest
                
                formData.append('donor_message', this.donorMessage);
                formData.append('anonymous', this.anonymous ? '1' : '0');
                formData.append('recurring', this.recurring ? '1' : '0');

                const response = await fetch('{{ route("donations.store") }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    if (data.payment_url) {
                        window.location.href = data.payment_url;
                    } else if (data.redirect_url) {
                        window.location.href = data.redirect_url;
                    }
                } else {
                    alert(data.message || 'An error occurred. Please try again.');
                }
            } catch (error) {
                console.error('Donation submission error:', error);
                alert('An error occurred. Please try again.');
            } finally {
                this.processing = false;
            }
        }
    }
}
</script>
@endsection