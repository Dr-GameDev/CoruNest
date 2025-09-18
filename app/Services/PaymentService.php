<?php

namespace App\Services;

use App\Models\Donation;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;

class PaymentService
{
    /**
     * Initialize payment with the specified provider.
     */
    public function initializePayment(Donation $donation, string $provider): array
    {
        return match($provider) {
            'yoco' => $this->initializeYocoPayment($donation),
            'ozow' => $this->initializeOzowPayment($donation),
            default => ['success' => false, 'message' => 'Unsupported payment provider'],
        };
    }

    /**
     * Initialize Yoco payment.
     */
    private function initializeYocoPayment(Donation $donation): array
    {
        try {
            $apiKey = config('services.yoco.secret_key');
            
            if (!$apiKey) {
                Log::error('Yoco API key not configured');
                return ['success' => false, 'message' => 'Payment configuration error'];
            }

            $payload = [
                'amount' => intval($donation->amount * 100), // Convert to cents
                'currency' => $donation->currency,
                'metadata' => [
                    'donation_id' => $donation->id,
                    'campaign_id' => $donation->campaign_id,
                    'campaign_title' => $donation->campaign->title,
                ],
                'success_url' => route('donation.success', $donation),
                'failure_url' => route('donation.failure', $donation),
                'cancel_url' => route('campaigns.show', $donation->campaign),
                'webhook_url' => route('donation.webhook', 'yoco'),
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://online.yoco.com/v1/charges', $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                // Update donation with Yoco charge ID
                $donation->update([
                    'metadata' => array_merge($donation->metadata ?? [], [
                        'yoco_charge_id' => $responseData['id'] ?? null,
                        'yoco_checkout_id' => $responseData['checkoutId'] ?? null,
                    ])
                ]);

                return [
                    'success' => true,
                    'payment_url' => $responseData['redirectUrl'] ?? null,
                    'redirect_url' => $responseData['redirectUrl'] ?? null,
                ];
            }

            Log::error('Yoco payment initialization failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'donation_id' => $donation->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initialize payment with Yoco'
            ];

        } catch (\Exception $e) {
            Log::error('Yoco payment initialization exception', [
                'error' => $e->getMessage(),
                'donation_id' => $donation->id,
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * Initialize Ozow payment.
     */
    private function initializeOzowPayment(Donation $donation): array
    {
        try {
            $siteCode = config('services.ozow.site_code');
            $privateKey = config('services.ozow.private_key');
            $apiUrl = config('services.ozow.api_url', 'https://api.ozow.com');

            if (!$siteCode || !$privateKey) {
                Log::error('Ozow credentials not configured');
                return ['success' => false, 'message' => 'Payment configuration error'];
            }

            $transactionReference = $donation->transaction_id;
            $amount = number_format($donation->amount, 2, '.', '');
            $currency = $donation->currency;
            
            // Generate Ozow hash
            $hashData = implode('', [
                $siteCode,
                $transactionReference,
                $amount,
                $currency,
                'true', // isTest (set to false in production)
            ]);
            $hashCheck = hash('sha512', strtolower($hashData . $privateKey));

            $payload = [
                'SiteCode' => $siteCode,
                'TransactionReference' => $transactionReference,
                'Amount' => $amount,
                'CurrencyCode' => $currency,
                'IsTest' => config('app.env') !== 'production',
                'HashCheck' => $hashCheck,
                'SuccessUrl' => route('donation.success', $donation),
                'CancelUrl' => route('campaigns.show', $donation->campaign),
                'ErrorUrl' => route('donation.failure', $donation),
                'NotifyUrl' => route('donation.webhook', 'ozow'),
                'Optional1' => $donation->id,
                'Optional2' => $donation->campaign_id,
                'Optional3' => $donation->campaign->title,
                'Optional4' => $donation->donor_display_name,
                'Optional5' => $donation->donor_email_for_receipt,
            ];

            $response = Http::post("{$apiUrl}/postpaymentrequest", $payload);

            if ($response->successful()) {
                $responseData = $response->json();
                
                if ($responseData['url'] ?? false) {
                    // Update donation with Ozow transaction reference
                    $donation->update([
                        'metadata' => array_merge($donation->metadata ?? [], [
                            'ozow_transaction_reference' => $transactionReference,
                            'ozow_hash_check' => $hashCheck,
                        ])
                    ]);

                    return [
                        'success' => true,
                        'payment_url' => $responseData['url'],
                        'redirect_url' => $responseData['url'],
                    ];
                }
            }

            Log::error('Ozow payment initialization failed', [
                'status' => $response->status(),
                'body' => $response->body(),
                'donation_id' => $donation->id,
            ]);

            return [
                'success' => false,
                'message' => 'Failed to initialize payment with Ozow'
            ];

        } catch (\Exception $e) {
            Log::error('Ozow payment initialization exception', [
                'error' => $e->getMessage(),
                'donation_id' => $donation->id,
            ]);

            return [
                'success' => false,
                'message' => 'Payment initialization failed'
            ];
        }
    }

    /**
     * Verify payment completion.
     */
    public function verifyPayment(Donation $donation, array $callbackData): bool
    {
        return match($donation->payment_provider) {
            'yoco' => $this->verifyYocoPayment($donation, $callbackData),
            'ozow' => $this->verifyOzowPayment($donation, $callbackData),
            default => false,
        };
    }

    /**
     * Verify Yoco payment.
     */
    private function verifyYocoPayment(Donation $donation, array $callbackData): bool
    {
        try {
            $apiKey = config('services.yoco.secret_key');
            $chargeId = $donation->metadata['yoco_charge_id'] ?? null;

            if (!$chargeId) {
                Log::error('No Yoco charge ID found for verification', [
                    'donation_id' => $donation->id,
                ]);
                return false;
            }

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->get("https://online.yoco.com/v1/charges/{$chargeId}");

            if ($response->successful()) {
                $chargeData = $response->json();
                
                if ($chargeData['status'] === 'successful') {
                    // Update donation metadata with final charge details
                    $donation->update([
                        'metadata' => array_merge($donation->metadata ?? [], [
                            'yoco_charge_data' => $chargeData,
                            'verified_at' => now(),
                        ])
                    ]);

                    return true;
                }
            }

            Log::error('Yoco payment verification failed', [
                'charge_id' => $chargeId,
                'response' => $response->json(),
                'donation_id' => $donation->id,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Yoco verification exception', [
                'error' => $e->getMessage(),
                'donation_id' => $donation->id,
            ]);

            return false;
        }
    }

    /**
     * Verify Ozow payment.
     */
    private function verifyOzowPayment(Donation $donation, array $callbackData): bool
    {
        try {
            $privateKey = config('services.ozow.private_key');
            $siteCode = config('services.ozow.site_code');

            // Verify hash from Ozow callback
            $expectedHash = hash('sha512', strtolower(implode('', [
                $siteCode,
                $callbackData['TransactionReference'] ?? '',
                $callbackData['Amount'] ?? '',
                $callbackData['Status'] ?? '',
                $callbackData['StatusMessage'] ?? '',
                $callbackData['DateTime'] ?? '',
                $callbackData['Optional1'] ?? '',
                $callbackData['Optional2'] ?? '',
                $callbackData['Optional3'] ?? '',
                $callbackData['Optional4'] ?? '',
                $callbackData['Optional5'] ?? '',
                $callbackData['CurrencyCode'] ?? '',
                $callbackData['IsTest'] ?? '',
                $callbackData['BankReference'] ?? '',
                $privateKey,
            ])));

            $receivedHash = $callbackData['HashCheck'] ?? '';

            if ($expectedHash !== $receivedHash) {
                Log::error('Ozow hash verification failed', [
                    'expected' => $expectedHash,
                    'received' => $receivedHash,
                    'donation_id' => $donation->id,
                ]);
                return false;
            }

            $status = $callbackData['Status'] ?? '';
            if ($status === 'Complete' || $status === 'Successful') {
                // Update donation metadata
                $donation->update([
                    'metadata' => array_merge($donation->metadata ?? [], [
                        'ozow_callback_data' => $callbackData,
                        'ozow_bank_reference' => $callbackData['BankReference'] ?? null,
                        'verified_at' => now(),
                    ])
                ]);

                return true;
            }

            Log::info('Ozow payment not successful', [
                'status' => $status,
                'message' => $callbackData['StatusMessage'] ?? '',
                'donation_id' => $donation->id,
            ]);

            return false;

        } catch (\Exception $e) {
            Log::error('Ozow verification exception', [
                'error' => $e->getMessage(),
                'donation_id' => $donation->id,
            ]);

            return false;
        }
    }

    /**
     * Handle payment provider webhooks.
     */
    public function handleWebhook(string $provider, array $payload): array
    {
        return match($provider) {
            'yoco' => $this->handleYocoWebhook($payload),
            'ozow' => $this->handleOzowWebhook($payload),
            default => ['success' => false, 'message' => 'Unknown provider'],
        };
    }

    /**
     * Handle Yoco webhook.
     */
    private function handleYocoWebhook(array $payload): array
    {
        try {
            $event = $payload['type'] ?? '';
            $chargeData = $payload['payload'] ?? [];
            
            if ($event === 'payment.succeeded') {
                $donationId = $chargeData['metadata']['donation_id'] ?? null;
                
                if ($donationId) {
                    $donation = Donation::find($donationId);
                    
                    if ($donation && $donation->status === 'pending') {
                        $donation->markAsCompleted();
                        
                        Log::info('Yoco webhook processed successfully', [
                            'donation_id' => $donationId,
                            'charge_id' => $chargeData['id'] ?? null,
                        ]);
                        
                        return ['success' => true];
                    }
                }
            }
            
            if ($event === 'payment.failed') {
                $donationId = $chargeData['metadata']['donation_id'] ?? null;
                
                if ($donationId) {
                    $donation = Donation::find($donationId);
                    
                    if ($donation && $donation->status === 'pending') {
                        $reason = $chargeData['failure_reason'] ?? 'Payment failed';
                        $donation->markAsFailed($reason);
                        
                        Log::info('Yoco payment failed via webhook', [
                            'donation_id' => $donationId,
                            'reason' => $reason,
                        ]);
                        
                        return ['success' => true];
                    }
                }
            }

            return ['success' => false, 'message' => 'Unhandled event type'];

        } catch (\Exception $e) {
            Log::error('Yoco webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return ['success' => false, 'message' => 'Webhook processing failed'];
        }
    }

    /**
     * Handle Ozow webhook.
     */
    private function handleOzowWebhook(array $payload): array
    {
        try {
            $donationId = $payload['Optional1'] ?? null;
            $status = $payload['Status'] ?? '';
            
            if ($donationId) {
                $donation = Donation::find($donationId);
                
                if ($donation && $donation->status === 'pending') {
                    // Verify the webhook hash
                    if ($this->verifyOzowPayment($donation, $payload)) {
                        if (in_array($status, ['Complete', 'Successful'])) {
                            $donation->markAsCompleted();
                            
                            Log::info('Ozow webhook processed - payment completed', [
                                'donation_id' => $donationId,
                                'status' => $status,
                            ]);
                        } else {
                            $reason = $payload['StatusMessage'] ?? 'Payment failed';
                            $donation->markAsFailed($reason);
                            
                            Log::info('Ozow webhook processed - payment failed', [
                                'donation_id' => $donationId,
                                'status' => $status,
                                'reason' => $reason,
                            ]);
                        }
                        
                        return ['success' => true];
                    } else {
                        Log::error('Ozow webhook hash verification failed', [
                            'donation_id' => $donationId,
                        ]);
                        
                        return ['success' => false, 'message' => 'Hash verification failed'];
                    }
                }
            }

            return ['success' => false, 'message' => 'No matching donation found'];

        } catch (\Exception $e) {
            Log::error('Ozow webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return ['success' => false, 'message' => 'Webhook processing failed'];
        }
    }

    /**
     * Refund a donation.
     */
    public function refundDonation(Donation $donation, float $amount = null): array
    {
        if ($donation->status !== 'completed') {
            return ['success' => false, 'message' => 'Can only refund completed donations'];
        }

        $refundAmount = $amount ?? $donation->amount;

        return match($donation->payment_provider) {
            'yoco' => $this->refundYocoPayment($donation, $refundAmount),
            'ozow' => $this->refundOzowPayment($donation, $refundAmount),
            default => ['success' => false, 'message' => 'Refunds not supported for this provider'],
        };
    }

    /**
     * Refund Yoco payment.
     */
    private function refundYocoPayment(Donation $donation, float $amount): array
    {
        try {
            $apiKey = config('services.yoco.secret_key');
            $chargeId = $donation->metadata['yoco_charge_id'] ?? null;

            if (!$chargeId) {
                return ['success' => false, 'message' => 'No charge ID found'];
            }

            $payload = [
                'amount' => intval($amount * 100), // Convert to cents
            ];

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post("https://online.yoco.com/v1/charges/{$chargeId}/refunds", $payload);

            if ($response->successful()) {
                $refundData = $response->json();
                
                // Update donation status and metadata
                $donation->update([
                    'status' => 'refunded',
                    'metadata' => array_merge($donation->metadata ?? [], [
                        'refund_data' => $refundData,
                        'refunded_at' => now(),
                        'refund_amount' => $amount,
                    ])
                ]);

                // Update campaign totals
                $donation->campaign->updateTotals();

                return ['success' => true, 'refund_id' => $refundData['id'] ?? null];
            }

            return [
                'success' => false, 
                'message' => 'Refund failed: ' . ($response->json()['displayMessage'] ?? 'Unknown error')
            ];

        } catch (\Exception $e) {
            Log::error('Yoco refund failed', [
                'error' => $e->getMessage(),
                'donation_id' => $donation->id,
            ]);

            return ['success' => false, 'message' => 'Refund processing failed'];
        }
    }

    /**
     * Refund Ozow payment.
     */
    private function refundOzowPayment(Donation $donation, float $amount): array
    {
        // Ozow typically handles refunds manually through their admin panel
        // This would need to be coordinated with Ozow support
        return [
            'success' => false,
            'message' => 'Ozow refunds must be processed manually. Please contact Ozow support.'
        ];
    }
}