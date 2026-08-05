<?php

namespace App\Livewire\Chats;

use App\Models\Application;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Review;
use App\Models\Shift;
use App\Support\Partnerships;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Conversa — GiroMoto')]
class Show extends Component
{
    public string $chatId;

    public string $body = '';

    public int $rating = 0;

    public string $comment = '';

    public function mount(string $id): void
    {
        $chat = Chat::findOrFail($id);
        abort_unless($chat->hasParticipant(Auth::id()), 403);
        $this->chatId = $id;
    }

    /** Listen on this chat's private channel for new messages (replaces polling). */
    public function getListeners(): array
    {
        return [
            "echo-private:chat.{$this->chatId},.message.sent" => 'onMessage',
        ];
    }

    /** A round-trip re-runs render(), which reloads messages + partnership state. */
    public function onMessage(): void {}

    #[Computed]
    public function chat(): Chat
    {
        return Chat::with('shift')->findOrFail($this->chatId);
    }

    protected function courierId(Chat $chat, ?Shift $shift): ?string
    {
        if (! $shift) {
            return null;
        }
        $me = Auth::id();

        return $shift->creator_id === $me ? $chat->otherParticipant($me) : $me;
    }

    protected function expired(?Shift $shift): bool
    {
        if (! $shift) {
            return false;
        }

        return Carbon::parse($shift->date->toDateString().' '.$shift->end_time)->isPast();
    }

    public function send(): void
    {
        $this->validate(['body' => 'required|max:1000']);

        $shift = $this->chat()->shift;
        if ($this->expired($shift)) {
            return;
        }

        Message::create([
            'chat_id' => $this->chatId,
            'author_id' => Auth::id(),
            'body' => trim($this->body),
        ]);

        $this->body = '';
    }

    public function confirmPartnership(): void
    {
        $chat = $this->chat();
        $shift = $chat->shift;
        if (! $shift) {
            return;
        }

        $courierId = $this->courierId($chat, $shift);
        $filled = Partnerships::confirm($shift, Auth::id(), $courierId);

        $this->dispatch('toast', message: $filled ? 'Parceria confirmada!' : 'Aguardando confirmação da outra parte');
    }

    public function setRating(int $value): void
    {
        $this->rating = max(0, min(5, $value));
    }

    public function submitReview(): void
    {
        $chat = $this->chat();
        $shift = $chat->shift;

        // Only the creator reviews, only after the shift ended, only the other
        // (confirmed) participant, and never self.
        if (! $shift || $shift->creator_id !== Auth::id() || ! $this->expired($shift)) {
            return;
        }

        $courierId = $chat->otherParticipant(Auth::id());
        if (! $courierId || $courierId === Auth::id() || $this->rating < 1) {
            return;
        }

        $courierApp = Application::where('shift_id', $shift->id)->where('user_id', $courierId)->first();
        if (! $courierApp || ! $courierApp->confirmed) {
            return;
        }

        Review::updateOrCreate(
            ['shift_id' => $shift->id, 'author_id' => Auth::id(), 'target_id' => $courierId],
            ['rating' => $this->rating, 'comment' => trim($this->comment)],
        );

        $this->rating = 0;
        $this->comment = '';
        $this->dispatch('toast', message: 'Avaliação enviada!');
    }

    public function render()
    {
        $me = Auth::id();
        $chat = $this->chat();
        $shift = $chat->shift;
        $otherId = $chat->otherParticipant($me);
        $courierId = $this->courierId($chat, $shift);

        $messages = Message::where('chat_id', $this->chatId)->orderBy('created_at')->get();
        $other = Profile::publicColumns()->find($otherId);

        $courierApp = ($shift && $courierId)
            ? Application::where('shift_id', $shift->id)->where('user_id', $courierId)->first()
            : null;

        $confirmations = $courierApp?->confirmations ?? [];
        $confirmedHere = (bool) $courierApp?->confirmed;
        $expired = $this->expired($shift);

        // Conflict: courier already has a confirmed partnership on an overlapping shift.
        $conflict = null;
        if ($shift && $courierId) {
            // A confirmed partnership usually marks the shift as "filled", so we
            // must NOT exclude filled shifts here (matches the React behaviour).
            $conflict = Shift::where('id', '!=', $shift->id)
                ->whereDate('date', $shift->date->toDateString())
                ->whereHas('applications', fn ($q) => $q->where('user_id', $courierId)
                    ->where('status', Application::STATUS_ACCEPTED)->where('confirmed', true))
                ->get()
                ->first(fn ($v) => $v->start_time < $shift->end_time && $shift->start_time < $v->end_time);
        }

        $vm = [
            'chat' => $chat,
            'shift' => $shift,
            'messages' => $messages,
            'other' => $other,
            'otherId' => $otherId,
            'me' => $me,
            'confirmedHere' => $confirmedHere,
            'expired' => $expired,
            'conflict' => $conflict,
            'isCreator' => $shift && $shift->creator_id === $me,
            'canReview' => $shift && $confirmedHere && $expired && $shift->creator_id === $me && $courierId,
            // confirm-panel state
            'alreadyConfirmed' => in_array($me, $confirmations, true),
            'otherConfirmed' => in_array($otherId, $confirmations, true),
            'courierAccepted' => $courierApp && $courierApp->status === Application::STATUS_ACCEPTED,
            'confirmCount' => count($confirmations),
        ];

        return view('livewire.chats.show', $vm);
    }
}
