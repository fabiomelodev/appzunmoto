<?php

namespace App\Observers;

use App\Events\NotificationReceived;
use App\Models\Notification;
use App\Notifications\PushNotification;

/**
 * Broadcasts every new in-app notification to its recipient's private channel.
 * Centralising it here covers all creation points (message/application/shift/
 * partnership observers) and inherits their UserSetting preference filters.
 */
class NotificationObserver
{
    /** Types that also fire a browser push, initially: vagas and chat. */
    protected const PUSH_TYPES = ['vaga', 'mensagem'];

    public function created(Notification $notification): void
    {
        try {
            broadcast(new NotificationReceived($notification));
        } catch (\Throwable $e) {
            // Realtime is best-effort: never let a down Reverb break the request.
            report($e);
        }

        if (in_array($notification->type, self::PUSH_TYPES, true)) {
            $this->sendPush($notification);
        }
    }

    protected function sendPush(Notification $notification): void
    {
        $user = $notification->user;
        if (! $user) {
            return;
        }

        try {
            $user->notify(new PushNotification(
                $notification->title,
                $notification->description,
                $this->urlFor($notification),
            ));
        } catch (\Throwable $e) {
            // Push is best-effort too: a bad subscription must never break the request.
            report($e);
        }
    }

    protected function urlFor(Notification $notification): ?string
    {
        $payload = $notification->payload ?? [];

        return match ($notification->type) {
            'vaga' => isset($payload['shift_id']) ? route('shifts.show', $payload['shift_id']) : null,
            'mensagem' => isset($payload['chat_id']) ? route('chats.show', $payload['chat_id']) : null,
            default => null,
        };
    }
}
