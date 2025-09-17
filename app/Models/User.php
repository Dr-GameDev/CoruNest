<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'role',
        'profile',
        'email_notifications',
        'sms_notifications',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'profile' => 'array',
            'last_donation_at' => 'datetime',
            'last_volunteer_at' => 'datetime',
            'email_notifications' => 'boolean',
            'sms_notifications' => 'boolean',
        ];
    }

    /**
     * Get all donations made by this user.
     */
    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    /**
     * Get all volunteer signups for this user.
     */
    public function volunteers(): HasMany
    {
        return $this->hasMany(Volunteer::class);
    }

    /**
     * Get campaigns created by this user.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'created_by');
    }

    /**
     * Get events created by this user.
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class, 'created_by');
    }

    /**
     * Get audit logs for this user.
     */
    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    /**
     * Check if user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is an admin or staff.
     */
    public function canAccessAdmin(): bool
    {
        return in_array($this->role, ['admin', 'staff']);
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user can manage campaigns.
     */
    public function canManageCampaigns(): bool
    {
        return in_array($this->role, ['admin', 'staff']);
    }

    /**
     * Check if user can manage events.
     */
    public function canManageEvents(): bool
    {
        return in_array($this->role, ['admin', 'staff']);
    }

    /**
     * Get user's total donations amount.
     */
    public function getTotalDonationsAttribute(): float
    {
        return $this->donations()->where('status', 'completed')->sum('amount');
    }

    /**
     * Get user's donation count.
     */
    public function getDonationCountAttribute(): int
    {
        return $this->donations()->where('status', 'completed')->count();
    }

    /**
     * Get user's volunteer count.
     */
    public function getVolunteerCountAttribute(): int
    {
        return $this->volunteers()->whereIn('status', ['confirmed', 'completed'])->count();
    }

    /**
     * Update last donation timestamp.
     */
    public function updateLastDonation(): void
    {
        $this->update(['last_donation_at' => now()]);
    }

    /**
     * Update last volunteer timestamp.
     */
    public function updateLastVolunteer(): void
    {
        $this->update(['last_volunteer_at' => now()]);
    }

    /**
     * Get the user's display name.
     */
    public function getDisplayNameAttribute(): string
    {
        return $this->name ?: 'Anonymous Donor';
    }

    /**
     * Scope to get users with a specific role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    /**
     * Scope to get admin users.
     */
    public function scopeAdmins($query)
    {
        return $query->whereIn('role', ['admin', 'staff']);
    }

    /**
     * Scope to get donors.
     */
    public function scopeDonors($query)
    {
        return $query->where('role', 'donor');
    }

    /**
     * Scope to get volunteers.
     */
    public function scopeVolunteers($query)
    {
        return $query->where('role', 'volunteer');
    }
}
