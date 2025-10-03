<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FaceAuthLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attempt_type',
        'success',
        'ip_address',
        'user_agent',
        'face_data',
        'failure_reason'
    ];

    protected $casts = [
        'success' => 'boolean',
        'face_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the user that owns the face auth log
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for successful attempts
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope for failed attempts
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope for specific attempt type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('attempt_type', $type);
    }

    /**
     * Scope for recent attempts
     */
    public function scopeRecent($query, int $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    /**
     * Get formatted attempt type
     */
    public function getFormattedTypeAttribute(): string
    {
        return ucfirst(str_replace('_', ' ', $this->attempt_type));
    }

    /**
     * Get success status as text
     */
    public function getStatusTextAttribute(): string
    {
        return $this->success ? 'Success' : 'Failed';
    }

    /**
     * Get confidence score from face data if available
     */
    public function getConfidenceAttribute(): ?float
    {
        return $this->face_data['confidence'] ?? null;
    }
}