<?php

namespace App\Observers;

use App\Events\NotificationReceived;
use App\Models\Notification;

/**
 * Broadcasts every new in-app notification to its recipient's private channel.
 * Centralising it here covers all creation points (message/application/shift/
 * partnership observers) and inherits their UserSetting preference filters.
 */
class NotificationObserver
{
    public function created(Notification $notification): void
    {
        try {
            broadcast(new NotificationReceived($notification));
        } catch (\Throwable $e) {
            // Realtime is best-effort: never let a down Reverb break the request.
            report($e);
        }
    }
}
