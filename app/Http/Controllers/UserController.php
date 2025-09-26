<?php

namespace App\Http\Controllers;

use App\Services\UserStatusService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UserController extends Controller
{
    protected UserStatusService $userStatusService;

    public function __construct(UserStatusService $userStatusService)
    {
        $this->userStatusService = $userStatusService;
    }

    /**
     * Update user's online status
     */
    public function updateStatus(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $this->userStatusService->updateUserStatus($user);
            
            return response()->json([
                'success' => true,
                'status' => $this->userStatusService->isUserOnline($user) ? 'online' : 'offline',
                'last_seen' => $user->fresh()->last_seen_at
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Status update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to update status'
            ], 500);
        }
    }

    /**
     * Get user's current status
     */
    public function getStatus(Request $request): JsonResponse
    {
        $user = auth()->user();
        
        return response()->json([
            'user_id' => $user->id,
            'status' => $this->userStatusService->isUserOnline($user) ? 'online' : 'offline',
            'last_seen' => $user->last_seen_at
        ]);
    }

    /**
     * Get all users with their status
     */
    public function getAllUsersStatus(): JsonResponse
    {
        $usersWithStatus = $this->userStatusService->getUsersWithStatus();
        
        return response()->json([
            'users' => $usersWithStatus
        ]);
    }
}