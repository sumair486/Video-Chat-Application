<?php

use Illuminate\Support\Facades\Broadcast;

// Private channel for video calls - only the user can listen to their own channel
Broadcast::channel('video.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Private channel for chat messages - only the user can listen to their own channel
Broadcast::channel('chat.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Public channel for user status updates - everyone can listen
Broadcast::channel('user-status', function ($user) {
    return true; // Allow all authenticated users to listen
});

// Presence channel for showing who's online in the chat
Broadcast::channel('chat-presence', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name,
        'avatar' => $user->avatar ?? null,
        'status' => 'online'
    ];
});

// Private channel for direct messages between two users
Broadcast::channel('direct-message.{userId1}.{userId2}', function ($user, $userId1, $userId2) {
    // User can only listen if they are one of the participants
    return (int) $user->id === (int) $userId1 || (int) $user->id === (int) $userId2;
});