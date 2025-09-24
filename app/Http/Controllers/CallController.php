<?php

namespace App\Http\Controllers;

use App\Events\VideoSignal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class CallController extends Controller
{
    /**
     * Handle video call signaling between users
     */
    public function signal(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|in:offer,answer,candidate',
            'to' => 'required|integer|exists:users,id',
            'data' => 'required'
        ]);

        $currentUser = auth()->user();
        $toUserId = $request->input('to');
        $type = $request->input('type');
        $data = $request->input('data');

        // Prevent signaling to self
        if ($currentUser->id == $toUserId) {
            return response()->json([
                'success' => false,
                'error' => 'Cannot signal to yourself'
            ], 400);
        }

        // Check if target user exists and is different from current user
        $targetUser = User::find($toUserId);
        if (!$targetUser) {
            return response()->json([
                'success' => false,
                'error' => 'Target user not found'
            ], 404);
        }

        // Prepare the signal payload
        $payload = [
            'from' => $currentUser->id,
            'to' => $toUserId,
            'type' => $type,
            'data' => $data,
            'timestamp' => now()->toISOString(),
            'user' => [
                'id' => $currentUser->id,
                'name' => $currentUser->name
            ]
        ];

        // Log the signaling for debugging
        \Log::info('Video Signal', [
            'from' => $currentUser->id,
            'to' => $toUserId,
            'type' => $type,
            'timestamp' => $payload['timestamp']
        ]);

        try {
            // Broadcast the signal to the target user's private channel
            broadcast(new VideoSignal($toUserId, $payload));

            return response()->json([
                'success' => true,
                'message' => "Signal sent to user {$toUserId}",
                'type' => $type
            ]);

        } catch (\Exception $e) {
            \Log::error('Video Signal Error', [
                'error' => $e->getMessage(),
                'from' => $currentUser->id,
                'to' => $toUserId,
                'type' => $type
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to send signal'
            ], 500);
        }
    }

    /**
     * Get call history for the authenticated user
     */
    public function callHistory(): JsonResponse
    {
        // This is optional - you can implement call logging if needed
        return response()->json([
            'calls' => [], // Implement call history logic here
            'message' => 'Call history not implemented yet'
        ]);
    }

    /**
     * Check if a user is available for calls
     */
    public function checkAvailability(User $user): JsonResponse
    {
        $currentUser = auth()->user();

        if ($user->id === $currentUser->id) {
            return response()->json([
                'available' => false,
                'reason' => 'Cannot call yourself'
            ]);
        }

        // Check if user is online (last seen within 5 minutes)
        $isOnline = $user->isOnline();

        return response()->json([
            'available' => $isOnline,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'last_seen' => $user->last_seen_at,
                'is_online' => $isOnline
            ],
            'reason' => $isOnline ? 'User is available' : 'User is offline'
        ]);
    }
}