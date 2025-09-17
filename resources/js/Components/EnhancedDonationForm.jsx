import React, { useState } from 'react';
import { useOffline } from './OfflineManager';
import { useMobile } from './MobileDetector';

const EnhancedDonationForm = ({ campaign }) => {
    const { isOnline, submitOfflineForm } = useOffline();
    const { isCapacitor } = useMobile();
    
    const [formData, setFormData] = useState({
        campaign_id: campaign.id,
        amount: '',
        selectedAmount: null,
        donor_name: '',
        donor_email: '',
        donor_phone: '',
        payment_method: 'yoco',
        allow_marketing: false,
    });
    
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});

    const predefinedAmounts = [50, 100, 250, 500, 1000, 2500];

    const handleAmountSelect = (amount) => {
        setFormData(prev => ({
            ...prev,
            selectedAmount: amount,
            amount: amount,
        }));
        setErrors(prev => ({ ...prev, amount: null }));
    };

    const handleCustomAmount = (value) => {
        setFormData(prev => ({
            ...prev,
            selectedAmount: null,
            amount: value,
        }));
        setErrors(prev => ({ ...prev, amount: null }));
    };

    const validateForm = () => {
        const newErrors = {};
        
        if (!formData.amount || formData.amount < 10) {
            newErrors.amount = 'Minimum donation is R10';
        }
        
        if (!formData.donor_name.trim()) {
            newErrors.donor_name = 'Name is required';
        }
        
        if (!formData.donor_email.trim() || !/\S+@\S+\.\S+/.test(formData.donor_email)) {
            newErrors.donor_email = 'Valid email is required';
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        if (!validateForm()) return;
        
        setLoading(true);

        try {
            if (!isOnline) {
                // Handle offline submission
                submitOfflineForm('donations', formData);
                
                // Show offline success message
                if (window.scheduleNotification) {
                    await window.scheduleNotification({
                        title: 'Donation Saved!',
                        body: `Your R${formData.amount} donation will be processed when you're back online.`,
                        delay: 1000,
                        data: { type: 'offline_donation' }
                    });
                }
                
                setFormData(prev => ({
                    ...prev,
                    amount: '',
                    selectedAmount: null,
                    donor_name: '',
                    donor_email: '',
                    donor_phone: '',
                    allow_marketing: false,
                }));
                
                return;
            }

            // Online submission
            const response = await fetch('/api/donate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                },
                body: JSON.stringify(formData),
            });

            const result = await response.json();

            if (response.ok) {
                // Redirect to payment gateway
                window.location.href = result.payment_url;
            } else {
                setErrors({ general: result.message || 'Something went wrong' });
            }
        } catch (error) {
            setErrors({ general: 'Network error. Please try again.' });
        } finally {
            setLoading(false);
        }
    };

    return (
        <div className="enhanced-donation-form">
            {!isOnline && (
                <div className="offline-banner">
                    <i className="fas fa-wifi-slash"></i>
                    You're offline. Donations will be saved and processed when you reconnect.
                </div>
            )}

            <form onSubmit={handleSubmit} className="space-y-4">
                <div className="form-section">
                    <h3 className="form-section-title">Choose Amount</h3>
                    
                    <div className="amount-grid">
                        {predefinedAmounts.map(amount => (
                            <button
                                key={amount}
                                type="button"
                                className={`amount-btn ${formData.selectedAmount === amount ? 'selected' : ''}`}
                                onClick={() => handleAmountSelect(amount)}
                            >
                                R{amount}
                            </button>
                        ))}
                    </div>

                    <div className="custom-amount-group">
                        <input
                            type="number"
                            placeholder="Enter custom amount"
                            value={formData.selectedAmount ? '' : formData.amount}
                            onChange={(e) => handleCustomAmount(e.target.value)}
                            className={`form-input ${errors.amount ? 'error' : ''}`}
                            min="10"
                        />
                        {errors.amount && <div className="error-message">{errors.amount}</div>}
                    </div>
                </div>

                <div className="form-section">
                    <h3 className="form-section-title">Your Details</h3>
                    
                    <div className="form-group">
                        <input
                            type="text"
                            placeholder="Full Name"
                            value={formData.donor_name}
                            onChange={(e) => setFormData(prev => ({ ...prev, donor_name: e.target.value }))}
                            className={`form-input ${errors.donor_name ? 'error' : ''}`}
                            required
                        />
                        {errors.donor_name && <div className="error-message">{errors.donor_name}</div>}
                    </div>

                    <div className="form-group">
                        <input
                            type="email"
                            placeholder="Email Address"
                            value={formData.donor_email}
                            onChange={(e) => setFormData(prev => ({ ...prev, donor_email: e.target.value }))}
                            className={`form-input ${errors.donor_email ? 'error' : ''}`}
                            required
                        />
                        {errors.donor_email && <div className="error-message">{errors.donor_email}</div>}
                    </div>

                    <div className="form-group">
                        <input
                            type="tel"
                            placeholder="Phone Number (Optional)"
                            value={formData.donor_phone}
                            onChange={(e) => setFormData(prev => ({ ...prev, donor_phone: e.target.value }))}
                            className="form-input"
                        />
                    </div>
                </div>

                <div className="form-section">
                    <h3 className="form-section-title">Payment Method</h3>
                    
                    <div className="payment-methods">
                        <label className={`payment-option ${formData.payment_method === 'yoco' ? 'selected' : ''}`}>
                            <input
                                type="radio"
                                name="payment_method"
                                value="yoco"
                                checked={formData.payment_method === 'yoco'}
                                onChange={(e) => setFormData(prev => ({ ...prev, payment_method: e.target.value }))}
                            />
                            <div className="payment-content">
                                <i className="fas fa-credit-card"></i>
                                <span>Credit Card</span>
                                <small>Visa, Mastercard</small>
                            </div>
                        </label>

                        <label className={`payment-option ${formData.payment_method === 'ozow' ? 'selected' : ''}`}>
                            <input
                                type="radio"
                                name="payment_method"
                                value="ozow"
                                checked={formData.payment_method === 'ozow'}
                                onChange={(e) => setFormData(prev => ({ ...prev, payment_method: e.target.value }))}
                            />
                            <div className="payment-content">
                                <i className="fas fa-university"></i>
                                <span>Bank Transfer</span>
                                <small>EFT via Ozow</small>
                            </div>
                        </label>
                    </div>
                </div>

                <div className="form-section">
                    <label className="checkbox-label">
                        <input
                            type="checkbox"
                            checked={formData.allow_marketing}
                            onChange={(e) => setFormData(prev => ({ ...prev, allow_marketing: e.target.checked }))}
                        />
                        <span>Send me updates about this campaign and similar causes</span>
                    </label>
                </div>

                {errors.general && (
                    <div className="error-banner">
                        <i className="fas fa-exclamation-triangle"></i>
                        {errors.general}
                    </div>
                )}

                <button
                    type="submit"
                    className={`btn btn-primary btn-large w-full ${loading ? 'loading' : ''}`}
                    disabled={loading || (!formData.amount || !formData.donor_name || !formData.donor_email)}
                >
                    {loading ? (
                        <>
                            <div className="loading-spinner"></div>
                            Processing...
                        </>
                    ) : (
                        <>
                            <i className="fas fa-heart"></i>
                            {isOnline ? `Donate R${formData.amount || 0}` : `Save Donation R${formData.amount || 0}`}
                        </>
                    )}
                </button>

                <div className="security-notice">
                    <i className="fas fa-shield-alt"></i>
                    <span>Your donation is secure and encrypted</span>
                </div>
            </form>
        </div>
    );
};

export default EnhancedDonationForm;