<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Display the chat interface with user selection
     */
    public function index(Request $request): View
    {
        $currentUser = auth()->user();
        $chattingWith = null;
        $messages = collect();

        // Get the user we're chatting with (if specified)
        if ($request->has('with')) {
            $withUserId = $request->get('with');
            $chattingWith = User::find($withUserId);
            
            if ($chattingWith && $chattingWith->id !== $currentUser->id) {
                // Get messages between current user and the selected user
                $messages = Message::betweenUsers($currentUser->id, $chattingWith->id)
                    ->with(['user', 'receiver'])
                    ->orderBy('created_at', 'asc')
                    ->get();

                // Mark messages as read
                Message::where('receiver_id', $currentUser->id)
                    ->where('user_id', $chattingWith->id)
                    ->where('is_read', false)
                    ->update([
                        'is_read' => true,
                        'read_at' => now()
                    ]);
            }
        }

        // Get all users except current user for the user list
        $users = User::where('id', '!=', $currentUser->id)
            ->withCount([
                'sentMessages as unread_messages_count' => function ($query) use ($currentUser) {
                    $query->where('receiver_id', $currentUser->id)->where('is_read', false);
                }
            ])
            ->get();

        return view('chat.index', compact('messages', 'users', 'chattingWith'));
    }

    /**
     * Store a new message
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:1000',
            'receiver_id' => 'required|exists:users,id'
        ]);

        $currentUser = auth()->user();
        
        // Don't allow sending message to self
        if ($currentUser->id == $request->receiver_id) {
            return response()->json(['error' => 'Cannot send message to yourself'], 400);
        }

        $message = Message::create([
            'user_id' => $currentUser->id,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'type' => 'text'
        ]);

        // Load relationships
        $message->load(['user', 'receiver']);

        // Broadcast the message to both users
        broadcast(new MessageSent($message));

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Get conversation between current user and another user
     */
    public function conversation(User $user): JsonResponse
    {
        $currentUser = auth()->user();
        
        if ($user->id === $currentUser->id) {
            return response()->json(['error' => 'Invalid user'], 400);
        }

        $messages = Message::betweenUsers($currentUser->id, $user->id)
            ->with(['user', 'receiver'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Mark messages as read
        Message::where('receiver_id', $currentUser->id)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now()
            ]);

        return response()->json([
            'messages' => $messages,
            'user' => $user
        ]);
    }

    /**
     * Get unread message count for current user
     */
    public function unreadCount(): JsonResponse
    {
        $count = Message::unreadForUser(auth()->id())->count();
        
        return response()->json(['unread_count' => $count]);
    }

    /**
     * Mark conversation as read
     */
    public function markAsRead(User $user): JsonResponse
    {
        $currentUser = auth()->user();
        
        // Get unread messages from this user
        $messages = Message::where('receiver_id', $currentUser->id)
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->get();

        $messageIds = $messages->pluck('id')->toArray();

        if (!empty($messageIds)) {
            // Mark messages as read
            Message::whereIn('id', $messageIds)->update([
                'is_read' => true,
                'read_at' => now()
            ]);

            // Broadcast read status to the sender
            broadcast(new \App\Events\MessageRead($messageIds, $currentUser->id, $user->id));
        }

        return response()->json(['success' => true, 'marked_count' => count($messageIds)]);
    }
}