<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new chat message — broadcast on the chat's private channel so both
 * participants' open conversations update instantly (replaces the 6s poll).
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('chat.'.$this->message->chat_id);
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'chat_id' => $this->message->chat_id,
            'author_id' => $this->message->author_id,
            'body' => $this->message->body,
            // Message has $timestamps=false and created_at is a DB default, so it
            // may be null in memory right after create() — fall back to now().
            'created_at' => ($this->message->created_at ?? now())->toIso8601String(),
        ];
    }
}
