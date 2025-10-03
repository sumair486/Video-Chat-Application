<?php
// app/Events/UserTyping.php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $userId;
    public $userName;
    public $typing;
    public $receiverId;

    public function __construct($userId, $userName, $receiverId, $typing)
    {
        $this->userId = $userId;
        $this->userName = $userName;
        $this->receiverId = $receiverId;
        $this->typing = $typing;
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->receiverId);
    }

    public function broadcastWith()
    {
        return [
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'typing' => $this->typing
        ];
    }

    public function broadcastAs()
    {
        return 'user.typing';
    }
}