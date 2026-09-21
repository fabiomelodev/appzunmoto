<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Invisible, always-mounted component (see <livewire:notification-listener />
 * in components/layouts/app.blade.php) whose only job is staying subscribed
 * to the user's private notification channel on every authenticated page, so
 * the floating toast (resources/views/components/layouts/app.blade.php) can
 * show up regardless of which page the user happens to be on — not just the
 * pages that already listen for this event to refresh their own state
 * (Shifts/Index's unread badge, Notifications/Page's list, Chats/Index).
 */
class NotificationListener extends Component
{
    public function getListeners(): array
    {
        return ['echo-private:user.'.Auth::id().',.notification.received' => 'onNotification'];
    }

    public function onNotification($event): void
    {
        $this->dispatch(
            'notification-toast',
            id: $event['id'] ?? null,
            title: $event['title'] ?? '',
            description: $event['description'] ?? '',
            url: $event['url'] ?? null,
        );
    }

    public function render()
    {
        return view('livewire.notification-listener');
    }
}
