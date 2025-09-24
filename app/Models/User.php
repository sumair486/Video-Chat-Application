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
        'last_seen_at'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
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
            'last_seen_at' => 'datetime',
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
     * Update user's last seen timestamp
     */
    public function updateLastSeen(): void
    {
        $this->update(['last_seen_at' => now()]);
    }
}