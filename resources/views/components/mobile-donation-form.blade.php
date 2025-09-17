<div x-data="{
    step: 1,
    selectedAmount: null,
    customAmount: '',
    donorInfo: {
        name: '',
        email: '',
        phone: ''
    },
    paymentMethod: 'yoco',
    loading: false
}">
    
    <!-- Step 1: Amount Selection -->
    <div x-show="step === 1" x-transition>
        <h4 class="font-semibold mb-3">Choose Amount</h4>
        
        <div class="grid grid-cols-3 gap-2 mb-4">
            <template x-for="amount in [50, 100, 250, 500, 1000, 2500]" :key="amount">
                <button type="button" 
                        class="amount-btn text-center py-3 rounded-lg border-2 transition-all"
                        :class="selectedAmount === amount ? 'border-primary-500 bg-primary-50 text-primary-600' : 'border-gray-300'"
                        @click="selectedAmount = amount; customAmount = ''; vibrate()">
                    R<span x-text="amount"></span>
                </button>
            </template>
        </div>
        
        <input type="number" 
               x-model="customAmount" 
               @input="selectedAmount = null"
               placeholder="Enter custom amount" 
               class="w-full p-3 border border-gray-300 rounded-lg mb-4"
               min="10">
        
        <button @click="step = 2; vibrate()" 
                :disabled="!selectedAmount && !customAmount"
                class="w-full btn btn-primary py-3 text-lg">
            Continue
        </button>
    </div>
    
    <!-- Step 2: Donor Information -->
    <div x-show="step === 2" x-transition>
        <div class="flex items-center justify-between mb-4">
            <button @click="step = 1; vibrate()" class="text-gray-500">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h4 class="font-semibold">Your Details</h4>
            <div class="w-6"></div>
        </div>
        
        <div class="space-y-3 mb-6">
            <input type="text" 
                   x-model="donorInfo.name"
                   placeholder="Full Name" 
                   class="w-full p-3 border border-gray-300 rounded-lg"
                   required>
            
            <input type="email" 
                   x-model="donorInfo.email"
                   placeholder="Email Address" 
                   class="w-full p-3 border border-gray-300 rounded-lg"
                   required>
            
            <input type="tel" 
                   x-model="donorInfo.phone"
                   placeholder="Phone Number (Optional)" 
                   class="w-full p-3 border border-gray-300 rounded-lg">
        </div>
        
        <button @click="step = 3; vibrate()" 
                :disabled="!donorInfo.name || !donorInfo.email"
                class="w-full btn btn-primary py-3 text-lg">
            Continue
        </button>
    </div>
    
    <!-- Step 3: Payment Method -->
    <div x-show="step === 3" x-transition>
        <div class="flex items-center justify-between mb-4">
            <button @click="step = 2; vibrate()" class="text-gray-500">
                <i class="fas fa-arrow-left"></i>
            </button>
            <h4 class="font-semibold">Payment Method</h4>
            <div class="w-6"></div>
        </div>
        
        <div class="space-y-3 mb-6">
            <label class="payment-option flex items-center p-4 border-2 rounded-lg cursor-pointer"
                   :class="paymentMethod === 'yoco' ? 'border-primary-500 bg-primary-50' : 'border-gray-300'">
                <input type="radio" 
                       name="payment_method" 
                       value="yoco" 
                       x-model="paymentMethod"
                       class="sr-only">
                <div class="flex items-center gap-3 w-full">
                    <i class="fas fa-credit-card text-xl"></i>
                    <div class="flex-1">
                        <div class="font-semibold">Credit Card</div>
                        <div class="text-sm text-gray-600">Visa, Mastercard</div>
                    </div>
                    <i x-show="paymentMethod === 'yoco'" class="fas fa-check text-primary-600"></i>
                </div>
            </label>
            
            <label class="payment-option flex items-center p-4 border-2 rounded-lg cursor-pointer"
                   :class="paymentMethod === 'ozow' ? 'border-primary-500 bg-primary-50' : 'border-gray-300'">
                <input type="radio" 
                       name="payment_method" 
                       value="ozow" 
                       x-model="paymentMethod"
                       class="sr-only">
                <div class="flex items-center gap-3 w-full">
                    <i class="fas fa-university text-xl"></i>
                    <div class="flex-1">
                        <div class="font-semibold">Bank Transfer</div>
                        <div class="text-sm text-gray-600">EFT via Ozow</div>
                    </div>
                    <i x-show="paymentMethod === 'ozow'" class="fas fa-check text-primary-600"></i>
                </div>
            </label>
        </div>
        
        <!-- Donation Summary -->
        <div class="bg-gray-50 p-4 rounded-lg mb-6">
            <h5 class="font-semibold mb-2">Donation Summary</h5>
            <div class="flex justify-between items-center">
                <span>Amount:</span>
                <span class="font-bold text-primary-600">R<span x-text="selectedAmount || customAmount"></span></span>
            </div>
        </div>
        
        <button @click="submitMobileDonation()" 
                :disabled="loading"
                class="w-full btn btn-primary py-4 text-lg">
            <template x-if="!loading">
                <span>
                    <i class="fas fa-heart mr-2"></i>
                    Donate R<span x-text="selectedAmount || customAmount"></span>
                </span>
            </template>
            <template x-if="loading">
                <span class="flex items-center justify-center">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-white mr-2"></div>
                    Processing...
                </span>
            </template>
        </button>
    </div>
    
    <script>
        function submitMobileDonation() {
            this.loading = true;
            vibrate([10, 100, 10]);
            
            const formData = {
                campaign_id: 1, // This would be dynamic
                amount: this.selectedAmount || this.customAmount,
                donor_name: this.donorInfo.name,
                donor_email: this.donorInfo.email,
                donor_phone: this.donorInfo.phone,
                payment_method: this.paymentMethod
            };
            
            // Check if offline
            if (!navigator.onLine) {
                // Store offline
                const offlineData = {
                    id: Date.now(),
                    type: 'donations',
                    data: formData,
                    timestamp: new Date().toISOString(),
                    csrfToken: document.querySelector('meta[name="csrf-token"]').content
                };
                
                // Store in localStorage as fallback
                const offlineQueue = JSON.parse(localStorage.getItem('corunest_offline_queue') || '[]');
                offlineQueue.push(offlineData);
                localStorage.setItem('corunest_offline_queue', JSON.stringify(offlineQueue));
                
                // Show success message
                this.showMobileToast('Donation saved! It will be processed when you\'re back online.');
                this.resetMobileForm();
                this.loading = false;
                return;
            }
            
            // Online submission
            fetch('/api/donate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(result => {
                if (result.payment_url) {
                    // Use Capacitor to open payment URL in system browser
                    if (window.Capacitor) {
                        App.openUrl({ url: result.payment_url });
                    } else {
                        window.location.href = result.payment_url;
                    }
                }
            })
            .catch(error => {
                console.error('Payment error:', error);
                this.showMobileToast('Payment failed. Please try again.');
            })
            .finally(() => {
                this.loading = false;
            });
        }
        
        function showMobileToast(message) {
            const toast = document.createElement('div');
            toast.className = 'mobile-toast';
            toast.textContent = message;
            document.body.appendChild(toast);
            
            setTimeout(() => {
                if (document.body.contains(toast)) {
                    document.body.removeChild(toast);
                }
            }, 4000);
        }
        
        function resetMobileForm() {
            this.step = 1;
            this.selectedAmount = null;
            this.customAmount = '';
            this.donorInfo = { name: '', email: '', phone: '' };
            this.paymentMethod = 'yoco';
            this.showDonationForm = false;
        }
    </script>
</div>