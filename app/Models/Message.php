<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receiver_id',
        'message',
        'type',
        'is_read',
        'read_at',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'original_name'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'file_size' => 'integer',
    ];

    protected $appends = [
        'file_url',
        'formatted_file_size',
        'is_file_message'
    ];

    /**
     * Message sender
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Message receiver
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope for messages between two users
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
     * Scope for unread messages for a specific user
     */
    public function scopeUnreadForUser($query, $userId)
    {
        return $query->where('receiver_id', $userId)->where('is_read', false);
    }

    /**
     * Get the full URL for the file
     */
    public function getFileUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }

        return asset('storage/' . $this->file_path);
    }

    /**
     * Get formatted file size
     */
    public function getFormattedFileSizeAttribute(): ?string
    {
        if (!$this->file_size) {
            return null;
        }

        return $this->formatFileSize($this->file_size);
    }

    /**
     * Check if message is a file message
     */
    public function getIsFileMessageAttribute(): bool
    {
        return in_array($this->type, ['image', 'file', 'document', 'video', 'audio']);
    }

    /**
     * Check if the file is an image
     */
    public function isImage(): bool
    {
        return $this->type === 'image' || (
            $this->file_type && str_starts_with($this->file_type, 'image/')
        );
    }

    /**
     * Check if the file is a video
     */
    public function isVideo(): bool
    {
        return $this->type === 'video' || (
            $this->file_type && str_starts_with($this->file_type, 'video/')
        );
    }

    /**
     * Check if the file is an audio
     */
    public function isAudio(): bool
    {
        return $this->type === 'audio' || (
            $this->file_type && str_starts_with($this->file_type, 'audio/')
        );
    }

    /**
     * Check if the file is a document
     */
    public function isDocument(): bool
    {
        return $this->type === 'document' || (
            $this->file_type && in_array($this->file_type, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv'
            ])
        );
    }

    /**
     * Get file icon based on type
     */
    public function getFileIcon(): string
    {
        if ($this->isImage()) {
            return 'fas fa-image';
        }
        
        if ($this->isVideo()) {
            return 'fas fa-video';
        }
        
        if ($this->isAudio()) {
            return 'fas fa-music';
        }
        
        if ($this->isDocument()) {
            return match (true) {
                str_contains($this->file_type ?? '', 'pdf') => 'fas fa-file-pdf',
                str_contains($this->file_type ?? '', 'word') => 'fas fa-file-word',
                str_contains($this->file_type ?? '', 'excel') || str_contains($this->file_type ?? '', 'sheet') => 'fas fa-file-excel',
                str_contains($this->file_type ?? '', 'powerpoint') || str_contains($this->file_type ?? '', 'presentation') => 'fas fa-file-powerpoint',
                str_contains($this->file_type ?? '', 'text') => 'fas fa-file-alt',
                default => 'fas fa-file'
            };
        }
        
        return 'fas fa-file';
    }

    /**
     * Format file size in human readable format
     */
    private function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Auto-detect message type based on file type
     */
    public static function detectMessageType(?string $mimeType): string
    {
        if (!$mimeType) {
            return 'text';
        }

        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            in_array($mimeType, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-powerpoint',
                'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                'text/plain',
                'text/csv'
            ]) => 'document',
            default => 'file'
        };
    }


    public function reactions(): HasMany
{
    return $this->hasMany(MessageReaction::class);
}

/**
 * Get grouped reactions with counts
 */
public function getGroupedReactionsAttribute(): array
{
    return $this->reactions()
        ->selectRaw('reaction, COUNT(*) as count, GROUP_CONCAT(user_id) as user_ids')
        ->groupBy('reaction')
        ->get()
        ->map(function ($item) {
            return [
                'reaction' => $item->reaction,
                'count' => $item->count,
                'user_ids' => array_map('intval', explode(',', $item->user_ids))
            ];
        })
        ->toArray();
}

/**
 * Check if user has reacted to this message
 */
public function hasUserReacted(int $userId): ?string
{
    $reaction = $this->reactions()->where('user_id', $userId)->first();
    return $reaction ? $reaction->reaction : null;
}
}