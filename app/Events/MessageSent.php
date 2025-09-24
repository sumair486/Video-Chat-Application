<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct(Message $message)
    {
        // eager load user and receiver for convenience
        $this->message = $message->load(['user', 'receiver']);
    }

    /**
     * Get the channels the event should broadcast on.
     * Now broadcasts to both sender and receiver private channels
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        $channels = [];
        
        // Send to sender's private channel
        $channels[] = new PrivateChannel('chat.' . $this->message->user_id);
        
        // Send to receiver's private channel (if different from sender)
        if ($this->message->receiver_id && $this->message->receiver_id !== $this->message->user_id) {
            $channels[] = new PrivateChannel('chat.' . $this->message->receiver_id);
        }
        
        return $channels;
    }

    public function broadcastWith()
    {
        return ['message' => $this->message];
    }

    public function broadcastAs()
    {
        return 'message.sent';
    }
}