<?php

namespace App\Livewire;

use App\Models\Chat;
use App\Support\Partnerships;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * Count badge on the "Parcerias" entry of the bottom nav / sidebar: unread chat
 * messages + candidates waiting for my answer (as the shift's author) + shifts
 * where I was accepted and still have to confirm (as the courier). Refreshes when
 * a notification arrives over the user's private channel, when a conversation gets
 * read and when a partnership step is taken.
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
            // Accepted / declined / confirmed somewhere on the page: the pending counts changed.
            'partnerships-changed' => '$refresh',
        ];
    }

    #[Computed]
    public function total(): int
    {
        $me = Auth::id();

        return (int) Chat::unreadCountsFor($me)->sum()
            + (int) Partnerships::pendingCandidatesByShift($me)->sum()
            + Partnerships::awaitingConfirmationCount($me);
    }

    public function render()
    {
        return view('livewire.chat-unread-badge');
    }
}
