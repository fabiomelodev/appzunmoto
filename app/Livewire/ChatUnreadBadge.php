<?php

namespace App\Livewire;

use App\Models\Chat;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Total of unread chat messages, as a count badge on the "Parcerias" entry of
 * the bottom nav / sidebar. Refreshes when a message notification arrives over
 * the user's private channel, and when a conversation gets read.
 */
class ChatUnreadBadge extends Component
{
    /** Positioning classes for the wrapper, supplied by the nav that embeds it. */
    public string $class = '';

    public function getListeners(): array
    {
        return [
            'echo-private:user.'.Auth::id().',.notification.received' => '$refresh',
            'chats-read' => '$refresh',
        ];
    }

    #[Computed]
    public function total(): int
    {
        return (int) Chat::unreadCountsFor(Auth::id())->sum();
    }

    public function render()
    {
        return view('livewire.chat-unread-badge');
    }
}
