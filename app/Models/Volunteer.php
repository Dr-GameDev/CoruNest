<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Volunteer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'event_id',
        'status',
        'notes',
        'volunteer_name',
        'volunteer_email',
        'volunteer_phone',
        'skills',
        'availability',
        'message',
        'has_transport',
        'emergency_contact_provided',
        'emergency_contact_name',
        'emergency_contact_phone',
        'confirmed_at',
        'cancelled_at',
        'completed_at',
        'confirmed_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'skills' => 'array',
        'availability' => 'array',
        'has_transport' => 'boolean',
        'emergency_contact_provided' => 'boolean',
        'confirmed_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::updated(function ($volunteer) {
            if ($volunteer->wasChanged('status')) {
                switch ($volunteer->status) {
                    case 'confirmed':
                        $volunteer->confirmed_at = now();
                        $volunteer->saveQuietly();
                        break;
                    case 'cancelled':
                        $volunteer->cancelled_at = now();
                        $volunteer->saveQuietly();
                        break;
                    case 'completed':
                        $volunteer->completed_at = now();
                        $volunteer->saveQuietly();
                        // Update user's last volunteer timestamp
                        if ($volunteer->user) {
                            $volunteer->user->updateLastVolunteer();
                        }
                        break;
                }

                // Update event volunteer count
                $volunteer->event->updateVolunteerCount();
            }
        });

        static::created(function ($volunteer) {
            $volunteer->event->updateVolunteerCount();
        });

        static::deleted(function ($volunteer) {
            $volunteer->event->updateVolunteerCount();
        });
    }

    /**
     * Get the user who volunteered.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the event this volunteer signup is for.
     */
    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Get the user who confirmed this volunteer.
     */
    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    /**
     * Get the volunteer's display name.
     */
    public function getVolunteerDisplayNameAttribute(): string
    {
        if ($this->user) {
            return $this->user->name;
        }

        return $this->volunteer_name ?: 'Anonymous Volunteer';
    }

    /**
     * Get the volunteer's email.
     */
    public function getVolunteerEmailAttribute(): ?string
    {
        if ($this->user) {
            return $this->user->email;
        }

        return $this->volunteer_email;
    }

    /**
     * Get the volunteer's phone.
     */
    public function getVolunteerPhoneAttribute(): ?string
    {
        if ($this->user) {
            return $this->user->phone;
        }

        return $this->volunteer_phone;
    }

    /**
     * Check if volunteer is confirmed.
     */
    public function getIsConfirmedAttribute(): bool
    {
        return $this->status === 'confirmed';
    }

    /**
     * Check if volunteer is pending.
     */
    public function getIsPendingAttribute(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if volunteer is cancelled.
     */
    public function getIsCancelledAttribute(): bool
    {
        return $this->status === 'cancelled';
    }

    /**
     * Check if volunteer is completed.
     */
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Get formatted skills list.
     */
    public function getFormattedSkillsAttribute(): ?string
    {
        if (!$this->skills || !is_array($this->skills)) {
            return null;
        }

        return implode(', ', $this->skills);
    }

    /**
     * Confirm the volunteer signup.
     */
    public function confirm(int $confirmedBy): void
    {
        $this->update([
            'status' => 'confirmed',
            'confirmed_by' => $confirmedBy,
            'confirmed_at' => now(),
        ]);
    }

    /**
     * Cancel the volunteer signup.
     */
    public function cancel(): void
    {
        $this->update([
            'status' => 'cancelled',
            'cancelled_at' => now(),
        ]);
    }

    /**
     * Mark volunteer as completed.
     */
    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark volunteer as no-show.
     */
    public function markAsNoShow(): void
    {
        $this->update([
            'status' => 'no_show',
        ]);
    }

    /**
     * Check if volunteer can be confirmed.
     */
    public function canBeConfirmed(): bool
    {
        return $this->status === 'pending' && $this->event->signup_open;
    }

    /**
     * Check if volunteer can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'confirmed']) && !$this->event->has_ended;
    }

    /**
     * Scope to get confirmed volunteers.
     */
    public function scopeConfirmed($query)
    {
        return $query->where('status', 'confirmed');
    }

    /**
     * Scope to get pending volunteers.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope to get cancelled volunteers.
     */
    public function scopeCancelled($query)
    {
        return $query->where('status', 'cancelled');
    }

    /**
     * Scope to get completed volunteers.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope to get volunteers for a specific event.
     */
    public function scopeForEvent($query, $eventId)
    {
        return $query->where('event_id', $eventId);
    }

    /**
     * Scope to get volunteers with transport.
     */
    public function scopeWithTransport($query)
    {
        return $query->where('has_transport', true);
    }

    /**
     * Scope to get volunteers with emergency contact.
     */
    public function scopeWithEmergencyContact($query)
    {
        return $query->where('emergency_contact_provided', true);
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'cancelled' => 'Cancelled',
            'completed' => 'Completed',
            'no_show' => 'No Show',
        ];
    }

    /**
     * Get available skills.
     */
    public static function getAvailableSkills(): array
    {
        return [
            'first_aid' => 'First Aid',
            'cooking' => 'Cooking',
            'construction' => 'Construction',
            'teaching' => 'Teaching',
            'driving' => 'Driving',
            'photography' => 'Photography',
            'social_media' => 'Social Media',
            'fundraising' => 'Fundraising',
            'event_planning' => 'Event Planning',
            'translation' => 'Translation',
            'computer_skills' => 'Computer Skills',
            'childcare' => 'Childcare',
            'elderly_care' => 'Elderly Care',
            'manual_labor' => 'Manual Labor',
            'administrative' => 'Administrative',
            'customer_service' => 'Customer Service',
        ];
    }
}
