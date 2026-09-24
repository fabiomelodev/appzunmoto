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
    protected const PUSH_TYPES = ['vaga', 'nova_vaga', 'mensagem', 'avaliacao'];

    /**
     * "turno" covers both "você foi aceito" and "parceria confirmada" (see
     * Partnerships.php) — only the latter pushes for now, matched by title
     * since the Notification model has no subtype column.
     */
    protected const PUSH_TITLES = ['Parceria confirmada!'];

    public function created(Notification $notification): void
    {
        try {
            broadcast(new NotificationReceived($notification));
        } catch (\Throwable $e) {
            // Realtime is best-effort: never let a down Reverb break the request.
            report($e);
        }

        $shouldPush = in_array($notification->type, self::PUSH_TYPES, true)
            || in_array($notification->title, self::PUSH_TITLES, true);

        if ($shouldPush) {
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
                $notification->resolveUrl(),
            ));
        } catch (\Throwable $e) {
            // Push is best-effort too: a bad subscription must never break the request.
            report($e);
        }
    }
}
