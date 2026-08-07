<?php

namespace App\Livewire\Chats;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Profile;
use App\Models\Shift;
use App\Support\Partnerships;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Parcerias — ZunMoto')]
class Index extends Component
{
    /** 'conversas' | 'candidaturas' */
    public string $tab = 'conversas';

    public ?string $openShift = null;

    /** Pending decline confirmation: ['shiftId' => , 'courierId' => , 'name' => ]. */
    public ?array $declineTarget = null;

    public function mount(): void
    {
        $this->tab = request('tab') === 'candidaturas' ? 'candidaturas' : 'conversas';
        $this->openShift = request('vagaId');
        if ($this->openShift) {
            $this->tab = 'candidaturas';
        }
    }

    public function getListeners(): array
    {
        return ['echo-private:user.'.Auth::id().',.notification.received' => 'onSignal'];
    }

    /** New message/application → recompute the lists (order depends on latest activity). */
    public function onSignal(): void
    {
        unset($this->conversations, $this->myShifts);
    }

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'candidaturas' ? 'candidaturas' : 'conversas';
    }

    public function toggleShift(string $shiftId): void
    {
        $this->openShift = $this->openShift === $shiftId ? null : $shiftId;
    }

    public function acceptCandidate(string $shiftId, string $courierId)
    {
        $shift = Shift::with('applications')->find($shiftId);
        if (! $shift || $shift->creator_id !== Auth::id()) {
            return null;
        }

        $chat = Partnerships::accept($shift, $courierId);
        if (! $chat) {
            $this->dispatch('toast', message: 'Essa vaga já está completa');

            return null;
        }

        $this->dispatch('toast', message: 'Candidato aceito!');

        return $this->redirect(route('chats.show', $chat->id), navigate: true);
    }

    public function requestDecline(string $shiftId, string $courierId): void
    {
        $name = Profile::where('id', $courierId)->value('name') ?: 'este candidato';
        $this->declineTarget = ['shiftId' => $shiftId, 'courierId' => $courierId, 'name' => $name];
    }

    public function confirmDecline(): void
    {
        if (! $this->declineTarget) {
            return;
        }

        $shift = Shift::find($this->declineTarget['shiftId']);
        if ($shift && $shift->creator_id === Auth::id()) {
            Partnerships::decline($shift, $this->declineTarget['courierId']);
            $this->dispatch('toast', message: 'Candidato recusado');
        }

        $this->declineTarget = null;
        unset($this->myShifts);
    }

    public function openChatWith(string $shiftId, string $courierId)
    {
        $shift = Shift::find($shiftId);
        if (! $shift || $shift->creator_id !== Auth::id()) {
            return null;
        }

        $chat = Chat::findOrCreateBetween($shiftId, Auth::id(), $courierId);

        return $this->redirect(route('chats.show', $chat->id), navigate: true);
    }

    protected function expired(?Shift $shift): bool
    {
        if (! $shift) {
            return false;
        }

        return Carbon::parse($shift->date->toDateString().' '.$shift->end_time)->isPast();
    }

    #[Computed]
    public function conversations(): array
    {
        $me = Auth::id();

        $chats = Chat::where(fn ($q) => $q->where('user_a', $me)->orWhere('user_b', $me))
            ->with('shift')
            ->get();

        $otherIds = $chats->map(fn ($c) => $c->otherParticipant($me))->unique()->values();
        $profiles = Profile::publicColumns()->whereIn('id', $otherIds)->get()->keyBy('id');

        // Fetch only the latest message per chat (avoids loading every message).
        $latest = Message::whereIn('chat_id', $chats->pluck('id'))
            ->selectRaw('chat_id, MAX(created_at) as last_at')
            ->groupBy('chat_id')
            ->get();

        $lastByChat = $latest->isEmpty()
            ? collect()
            : Message::where(function ($q) use ($latest) {
                foreach ($latest as $row) {
                    $q->orWhere(fn ($w) => $w->where('chat_id', $row->chat_id)->where('created_at', $row->last_at));
                }
            })->get()->keyBy('chat_id');

        $items = $chats->map(fn ($c) => [
            'chat' => $c,
            'other' => $profiles[$c->otherParticipant($me)] ?? null,
            'last' => $lastByChat[$c->id] ?? null,
            'shift' => $c->shift,
            'expired' => $this->expired($c->shift),
        ]);

        return [
            'active' => $items->where('expired', false)->values(),
            'expired' => $items->where('expired', true)->values(),
        ];
    }

    #[Computed]
    public function myShifts(): array
    {
        $shifts = Shift::where('creator_id', Auth::id())
            ->with('applications.user.profile')
            ->latest()
            ->get();

        $isExpiredOrFilled = fn ($s) => $s->status === Shift::STATUS_FILLED || $this->expired($s);

        return [
            'active' => $shifts->reject($isExpiredOrFilled)->values(),
            'expired' => $shifts->filter($isExpiredOrFilled)->values(),
        ];
    }

    public function render()
    {
        return view('livewire.chats.index');
    }
}
