<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DonationController extends Controller
{
    public function __construct(
        private PaymentService $paymentService
    ) {}

    /**
     * Process a new donation.
     */
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'amount' => 'required|numeric|min:10|max:50000',
            'payment_provider' => 'required|in:yoco,ozow',
            'donor_name' => 'nullable|string|max:255',
            'donor_email' => 'nullable|email|max:255',
            'donor_phone' => 'nullable|string|max:20',
            'donor_message' => 'nullable|string|max:500',
            'anonymous' => 'boolean',
            'recurring' => 'boolean',
        ]);

        // Get the campaign and validate it's active
        $campaign = Campaign::findOrFail($validated['campaign_id']);
        
        if ($campaign->status !== 'active' || !$campaign->is_active) {
            throw ValidationException::withMessages([
                'campaign' => 'This campaign is no longer accepting donations.'
            ]);
        }

        try {
            DB::beginTransaction();

            // Create donation record
            $donation = Donation::create([
                'user_id' => auth()->id(),
                'campaign_id' => $campaign->id,
                'amount' => $validated['amount'],
                'currency' => 'ZAR',
                'payment_provider' => $validated['payment_provider'],
                'transaction_id' => Donation::generateTransactionId(),
                'status' => 'pending',
                'donor_name' => $validated['donor_name'] ?? null,
                'donor_email' => $validated['donor_email'] ?? auth()->user()?->email,
                'donor_phone' => $validated['donor_phone'] ?? null,
                'donor_message' => $validated['donor_message'] ?? null,
                'anonymous' => $validated['anonymous'] ?? false,
                'recurring' => $validated['recurring'] ?? false,
            ]);

            // Initialize payment with chosen provider
            $paymentResult = $this->paymentService->initializePayment(
                $donation,
                $validated['payment_provider']
            );

            if (!$paymentResult['success']) {
                DB::rollBack();
                
                return response()->json([
                    'success' => false,
                    'message' => $paymentResult['message'] ?? 'Payment initialization failed.'
                ], 400);
            }

            DB::commit();

            // Return payment redirect URL or token
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'donation_id' => $donation->id,
                    'payment_url' => $paymentResult['payment_url'] ?? null,
                    'payment_token' => $paymentResult['payment_token'] ?? null,
                    'redirect_url' => $paymentResult['redirect_url'] ?? null,
                ]);
            }

            // Redirect to payment provider or show payment form
            return redirect($paymentResult['redirect_url']);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Donation creation failed', [
                'error' => $e->getMessage(),
                'campaign_id' => $campaign->id,
                'amount' => $validated['amount'],
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your donation. Please try again.'
                ], 500);
            }

            return back()->withErrors(['donation' => 'An error occurred. Please try again.']);
        }
    }

    /**
     * Handle successful payment callback.
     */
    public function success(Request $request, Donation $donation): RedirectResponse
    {
        if ($donation->status === 'completed') {
            return redirect()->route('donation.receipt', $donation)
                ->with('success', 'Your donation has been successfully processed!');
        }

        // Verify payment with provider
        $verified = $this->paymentService->verifyPayment(
            $donation,
            $request->all()
        );

        if ($verified) {
            $donation->markAsCompleted();
            
            return redirect()->route('donation.receipt', $donation)
                ->with('success', 'Thank you for your generous donation!');
        }

        return redirect()->route('campaigns.show', $donation->campaign)
            ->with('error', 'There was an issue verifying your payment. Please contact us if your card was charged.');
    }

    /**
     * Handle payment failure callback.
     */
    public function failure(Request $request, Donation $donation): RedirectResponse
    {
        $reason = $request->get('reason', 'Payment was cancelled or failed');
        $donation->markAsFailed($reason);

        return redirect()->route('campaigns.show', $donation->campaign)
            ->with('error', 'Your donation could not be processed. Please try again.');
    }

    /**
     * Handle payment provider webhooks.
     */
    public function webhook(Request $request, string $provider): JsonResponse
    {
        try {
            $result = $this->paymentService->handleWebhook($provider, $request->all());
            
            if ($result['success']) {
                return response()->json(['status' => 'success']);
            }

            return response()->json(['status' => 'error'], 400);

        } catch (\Exception $e) {
            Log::error("Webhook processing failed for {$provider}", [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
            ]);

            return response()->json(['status' => 'error'], 500);
        }
    }

    /**
     * Show donation receipt.
     */
    public function receipt(Donation $donation)
    {
        // Only allow viewing receipt if donation is completed and user owns it or is admin
        if ($donation->status !== 'completed') {
            abort(404, 'Receipt not available for this donation.');
        }

        if (!auth()->user()?->isAdmin() && $donation->user_id !== auth()->id()) {
            abort(403, 'You are not authorized to view this receipt.');
        }

        $donation->load(['campaign', 'user']);

        return view('donations.receipt', compact('donation'));
    }

    /**
     * Download donation receipt as PDF.
     */
    public function downloadReceipt(Donation $donation)
    {
        // Same authorization as receipt view
        if ($donation->status !== 'completed') {
            abort(404, 'Receipt not available for this donation.');
        }

        if (!auth()->user()?->isAdmin() && $donation->user_id !== auth()->id()) {
            abort(403, 'You are not authorized to download this receipt.');
        }

        $donation->load(['campaign', 'user']);

        // Generate PDF using Laravel PDF or DomPDF
        $pdf = app('dompdf.wrapper');
        $pdf->loadView('donations.receipt-pdf', compact('donation'));

        $filename = "receipt-{$donation->receipt_number}.pdf";
        
        return $pdf->download($filename);
    }

    /**
     * Get donation status for AJAX polling.
     */
    public function status(Donation $donation): JsonResponse
    {
        return response()->json([
            'id' => $donation->id,
            'status' => $donation->status,
            'is_completed' => $donation->is_completed,
            'is_failed' => $donation->is_failed,
            'receipt_url' => $donation->is_completed ? route('donation.receipt', $donation) : null,
            'campaign_url' => route('campaigns.show', $donation->campaign),
        ]);
    }

    /**
     * Show user's donation history.
     */
    public function history(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $donations = auth()->user()
            ->donations()
            ->with(['campaign'])
            ->latest()
            ->paginate(10);

        $totalDonated = auth()->user()->donations()
            ->completed()
            ->sum('amount');

        $donationCount = auth()->user()->donations()
            ->completed()
            ->count();

        return view('donations.history', compact(
            'donations',
            'totalDonated',
            'donationCount'
        ));
    }

    /**
     * Cancel a pending donation.
     */
    public function cancel(Donation $donation): RedirectResponse
    {
        // Only allow cancelling own pending donations
        if ($donation->user_id !== auth()->id() || $donation->status !== 'pending') {
            abort(403, 'Cannot cancel this donation.');
        }

        $donation->update(['status' => 'cancelled']);

        return redirect()->route('campaigns.show', $donation->campaign)
            ->with('success', 'Your donation has been cancelled.');
    }

    /**
     * Get recent donations for a campaign (AJAX).
     */
    public function recentForCampaign(Campaign $campaign): JsonResponse
    {
        $donations = $campaign->donations()
            ->completed()
            ->where('anonymous', false)
            ->with('user')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($donation) {
                return [
                    'donor_name' => $donation->donor_display_name,
                    'amount' => $donation->formatted_amount,
                    'message' => $donation->donor_message,
                    'created_at' => $donation->created_at->diffForHumans(),
                ];
            });

        return response()->json(['donations' => $donations]);
    }
}