<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receiver_id',
        'message',
        'type',
        'is_read',
        'read_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user who sent the message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who received the message
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope to get messages between two users
     */
    public function scopeBetweenUsers($query, $user1Id, $user2Id)
    {
        return $query->where(function ($q) use ($user1Id, $user2Id) {
            $q->where('user_id', $user1Id)->where('receiver_id', $user2Id);
        })->orWhere(function ($q) use ($user1Id, $user2Id) {
            $q->where('user_id', $user2Id)->where('receiver_id', $user1Id);
        });
    }

    /**
     * Scope to get unread messages for a user
     */
    public function scopeUnreadForUser($query, $userId)
    {
        return $query->where('receiver_id', $userId)->where('is_read', false);
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        $this->update([
            'is_read' => true,
            'read_at' => now()
        ]);
    }
}