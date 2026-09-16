<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Sends one of our in-app Notification rows as a browser push.
 *
 * Deliberately NOT queued: on shared hosting there's no persistent worker,
 * only a cron-driven `queue:work` running once a minute (see
 * routes/console.php), which would add up to that much delay. Sent inline
 * instead so it goes out the moment the triggering request happens.
 * NotifyCouriersOfNewShift (the one fan-out with many recipients) already
 * runs as its own queued job, so sending synchronously from inside it still
 * doesn't block any user-facing request.
 */
class PushNotification extends Notification
{
    public function __construct(
        protected string $title,
        protected string $body,
        protected ?string $url = null,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, self $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title($this->title)
            ->icon(asset('assets/favicon.png'))
            ->body($this->body)
            ->data(['url' => $this->url ?? url('/')])
            ->options(['TTL' => 300]);
    }
}
