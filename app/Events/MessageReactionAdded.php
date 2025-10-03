<?php

namespace App\Events;

use App\Models\Message;
use App\Models\MessageReaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageReactionAdded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageId;
    public $userId;
    public $userName;
    public $reaction;
    public $senderId;
    public $receiverId;

    public function __construct(MessageReaction $messageReaction, Message $message)
    {
        $this->messageId = $messageReaction->message_id;
        $this->userId = $messageReaction->user_id;
        $this->userName = $messageReaction->user->name;
        $this->reaction = $messageReaction->reaction;
        $this->senderId = $message->user_id;
        $this->receiverId = $message->receiver_id;
    }

    public function broadcastOn()
    {
        // Broadcast to both sender and receiver
        return [
            new PrivateChannel('chat.' . $this->senderId),
            new PrivateChannel('chat.' . $this->receiverId)
        ];
    }

    public function broadcastWith()
    {
        return [
            'message_id' => $this->messageId,
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'reaction' => $this->reaction,
            'timestamp' => now()->toISOString()
        ];
    }

    public function broadcastAs()
    {
        return 'message.reaction.added';
    }
}


