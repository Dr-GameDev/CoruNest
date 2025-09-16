<!-- ===== DONATION FORM MODAL (resources/views/components/donation-form.blade.php) ===== -->
    <div x-data="donationForm()" x-show="showModal" x-cloak 
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="donation-form max-w-md w-full" @click.away="showModal = false">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Make a Donation</h3>
                <button @click="showModal = false" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <form @submit.prevent="submitDonation()">
                <div class="donation-amounts">
                    <template x-for="amount in predefinedAmounts" :key="amount">
                        <button type="button" class="amount-btn" 
                                :class="{ 'selected': selectedAmount === amount }"
                                @click="selectedAmount = amount; customAmount = ''">
                            R<span x-text="amount"></span>
                        </button>
                    </template>
                </div>

                <input type="number" x-model="customAmount" @input="selectedAmount = null"
                       placeholder="Enter custom amount" class="custom-amount">

                <div class="form-group">
                    <label class="form-label">Full Name</label>
                    <input type="text" x-model="donorName" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Email Address</label>
                    <input type="email" x-model="donorEmail" class="form-input" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Payment Method</label>
                    <div class="payment-methods">
                        <button type="button" class="payment-method"
                                :class="{ 'selected': paymentMethod === 'yoco' }"
                                @click="paymentMethod = 'yoco'">
                            <i class="fas fa-credit-card"></i>
                            Card
                        </button>
                        <button type="button" class="payment-method"
                                :class="{ 'selected': paymentMethod === 'ozow' }"
                                @click="paymentMethod = 'ozow'">
                            <i class="fas fa-university"></i>
                            EFT
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" x-model="agreedToTerms" required>
                        <span class="text-sm">I agree to the terms and conditions</span>
                    </label>
                </div>

                <button type="submit" class="btn btn-primary w-full" :disabled="loading">
                    <template x-if="!loading">
                        <span><i class="fas fa-heart"></i> Donate R<span x-text="getDonationAmount()"></span></span>
                    </template>
                    <template x-if="loading">
                        <span class="flex items-center justify-center gap-2">
                            <div class="loading-spinner"></div>
                            Processing...
                        </span>
                    </template>
                </button>
            </form>
        </div>
    </div>