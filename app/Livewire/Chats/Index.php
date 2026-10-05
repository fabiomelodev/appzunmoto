<?php

namespace App\Livewire\Chats;

use App\Models\Application;
use App\Models\Chat;
use App\Models\Profile;
use App\Models\Shift;
use App\Support\Partnerships;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Parcerias — ZunMoto')]
class Index extends Component
{
    protected const TABS = ['publicadas', 'historico', 'interessadas'];

    /** 'publicadas' | 'historico' | 'interessadas' */
    public string $tab = 'publicadas';

    /** Sub-tab inside "Histórico de turnos": 'published' (Publiquei) | 'worked' (Trabalhei) — same split the old standalone History page used. */
    public string $historyTab = 'published';

    public ?string $openShift = null;

    /** Pending decline confirmation: ['shiftId' => , 'courierId' => , 'name' => ]. */
    public ?array $declineTarget = null;

    public function mount(): void
    {
        $requested = request('tab');
        $this->tab = in_array($requested, self::TABS, true)
            ? $requested
            // No shift to manage yet for a courier who never published one —
            // land them on their own interest list instead.
            : (Auth::user()->profile?->isBusiness() ? 'publicadas' : 'interessadas');

        $this->openShift = request('vagaId');
        if ($this->openShift) {
            $this->tab = 'publicadas';
        }
    }

    public function getListeners(): array
    {
        return ['echo-private:user.'.Auth::id().',.notification.received' => 'onSignal'];
    }

    /** New message/application → recompute the lists (order depends on latest activity). */
    public function onSignal(): void
    {
        unset($this->myShifts, $this->interestedShifts, $this->historyShifts, $this->interestedChats, $this->publishedUnread);
    }

    public function setTab(string $tab): void
    {
        $this->tab = in_array($tab, self::TABS, true) ? $tab : 'publicadas';
    }

    public function setHistoryTab(string $tab): void
    {
        $this->historyTab = $tab === 'worked' ? 'worked' : 'published';
    }

    public function toggleShift(string $shiftId): void
    {
        $this->openShift = $this->openShift === $shiftId ? null : $shiftId;
    }

    public function acceptCandidate(string $shiftId, string $courierId): void
    {
        $shift = Shift::with('applications')->find($shiftId);
        if (! $shift || $shift->creator_id !== Auth::id()) {
            return;
        }

        $chat = Partnerships::accept($shift, $courierId);
        if (! $chat) {
            $this->dispatch('toast', message: 'Essa vaga já está completa');

            return;
        }

        // The creator's side of "Confirmar Parceria" happens right here — no
        // need to redirect to the chat just to click it again. The courier
        // still confirms independently from their own side before the shift
        // is actually filled (see Partnerships::confirm()).
        $filled = Partnerships::confirm($shift, Auth::id(), $courierId);

        $this->dispatch('toast', message: $filled
            ? 'Candidato aceito e parceria confirmada!'
            : 'Candidato aceito! Aguardando confirmação do motoboy.');

        unset($this->myShifts);
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

        return $shift->hasEnded();
    }

    /**
     * "Vagas publicadas" tab: shifts this account created, split into
     * open (nobody confirmed yet), in progress (at least one courier confirmed the
     * partnership and the shift hasn't happened yet — for multi-courier shifts the
     * row still lets the creator accept the remaining slots) and closed (already over).
     */
    #[Computed]
    public function myShifts(): array
    {
        $shifts = Shift::where('creator_id', Auth::id())
            ->with('applications.user.profile')
            ->latest()
            ->get();

        [$ended, $upcoming] = $shifts->partition(fn ($s) => $this->expired($s));
        [$inProgress, $open] = $upcoming->partition(fn ($s) => $s->status === Shift::STATUS_FILLED
            || $s->applications->contains(fn ($a) => $a->status === Application::STATUS_ACCEPTED && $a->confirmed));

        return [
            'active' => $open->values(),
            'inProgress' => $inProgress->values(),
            'expired' => $ended->values(),
        ];
    }

    /** "Vagas interessadas" tab: every shift this courier applied to — pending, accepted or already confirmed — so it can be followed to the end (the row shows which step it is at). */
    #[Computed]
    public function interestedShifts()
    {
        $id = Auth::id();

        return Shift::whereHas('applications', fn ($q) => $q->where('user_id', $id)->whereIn('status', [Application::STATUS_INTERESTED, Application::STATUS_ACCEPTED]))
            ->where('creator_id', '!=', $id)
            ->orderByDesc('date')
            ->get();
    }


    /**
     * Chats the creator already opened with this courier, per interested shift (reply-only: the
     * courier can't start one), with how many of their messages are still unread.
     *
     * @return \Illuminate\Support\Collection<string, array{id: string, unread: int}> keyed by shift id
     */
    #[Computed]
    public function interestedChats()
    {
        $me = Auth::id();
        $chats = Chat::whereIn('shift_id', $this->interestedShifts->pluck('id'))
            ->where(fn ($q) => $q->where('user_a', $me)->orWhere('user_b', $me))
            ->get();
        $unread = Chat::unreadCountsFor($me, $chats->pluck('id')->all());

        return $chats->mapWithKeys(fn ($c) => [$c->shift_id => ['id' => $c->id, 'unread' => (int) ($unread[$c->id] ?? 0)]]);
    }

    /**
     * Unread counts for the creator's own shifts: total per shift, and per candidate
     * (key "shiftId|courierId") for the "Conversar com…" buttons.
     *
     * @return array{byShift: array<string, int>, byCandidate: array<string, int>}
     */
    #[Computed]
    public function publishedUnread(): array
    {
        $me = Auth::id();
        $shiftIds = $this->myShifts['active']->merge($this->myShifts['inProgress'])->merge($this->myShifts['expired'])->pluck('id');
        $chats = Chat::whereIn('shift_id', $shiftIds)
            ->where(fn ($q) => $q->where('user_a', $me)->orWhere('user_b', $me))
            ->get();
        $unread = Chat::unreadCountsFor($me, $chats->pluck('id')->all());

        $byShift = [];
        $byCandidate = [];
        foreach ($chats as $c) {
            $n = (int) ($unread[$c->id] ?? 0);
            $byShift[$c->shift_id] = ($byShift[$c->shift_id] ?? 0) + $n;
            $byCandidate[$c->shift_id.'|'.$c->otherParticipant($me)] = $n;
        }

        return compact('byShift', 'byCandidate');
    }

    /** "Histórico de turnos" tab — ported as-is from the old standalone History page. */
    #[Computed]
    public function historyShifts()
    {
        $id = Auth::id();

        return $this->historyTab === 'published'
            ? Shift::where('creator_id', $id)->orderByDesc('date')->get()
            : Shift::whereHas('applications', fn ($q) => $q->where('user_id', $id)->where('status', Application::STATUS_ACCEPTED))
                ->where('creator_id', '!=', $id)
                ->orderByDesc('date')
                ->get();
    }

    /** Keyed by shift_id, used by the "Histórico" / "Trabalhei" row to tell confirmed (concluded) apart from merely accepted. */
    #[Computed]
    public function myApplicationsByShift()
    {
        return Application::where('user_id', Auth::id())->get()->keyBy('shift_id');
    }

    public function render()
    {
        return view('livewire.chats.index');
    }
}
