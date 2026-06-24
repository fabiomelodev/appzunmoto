<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A new in-app notification — broadcast on the recipient's private channel.
 * Drives the notifications list, the unread badge and the partnerships-list
 * refresh (the `type` discriminates which UI reacts).
 */
class NotificationReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Notification $notification) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.'.$this->notification->user_id);
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'type' => $this->notification->type,          // mensagem | vaga | turno | ...
            'title' => $this->notification->title,
            'description' => $this->notification->description,
            'payload' => $this->notification->payload,    // {chat_id} | {shift_id} | ...
            'read' => false,
            'created_at' => ($this->notification->created_at ?? now())->toIso8601String(),
        ];
    }
}
