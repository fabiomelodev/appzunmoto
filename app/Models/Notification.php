<?php

namespace App\Models;

use App\Observers\NotificationObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Custom in-app notification (not Laravel's framework notifications).
 */
#[ObservedBy([NotificationObserver::class])]
class Notification extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'description',
        'read',
        'payload',
    ];

    protected $attributes = [
        'read' => false,
        'description' => '',
    ];

    protected $casts = [
        'read' => 'boolean',
        'payload' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Where clicking this notification should take the user. Single source of
     * truth shared by the notifications list (Notifications/Page::open()),
     * the browser push notification (NotificationObserver) and the realtime
     * floating toast (NotificationReceived::broadcastWith()).
     */
    public function resolveUrl(): ?string
    {
        $payload = $this->payload ?? [];

        if ($this->type === 'mensagem' && ! empty($payload['chat_id'])) {
            return route('chats.show', $payload['chat_id']);
        }
        if ($this->type === 'vaga') {
            if (! empty($payload['shift_id'])) {
                return route('chats.index', ['tab' => 'candidaturas', 'vagaId' => $payload['shift_id']]);
            }

            return route('shifts.index');
        }
        if (($this->type === 'turno' || $this->type === 'nova_vaga') && ! empty($payload['shift_id'])) {
            return route('shifts.show', $payload['shift_id']);
        }
        if ($this->type === 'documento') {
            return route('documents');
        }
        if (! empty($payload['shift_id'])) {
            return route('shifts.show', $payload['shift_id']);
        }

        return null;
    }
}
