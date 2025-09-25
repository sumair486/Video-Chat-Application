<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $messageIds;
    public $readBy;
    public $senderId;

    /**
     * Create a new event instance.
     *
     * @param array|int $messageIds - Single message ID or array of message IDs
     * @param int $readBy - User ID who read the messages
     * @param int $senderId - User ID who sent the messages (receiver of this event)
     */
    public function __construct($messageIds, $readBy, $senderId)
    {
        $this->messageIds = is_array($messageIds) ? $messageIds : [$messageIds];
        $this->readBy = $readBy;
        $this->senderId = $senderId;
    }

    /**
     * Get the channels the event should broadcast on.
     * Send to the original message sender's private channel
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->senderId);
    }

    /**
     * The event's broadcast name.
     */
    public function broadcastAs()
    {
        return 'message.read';
    }

    /**
     * Get the data to broadcast.
     */
    public function broadcastWith()
    {
        return [
            'message_ids' => $this->messageIds,
            'read_by' => $this->readBy,
            'read_at' => now()->toISOString()
        ];
    }
}