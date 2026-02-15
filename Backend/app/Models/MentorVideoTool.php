<?php

namespace App\Models;

use App\Traits\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Crypt;
use Spatie\Activitylog\Traits\LogsActivity;

class MentorVideoTool extends Model
{
    use HasFactory, LogsActivity, HasActivityLog;

    protected $fillable = [
        'mentor_id',
        'tool_type',
        'account_id',
        'account_email',
        'access_token',
        'refresh_token',
        'token_expires_at',
        'tool_settings',
        'is_active',
        'is_default',
        'last_sync_at',
    ];

    protected $casts = [
        'tool_settings' => 'array',
        'token_expires_at' => 'datetime',
        'last_sync_at' => 'datetime',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    protected array $logFields = [
        'tool_type',
        'account_email',
        'is_active',
        'is_default',
        'last_sync_at',
    ];

    protected string $moduleName = 'Mentor Video Tool';
    protected string $logName = 'mentor_video_tool';

    const TOOL_TYPES = [
        'zoom' => 'Zoom',
        'teams' => 'Microsoft Teams',
        'google_meet' => 'Google Meet',
    ];

    /**
     * Get the mentor that owns this video tool integration.
     */
    public function mentor(): BelongsTo
    {
        return $this->belongsTo(Mentor::class);
    }

    /**
     * Get the encrypted access token.
     */
    public function getAccessTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted access token.
     */
    public function setAccessTokenAttribute($value): void
    {
        $this->attributes['access_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Get the encrypted refresh token.
     */
    public function getRefreshTokenAttribute($value): ?string
    {
        return $value ? Crypt::decryptString($value) : null;
    }

    /**
     * Set the encrypted refresh token.
     */
    public function setRefreshTokenAttribute($value): void
    {
        $this->attributes['refresh_token'] = $value ? Crypt::encryptString($value) : null;
    }

    /**
     * Check if the token is expired.
     */
    public function isTokenExpired(): bool
    {
        if (!$this->token_expires_at) {
            return false;
        }

        return $this->token_expires_at->isPast();
    }

    /**
     * Check if the integration is valid and ready to use.
     */
    public function isValid(): bool
    {
        return $this->is_active && 
               $this->access_token && 
               !$this->isTokenExpired();
    }

    /**
     * Get the tool display name.
     */
    public function getToolDisplayNameAttribute(): string
    {
        return self::TOOL_TYPES[$this->tool_type] ?? $this->tool_type;
    }

    /**
     * Set this tool as the default for the mentor.
     */
    public function setAsDefault(): bool
    {
        // Remove default status from other tools for this mentor
        static::where('mentor_id', $this->mentor_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        return $this->update(['is_default' => true]);
    }

    /**
     * Get the default video tool for a mentor.
     * Returns only tools that are active, default, and have access_token.
     * 
     * Note: This method does NOT check if the token is expired because we auto-refresh tokens.
     * Token expiration is handled automatically when creating sessions.
     * Actual token validation happens when trying to use the token (e.g., creating a meeting).
     * If the token is invalid at that point, the tool will be marked as inactive automatically.
     */
    public static function getDefaultForMentor(int $mentorId): ?self
    {
        $tool = static::where('mentor_id', $mentorId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->whereNotNull('access_token')
            ->first();
        
        // Return tool if it exists and has access_token
        // We don't check token expiration here because we auto-refresh tokens
        return $tool;
    }

    /**
     * Get the global default video tool by tool type, email, or mentor ID.
     * Used for global account configurations (e.g., shared Google Meet account).
     * 
     * @param string $toolType The type of tool (e.g., 'google_meet')
     * @param string|null $accountEmail Optional account email to match (case-insensitive)
     * @param int|null $mentorId Optional mentor ID to match
     * @return self|null
     */
    public static function getGlobalDefault(string $toolType, ?string $accountEmail = null, ?int $mentorId = null): ?self
    {
        $query = static::where('tool_type', $toolType)
            ->where('is_active', true)
            ->whereNotNull('access_token');

        // Match by account email if provided (case-insensitive comparison)
        if ($accountEmail) {
            // Normalize email to lowercase for consistent matching
            $normalizedEmail = strtolower(trim($accountEmail));
            $query->whereRaw('LOWER(TRIM(account_email)) = ?', [$normalizedEmail]);
        }

        // Match by mentor ID if provided
        if ($mentorId) {
            $query->where('mentor_id', $mentorId);
        }

        // If neither email nor mentor ID provided, return null
        if (!$accountEmail && !$mentorId) {
            return null;
        }

        $tool = $query->first();

        // Return tool if it exists and has access_token
        // We don't check token expiration here because we auto-refresh tokens
        return $tool;
    }

    /**
     * Get all active video tools for a mentor.
     */
    public static function getActiveForMentor(int $mentorId): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('mentor_id', $mentorId)
            ->where('is_active', true)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Scope to get tools by type.
     */
    public function scopeByType($query, string $toolType)
    {
        return $query->where('tool_type', $toolType);
    }

    /**
     * Scope to get active tools.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get default tools.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
