<?php

namespace App\Events;

use App\Models\MessageCustomer;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public MessageCustomer $message)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('chat.customer.'.$this->message->user_id),
            new PrivateChannel('chat.admin'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => $this->message->toChatPayload(),
        ];
    }
}
