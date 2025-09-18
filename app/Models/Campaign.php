<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Campaign extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'body',
        'target_amount',
        'current_amount',
        'goal_type',
        'status',
        'start_at',
        'end_at',
        'featured',
        'image_path',
        'images',
        'metadata',
        'category',
        'donor_count',
        'average_donation',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'target_amount' => 'decimal:2',
        'current_amount' => 'decimal:2',
        'average_donation' => 'decimal:2',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'featured' => 'boolean',
        'images' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($campaign) {
            if (empty($campaign->slug)) {
                $campaign->slug = Str::slug($campaign->title);
            }
        });

        static::updating(function ($campaign) {
            if ($campaign->isDirty('title') && empty($campaign->slug)) {
                $campaign->slug = Str::slug($campaign->title);
            }
        });
    }

    /**
     * Get donations for this campaign.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get completed donations for this campaign.
     */
    public function completedDonations(): HasMany
    {
        return $this->hasMany(Donation::class)->where('status', 'completed');
    }

    /**
     * Get the user who created this campaign.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Calculate progress percentage.
     */
    public function getProgressPercentageAttribute(): float
    {
        if ($this->target_amount <= 0) {
            return 0;
        }

        $percentage = ($this->current_amount / $this->target_amount) * 100;
        return min($percentage, 100); // Cap at 100%
    }

    /**
     * Get remaining amount to reach target.
     */
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->target_amount - $this->current_amount);
    }

    /**
     * Check if campaign is active and within date range.
     */
    public function getIsActiveAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        $now = now();
        
        if ($this->start_at && $now->isBefore($this->start_at)) {
            return false;
        }

        if ($this->end_at && $now->isAfter($this->end_at)) {
            return false;
        }

        return true;
    }

    /**
     * Check if campaign has ended.
     */
    public function getHasEndedAttribute(): bool
    {
        return $this->end_at && now()->isAfter($this->end_at);
    }

    /**
     * Check if campaign is fully funded.
     */
    public function getIsFullyFundedAttribute(): bool
    {
        return $this->current_amount >= $this->target_amount;
    }

    /**
     * Get the main campaign image.
     */
    public function getMainImageAttribute(): ?string
    {
        if ($this->image_path) {
            return $this->image_path;
        }

        if ($this->images && is_array($this->images) && count($this->images) > 0) {
            return $this->images[0];
        }

        return null;
    }

    /**
     * Get days remaining until campaign ends.
     */
    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->end_at) {
            return null;
        }

        $diff = now()->diffInDays($this->end_at, false);
        return $diff > 0 ? $diff : 0;
    }

    /**
     * Update campaign totals from donations.
     */
    public function updateTotals(): void
    {
        $stats = $this->completedDonations()
            ->selectRaw('SUM(amount) as total_amount, COUNT(*) as donor_count, AVG(amount) as average_donation')
            ->first();

        $this->update([
            'current_amount' => $stats->total_amount ?? 0,
            'donor_count' => $stats->donor_count ?? 0,
            'average_donation' => $stats->average_donation ?? 0,
        ]);
    }

    /**
     * Scope to filter by category.
     */
    public function scopeByCategory($query, ?string $category)
    {
        if (!$category) {
            return $query;
        }

        return $query->where('category', $category);
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, ?string $status)
    {
        if (!$status) {
            return $query;
        }

        return $query->where('status', $status);
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Scope to order by progress (lowest first).
     */
    public function scopeOrderByProgress($query)
    {
        return $query->orderByRaw('(current_amount / target_amount) ASC');
    }

    /**
     * Scope to order by ending soon.
     */
    public function scopeEndingSoon($query)
    {
        return $query->where('end_at', '!=', null)
            ->orderBy('end_at', 'asc');
    }

    /**
     * Scope to get active campaigns.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('start_at')
                  ->orWhere('start_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_at')
                  ->orWhere('end_at', '>=', now());
            });
    }

    /**
     * Scope to get featured campaigns.
     */
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    /**
     * Scope to search campaigns.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('summary', 'like', "%{$search}%")
              ->orWhere('category', 'like', "%{$search}%");
        });
    }

    /**
     * Get the route key for the model.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Get available categories.
     */
    public static function getCategories(): array
    {
        return [
            'education' => 'Education',
            'healthcare' => 'Healthcare',
            'environment' => 'Environment',
            'poverty' => 'Poverty Relief',
            'community' => 'Community Development',
            'disaster' => 'Disaster Relief',
            'animals' => 'Animal Welfare',
            'arts' => 'Arts & Culture',
            'sports' => 'Sports',
            'other' => 'Other',
        ];
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            'draft' => 'Draft',
            'active' => 'Active',
            'completed' => 'Completed',
            'archived' => 'Archived',
        ];
    }

    /**
     * Auto-complete campaign if target is reached.
     */
    public function checkAutoComplete(): void
    {
        if ($this->status === 'active' && $this->is_fully_funded) {
            $this->update(['status' => 'completed']);
        }
    }

    /**
     * Get formatted target amount.
     */
    public function getFormattedTargetAmountAttribute(): string
    {
        return 'R' . number_format($this->target_amount, 2);
    }

    /**
     * Get formatted current amount.
     */
    public function getFormattedCurrentAmountAttribute(): string
    {
        return 'R' . number_format($this->current_amount, 2);
    }

    /**
     * Get formatted remaining amount.
     */
    public function getFormattedRemainingAmountAttribute(): string
    {
        return 'R' . number_format($this->remaining_amount, 2);
    }
}
