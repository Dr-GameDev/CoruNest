<div class="donation-form" x-data="donationForm()">
    <h3 class="text-lg font-semibold mb-4">Make a Donation</h3>
    
    <form @submit.prevent="submitDonation()">
        <!-- Amount Selection -->
        <div class="donation-amounts mb-4">
            <template x-for="amount in predefinedAmounts" :key="amount">
                <button type="button" 
                        class="amount-btn" 
                        :class="{ 'selected': selectedAmount === amount }"
                        @click="selectAmount(amount)">
                    R<span x-text="amount"></span>
                </button>
            </template>
        </div>
        
        <div class="custom-amount-group mb-4">
            <input type="number" 
                   x-model="customAmount" 
                   @input="selectedAmount = null"
                   placeholder="Enter custom amount" 
                   class="custom-amount"
                   min="10">
        </div>
        
        <!-- Donor Information -->
        <div class="donor-info mb-4">
            <div class="form-group mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" 
                       x-model="donorName" 
                       class="form-input" 
                       required>
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" 
                       x-model="donorEmail" 
                       class="form-input" 
                       required>
            </div>
            
            <div class="form-group mb-3">
                <label class="form-label">Phone (Optional)</label>
                <input type="tel" 
                       x-model="donorPhone" 
                       class="form-input">
            </div>
        </div>
        
        <!-- Payment Method -->
        <div class="payment-methods mb-4">
            <label class="form-label mb-2">Payment Method</label>
            <div class="payment-options">
                <label class="payment-option" :class="{ 'selected': paymentMethod === 'yoco' }">
                    <input type="radio" 
                           name="payment_method" 
                           value="yoco" 
                           x-model="paymentMethod">
                    <div class="payment-content">
                        <i class="fas fa-credit-card"></i>
                        <span>Credit Card</span>
                    </div>
                </label>
                
                <label class="payment-option" :class="{ 'selected': paymentMethod === 'ozow' }">
                    <input type="radio" 
                           name="payment_method" 
                           value="ozow" 
                           x-model="paymentMethod">
                    <div class="payment-content">
                        <i class="fas fa-university"></i>
                        <span>EFT (Ozow)</span>
                    </div>
                </label>
            </div>
        </div>
        
        <!-- Terms and Privacy -->
        <div class="form-group mb-4">
            <label class="checkbox-label">
                <input type="checkbox" x-model="agreedToTerms" required>
                <span class="text-sm">
                    I agree to the <a href="/terms" target="_blank" class="text-primary-600">Terms & Conditions</a>
                    and <a href="/privacy" target="_blank" class="text-primary-600">Privacy Policy</a>
                </span>
            </label>
        </div>
        
        <div class="form-group mb-4">
            <label class="checkbox-label">
                <input type="checkbox" x-model="allowMarketing">
                <span class="text-sm">
                    Send me updates about this campaign and similar causes
                </span>
            </label>
        </div>
        
        <!-- Donate Button -->
        <button type="submit" 
                class="btn btn-primary w-full" 
                :disabled="loading || !isFormValid()">
            <template x-if="!loading">
                <span>
                    <i class="fas fa-heart"></i> 
                    Donate R<span x-text="getDonationAmount()"></span>
                </span>
            </template>
            <template x-if="loading">
                <span class="flex items-center justify-center">
                    <div class="loading-spinner mr-2"></div>
                    Processing...
                </span>
            </template>
        </button>
        
        <!-- Security Notice -->
        <div class="security-notice mt-3 text-center">
            <div class="text-xs text-gray-500 flex items-center justify-center">
                <i class="fas fa-lock mr-1"></i>
                Secure payment powered by {{ config('services.payment.default') === 'yoco' ? 'Yoco' : 'Ozow' }}
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
    function donationForm() {
        return {
            selectedAmount: null,
            customAmount: '',
            donorName: '',
            donorEmail: '',
            donorPhone: '',
            paymentMethod: 'yoco',
            agreedToTerms: false,
            allowMarketing: false,
            loading: false,
            predefinedAmounts: [50, 100, 250, 500, 1000, 2500],
            
            selectAmount(amount) {
                this.selectedAmount = amount;
                this.customAmount = '';
            },
            
            getDonationAmount() {
                return this.customAmount || this.selectedAmount || 0;
            },
            
            isFormValid() {
                return this.getDonationAmount() >= 10 && 
                       this.donorName && 
                       this.donorEmail && 
                       this.paymentMethod && 
                       this.agreedToTerms;
            },
            
            async submitDonation() {
                if (!this.isFormValid()) {
                    alert('Please complete all required fields');
                    return;
                }
                
                this.loading = true;
                
                try {
                    const response = await fetch('/api/donate', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            campaign_id: {{ $campaign->id }},
                            amount: this.getDonationAmount(),
                            donor_name: this.donorName,
                            donor_email: this.donorEmail,
                            donor_phone: this.donorPhone,
                            payment_method: this.paymentMethod,
                            allow_marketing: this.allowMarketing
                        })
                    });
                    
                    const result = await response.json();
                    
                    if (response.ok) {
                        // Redirect to payment gateway
                        if (result.payment_url) {
                            window.location.href = result.payment_url;
                        }
                    } else {
                        throw new Error(result.message || 'Something went wrong');
                    }
                } catch (error) {
                    alert('Error: ' + error.message);
                } finally {
                    this.loading = false;
                }
            }
        }
    }
</script>
@endpush