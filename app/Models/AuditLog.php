<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    /**
     * Indicates if the model should be timestamped.
     * We only use created_at for audit logs.
     *
     * @var bool
     */
    public $timestamps = false;

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = ['created_at'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'changes',
        'original',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'changes' => 'array',
        'original' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($auditLog) {
            if (!$auditLog->created_at) {
                $auditLog->created_at = now();
            }

            // Auto-capture IP and user agent if available
            if (request()) {
                $auditLog->ip_address = $auditLog->ip_address ?: request()->ip();
                $auditLog->user_agent = $auditLog->user_agent ?: request()->userAgent();
            }
        });
    }

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the related model instance.
     */
    public function getModelInstance()
    {
        $modelClass = "App\\Models\\{$this->model}";
        
        if (class_exists($modelClass)) {
            return $modelClass::find($this->model_id);
        }

        return null;
    }

    /**
     * Get formatted action description.
     */
    public function getActionDescriptionAttribute(): string
    {
        $userName = $this->user ? $this->user->name : 'System';
        $modelName = strtolower($this->model);
        
        return match($this->action) {
            'created' => "{$userName} created a new {$modelName}",
            'updated' => "{$userName} updated a {$modelName}",
            'deleted' => "{$userName} deleted a {$modelName}",
            'restored' => "{$userName} restored a {$modelName}",
            'login' => "{$userName} logged in",
            'logout' => "{$userName} logged out",
            'donation_completed' => "{$userName} completed a donation",
            'volunteer_confirmed' => "{$userName} confirmed a volunteer signup",
            'campaign_published' => "{$userName} published a campaign",
            'event_published' => "{$userName} published an event",
            default => "{$userName} performed {$this->action} on a {$modelName}",
        };
    }

    /**
     * Get the changes in a human-readable format.
     */
    public function getFormattedChangesAttribute(): array
    {
        if (!$this->changes) {
            return [];
        }

        $formatted = [];
        foreach ($this->changes as $field => $newValue) {
            $originalValue = $this->original[$field] ?? null;
            
            $formatted[] = [
                'field' => $this->formatFieldName($field),
                'from' => $this->formatValue($originalValue),
                'to' => $this->formatValue($newValue),
            ];
        }

        return $formatted;
    }

    /**
     * Format field names for display.
     */
    private function formatFieldName(string $field): string
    {
        return ucwords(str_replace('_', ' ', $field));
    }

    /**
     * Format values for display.
     */
    private function formatValue($value): string
    {
        if (is_null($value)) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value);
        }

        if (is_numeric($value) && strlen($value) > 10) {
            // Probably a timestamp
            return date('Y-m-d H:i:s', $value);
        }

        return (string) $value;
    }

    /**
     * Create an audit log entry.
     */
    public static function log(string $action, string $model, int $modelId, ?array $changes = null, ?array $original = null, ?int $userId = null): void
    {
        static::create([
            'user_id' => $userId ?: auth()->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'changes' => $changes,
            'original' => $original,
        ]);
    }

    /**
     * Scope to get logs for a specific model.
     */
    public function scopeForModel($query, string $model, ?int $modelId = null)
    {
        $query = $query->where('model', $model);
        
        if ($modelId) {
            $query->where('model_id', $modelId);
        }
        
        return $query;
    }

    /**
     * Scope to get logs by user.
     */
    public function scopeByUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get logs by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope to order by most recent.
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Get available actions.
     */
    public static function getActions(): array
    {
        return [
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            'login' => 'Login',
            'logout' => 'Logout',
            'donation_completed' => 'Donation Completed',
            'volunteer_confirmed' => 'Volunteer Confirmed',
            'campaign_published' => 'Campaign Published',
            'event_published' => 'Event Published',
        ];
    }
}