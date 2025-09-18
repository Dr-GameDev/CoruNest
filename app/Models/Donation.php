<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Donation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'campaign_id',
        'amount',
        'currency',
        'payment_provider',
        'transaction_id',
        'status',
        'metadata',
        'donor_name',
        'donor_email',
        'donor_phone',
        'donor_message',
        'anonymous',
        'recurring',
        'receipt_number',
        'completed_at',
        'failed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'amount' => 'decimal:2',
        'metadata' => 'array',
        'anonymous' => 'boolean',
        'recurring' => 'boolean',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($donation) {
            if (empty($donation->receipt_number) && $donation->status === 'completed') {
                $donation->receipt_number = static::generateReceiptNumber();
            }
        });

        static::updated(function ($donation) {
            if ($donation->wasChanged('status')) {
                if ($donation->status === 'completed') {
                    $donation->completed_at = now();
                    if (empty($donation->receipt_number)) {
                        $donation->receipt_number = static::generateReceiptNumber();
                        $donation->saveQuietly();
                    }

                    // Update campaign totals
                    $donation->campaign->updateTotals();

                    // Update user's last donation timestamp
                    if ($donation->user) {
                        $donation->user->updateLastDonation();
                    }
                } elseif ($donation->status === 'failed') {
                    $donation->failed_at = now();
                }
            }
        });
    }

    /**
     * Get the user who made this donation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the campaign this donation belongs to.
     */
    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    /**
     * Get the donor's display name.
     */
    public function getDonorDisplayNameAttribute(): string
    {
        if ($this->anonymous) {
            return 'Anonymous Donor';
        }

        if ($this->user) {
            return $this->user->name;
        }

        return $this->donor_name ?: 'Anonymous Donor';
    }

    /**
     * Get the donor's email for receipts.
     */
    public function getDonorEmailForReceiptAttribute(): ?string
    {
        if ($this->user) {
            return $this->user->email;
        }

        return $this->donor_email;
    }

    /**
     * Check if donation is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if donation is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if donation failed.
     */
    public function getIsFailedAttribute(): bool
    {
        return in_array($this->status, ['failed', 'cancelled']);
    }

    /**
     * Get formatted amount with currency.
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'R' . number_format($this->amount, 2);
    }

    /**
     * Generate a unique receipt number.
     */
    public static function generateReceiptNumber(): string
    {
        do {
            $receiptNumber = 'REC-' . date('Y') . '-' . Str::upper(Str::random(8));
        } while (static::where('receipt_number', $receiptNumber)->exists());

        return $receiptNumber;
    }

    /**
     * Generate a unique transaction ID.
     */
    public static function generateTransactionId(): string
    {
        do {
            $transactionId = 'TXN-' . time() . '-' . Str::upper(Str::random(6));
        } while (static::where('transaction_id', $transactionId)->exists());

        return $transactionId;
    }

    /**
     * Mark donation as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark donation as failed.
     */
    public function markAsFailed(string $reason = null): void
    {
        $metadata = $this->metadata ?? [];
        if ($reason) {
            $metadata['failure_reason'] = $reason;
        }

        $this->update([
            'status' => 'failed',
            'failed_at' => now(),
            'metadata' => $metadata,
        ]);
    }

    /**
     * Scope to get completed donations.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get pending donations.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get failed donations.
     */
    public function scopeFailed($query)
    {
        return $query->whereIn('status', ['failed', 'cancelled']);
    }

    /**
     * Scope to get donations for a specific campaign.
     */
    public function scopeForCampaign($query, $campaignId)
    {
        return $query->where('campaign_id', $campaignId);
    }

    /**
     * Scope to get donations by payment provider.
     */
    public function scopeByProvider($query, string $provider)
    {
        return $query->where('payment_provider', $provider);
    }

    /**
     * Scope to get donations within date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent donations.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get available payment providers.
     */
    public static function getPaymentProviders(): array
    {
        return [
            'yoco' => 'Yoco',
            'ozow' => 'Ozow',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'completed' => 'Completed',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
            'cancelled' => 'Cancelled',
        ];
    }
}
