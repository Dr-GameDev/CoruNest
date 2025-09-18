<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Event extends Model
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
        'description',
        'location',
        'address',
        'latitude',
        'longitude',
        'capacity',
        'volunteer_count',
        'starts_at',
        'ends_at',
        'signup_deadline',
        'signup_limit',
        'status',
        'image_path',
        'images',
        'requirements',
        'instructions',
        'contact_name',
        'contact_phone',
        'contact_email',
        'category',
        'metadata',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'signup_deadline' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'images' => 'array',
        'requirements' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($event) {
            if (empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });

        static::updating(function ($event) {
            if ($event->isDirty('title') && empty($event->slug)) {
                $event->slug = Str::slug($event->title);
            }
        });
    }

    /**
     * Get volunteers for this event.
     */
    public function volunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class);
    }

    /**
     * Get confirmed volunteers for this event.
     */
    public function confirmedVolunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class)->where('status', 'confirmed');
    }

    /**
     * Get pending volunteers for this event.
     */
    public function pendingVolunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class)->where('status', 'pending');
    }

    /**
     * Get the user who created this event.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Check if event is upcoming.
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->starts_at && $this->starts_at->isFuture() && $this->status === 'active';
    }

    /**
     * Check if event has started.
     */
    public function getHasStartedAttribute(): bool
    {
        return $this->starts_at && $this->starts_at->isPast();
    }

    /**
     * Check if event has ended.
     */
    public function getHasEndedAttribute(): bool
    {
        return $this->ends_at && $this->ends_at->isPast();
    }

    /**
     * Check if signup is still open.
     */
    public function getSignupOpenAttribute(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->signup_deadline && now()->isAfter($this->signup_deadline)) {
            return false;
        }

        if ($this->signup_limit && $this->volunteer_count >= $this->signup_limit) {
            return false;
        }

        return !$this->has_started;
    }

    /**
     * Get remaining volunteer slots.
     */
    public function getRemainingSlotAttribute(): ?int
    {
        if (!$this->signup_limit) {
            return null;
        }

        return max(0, $this->signup_limit - $this->volunteer_count);
    }

    /**
     * Get event duration in hours.
     */
    public function getDurationInHoursAttribute(): ?float
    {
        if (!$this->starts_at || !$this->ends_at) {
            return null;
        }

        return $this->starts_at->diffInHours($this->ends_at);
    }

    /**
     * Get the main event image.
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
     * Get days until event starts.
     */
    public function getDaysUntilStartAttribute(): ?int
    {
        if (!$this->starts_at) {
            return null;
        }

        $diff = now()->diffInDays($this->starts_at, false);
        return $diff > 0 ? $diff : 0;
    }

    /**
     * Get formatted date range.
     */
    public function getFormattedDateRangeAttribute(): string
    {
        if (!$this->starts_at) {
            return 'Date TBD';
        }

        $start = $this->starts_at->format('M j, Y g:i A');

        if (!$this->ends_at) {
            return $start;
        }

        // Same day
        if ($this->starts_at->isSameDay($this->ends_at)) {
            return $this->starts_at->format('M j, Y g:i A') . ' - ' . $this->ends_at->format('g:i A');
        }

        // Different days
        return $start . ' - ' . $this->ends_at->format('M j, Y g:i A');
    }

    /**
     * Update volunteer count.
     */
    public function updateVolunteerCount(): void
    {
        $count = $this->volunteers()->whereIn('status', ['confirmed', 'completed'])->count();
        $this->update(['volunteer_count' => $count]);
    }

    /**
     * Check if user has already volunteered for this event.
     */
    public function hasUserVolunteered(?int $userId): bool
    {
        if (!$userId) {
            return false;
        }

        return $this->volunteers()
            ->where('user_id', $userId)
            ->whereIn('status', ['pending', 'confirmed', 'completed'])
            ->exists();
    }

    /**
     * Scope to get upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('status', 'active')
            ->where('starts_at', '>', now())
            ->orderBy('starts_at');
    }

    /**
     * Scope to get active events.
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope to get events within date range.
     */
    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('starts_at', [$startDate, $endDate])
                ->orWhereBetween('ends_at', [$startDate, $endDate])
                ->orWhere(function ($q2) use ($startDate, $endDate) {
                    $q2->where('starts_at', '<=', $startDate)
                        ->where('ends_at', '>=', $endDate);
                });
        });
    }

    /**
     * Scope to search events.
     */
    public function scopeSearch($query, ?string $search)
    {
        if (!$search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%")
                ->orWhere('location', 'like', "%{$search}%")
                ->orWhere('category', 'like', "%{$search}%");
        });
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
     * Scope to filter by location.
     */
    public function scopeByLocation($query, ?string $location)
    {
        if (!$location) {
            return $query;
        }

        return $query->where('location', 'like', "%{$location}%");
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
            'cleanup' => 'Cleanup & Environment',
            'food' => 'Food Distribution',
            'education' => 'Education & Tutoring',
            'healthcare' => 'Healthcare Support',
            'construction' => 'Construction & Building',
            'fundraising' => 'Fundraising Events',
            'community' => 'Community Outreach',
            'elderly' => 'Elderly Care',
            'children' => 'Children & Youth',
            'animals' => 'Animal Care',
            'disaster' => 'Disaster Relief',
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
            'cancelled' => 'Cancelled',
        ];
    }
}
