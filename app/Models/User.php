<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar',
        'last_seen_at',
        'face_descriptors',
        'face_image_path',
        'face_auth_enabled',
        'face_enrolled_at',
        'face_auth_attempts',
        'face_auth_locked_until'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
         'face_descriptors',
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
            'last_seen_at' => 'datetime',

        'face_enrolled_at' => 'datetime',
        'face_auth_locked_until' => 'datetime',
        'face_descriptors' => 'array',
        'face_auth_enabled' => 'boolean',
        'face_auth_attempts' => 'integer',

            'password' => 'hashed',
        ];
    }

    /**
     * Get messages sent by this user
     */
    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'user_id');
    }

    /**
     * Get messages received by this user
     */
    public function receivedMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id');
    }

    /**
     * Get all messages (sent and received) for this user
     */
    public function allMessages()
    {
        return Message::where('user_id', $this->id)
            ->orWhere('receiver_id', $this->id);
    }

    /**
     * Get conversation with another user
     */
    public function conversationWith(User $user)
    {
        return Message::betweenUsers($this->id, $user->id)
            ->orderBy('created_at', 'asc');
    }

    /**
     * Get unread messages count from a specific user
     */
    public function unreadMessagesFrom(User $user): int
    {
        return $this->receivedMessages()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    /**
     * Get total unread messages count
     */
    public function totalUnreadMessages(): int
    {
        return $this->receivedMessages()
            ->where('is_read', false)
            ->count();
    }

      public function faceAuthLogs(): HasMany
    {
        return $this->hasMany(FaceAuthLog::class);
    }

    /**
     * Check if user is online (seen in last 5 minutes)
     */
    public function isOnline(): bool
    {
        if (!$this->last_seen_at) {
            return false;
        }

        return $this->last_seen_at->gt(now()->subMinutes(5));
    }

    /**
     * Update user's last seen timestamp and broadcast status change
     */
    public function updateLastSeen(): void
    {
        $wasOnline = $this->isOnline();
        
        $this->update(['last_seen_at' => now()]);
        
        // Broadcast status change if user just came online
        if (!$wasOnline) {
            broadcast(new \App\Events\UserStatusChanged($this, 'online'));
        }
    }

    /**
     * Mark user as offline and broadcast status change
     */
    public function markOffline(): void
    {
        $wasOnline = $this->isOnline();
        
        $this->update(['last_seen_at' => now()->subMinutes(10)]);
        
        // Broadcast status change if user just went offline
        if ($wasOnline) {
            broadcast(new \App\Events\UserStatusChanged($this, 'offline'));
        }
    }


     public function hasFaceAuthEnabled(): bool
    {
        return $this->face_auth_enabled && !empty($this->face_descriptors);
    }

    /**
     * Check if user is locked out from face authentication
     */
    public function isFaceAuthLockedOut(): bool
    {
        return $this->face_auth_locked_until && now()->lt($this->face_auth_locked_until);
    }

    /**
     * Get remaining lockout time in minutes
     */
    public function getFaceAuthLockoutMinutes(): int
    {
        if (!$this->isFaceAuthLockedOut()) {
            return 0;
        }

        return $this->face_auth_locked_until->diffInMinutes(now());
    }

    /**
     * Get face authentication statistics
     */
    public function getFaceAuthStats(): array
    {
        $stats = $this->faceAuthLogs()
            ->selectRaw('
                attempt_type,
                COUNT(*) as total_attempts,
                SUM(CASE WHEN success = 1 THEN 1 ELSE 0 END) as successful_attempts,
                AVG(CASE WHEN success = 1 AND JSON_EXTRACT(face_data, "$.confidence") IS NOT NULL 
                    THEN JSON_EXTRACT(face_data, "$.confidence") ELSE NULL END) as avg_confidence,
                MAX(created_at) as last_attempt
            ')
            ->groupBy('attempt_type')
            ->get()
            ->keyBy('attempt_type');

        return $stats->toArray();
    }

    /**
     * Get recent face authentication attempts
     */
    public function getRecentFaceAuthAttempts(int $minutes = 60): int
    {
        return $this->faceAuthLogs()
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->count();
    }

    /**
     * Get successful face authentication rate
     */
    public function getFaceAuthSuccessRate(): float
    {
        $total = $this->faceAuthLogs()->where('attempt_type', 'verification')->count();
        
        if ($total === 0) {
            return 0;
        }

        $successful = $this->faceAuthLogs()
            ->where('attempt_type', 'verification')
            ->where('success', true)
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Reset face authentication attempts
     */
    public function resetFaceAuthAttempts(): bool
    {
        return $this->update([
            'face_auth_attempts' => 0,
            'face_auth_locked_until' => null
        ]);
    }

    /**
     * Check if user can attempt face authentication
     */
    public function canAttemptFaceAuth(): bool
    {
        return $this->hasFaceAuthEnabled() && !$this->isFaceAuthLockedOut();
    }

    /**
     * Get user's face image URL (if exists and accessible)
     */
    public function getFaceImageUrl(): ?string
    {
        if (!$this->face_image_path) {
            return null;
        }

        // Return a secure URL for face images (implement proper access control)
        return route('user.face-image', ['user' => $this->id]);
    }

    /**
     * Scope for users with face authentication enabled
     */
    public function scopeWithFaceAuth($query)
    {
        return $query->where('face_auth_enabled', true)
                    ->whereNotNull('face_descriptors');
    }

    /**
     * Scope for users currently locked out
     */
    public function scopeLockedOut($query)
    {
        return $query->whereNotNull('face_auth_locked_until')
                    ->where('face_auth_locked_until', '>', now());
    }

    /**
     * Boot method to set up model events
     */
    protected static function boot()
    {
        parent::boot();

        // Clean up face data when user is deleted
        static::deleting(function ($user) {
            if ($user->face_image_path) {
                \Storage::disk('private')->delete($user->face_image_path);
            }
            
            // Delete face auth logs
            $user->faceAuthLogs()->delete();
        });
    }
}