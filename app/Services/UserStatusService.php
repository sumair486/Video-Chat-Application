<?php

namespace App\Services;

use App\Events\UserStatusChanged;
use App\Models\User;
use Carbon\Carbon;

class UserStatusService
{
    const ONLINE_THRESHOLD_MINUTES = 5;

    /**
     * Update user's online status and broadcast if changed
     */
    public function updateUserStatus(User $user): void
    {
        $wasOnline = $this->isUserOnline($user);
        
        $user->update(['last_seen_at' => now()]);
        
        $isNowOnline = $this->isUserOnline($user);
        
        // Only broadcast if status actually changed
        if ($wasOnline !== $isNowOnline) {
            $status = $isNowOnline ? 'online' : 'offline';
            broadcast(new UserStatusChanged($user->fresh(), $status));
        }
    }

    /**
     * Mark user as offline and broadcast status change
     */
    public function markUserOffline(User $user): void
    {
        $wasOnline = $this->isUserOnline($user);
        
        $user->update(['last_seen_at' => now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES + 1)]);
        
        if ($wasOnline) {
            broadcast(new UserStatusChanged($user->fresh(), 'offline'));
        }
    }

    /**
     * Check if user is currently online
     */
    public function isUserOnline(User $user): bool
    {
        if (!$user->last_seen_at) {
            return false;
        }

        return $user->last_seen_at->gt(now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES));
    }

    /**
     * Get all users with their current online status
     */
    public function getUsersWithStatus(): array
    {
        $users = User::select('id', 'name', 'last_seen_at')->get();
        
        return $users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'status' => $this->isUserOnline($user) ? 'online' : 'offline',
                'last_seen_at' => $user->last_seen_at,
            ];
        })->toArray();
    }

    /**
     * Cleanup offline users (run this periodically via a scheduled job)
     */
    public function cleanupOfflineUsers(): void
    {
        $cutoff = now()->subMinutes(self::ONLINE_THRESHOLD_MINUTES);
        
        $recentlyOfflineUsers = User::where('last_seen_at', '>', $cutoff->subMinutes(1))
            ->where('last_seen_at', '<=', $cutoff)
            ->get();

        foreach ($recentlyOfflineUsers as $user) {
            broadcast(new UserStatusChanged($user, 'offline'));
        }
    }
}