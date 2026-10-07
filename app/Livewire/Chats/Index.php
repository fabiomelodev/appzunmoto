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
    protected const TABS = ['publicadas', 'interessadas', 'andamento', 'historico'];

    /** 'publicadas' | 'interessadas' | 'andamento' | 'historico' */
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
        unset($this->myShifts, $this->inProgressPublished, $this->inProgressWorked, $this->interestedShifts, $this->interestedSections, $this->historyShifts, $this->interestedChats, $this->publishedUnread);
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
     * "Vagas publicadas" tab: only the shifts this account created that are still open —
     * not over yet and with no confirmed partnership (once a courier confirms, the shift
     * is no longer "open"; it reappears in the Histórico de turnos when it ends).
     *
     * @return \Illuminate\Support\Collection<int, Shift>
     */
    #[Computed]
    public function myShifts()
    {
        return Shift::where('creator_id', Auth::id())
            ->with('applications.user.profile')
            ->latest()
            ->get()
            ->reject(fn ($s) => $this->expired($s)
                || $s->hasConfirmedPartnership())
            ->values();
    }

    /** "Em andamento" tab, creator side: my shifts with a confirmed partnership that haven't happened yet. */
    #[Computed]
    public function inProgressPublished()
    {
        return Shift::where('creator_id', Auth::id())
            ->with('applications.user.profile')
            ->orderBy('date')->orderBy('start_time')
            ->get()
            ->reject(fn ($s) => $this->expired($s))
            ->filter(fn ($s) => $s->hasConfirmedPartnership())
            ->values();
    }

    /** "Em andamento" tab, courier side: shifts I confirmed that haven't happened yet. */
    #[Computed]
    public function inProgressWorked()
    {
        $id = Auth::id();

        return Shift::whereHas('applications', fn ($q) => $q->where('user_id', $id)->where('status', Application::STATUS_ACCEPTED)->where('confirmed', true))
            ->where('creator_id', '!=', $id)
            ->orderBy('date')->orderBy('start_time')
            ->get()
            ->reject(fn ($s) => $this->expired($s))
            ->values();
    }

    /**
     * "Vagas interessadas" tab: the courier's applications that still need something —
     * 'confirm' (accepted by the creator, waiting for the courier's confirmation, most
     * urgent first) and 'analysis' (interest sent, creator hasn't answered). Confirmed
     * partnerships and shifts already over are not listed here (the latter move to
     * the Histórico de turnos).
     *
     * @return array{confirm: \Illuminate\Support\Collection, analysis: \Illuminate\Support\Collection}
     */
    #[Computed]
    public function interestedSections(): array
    {
        $id = Auth::id();
        $apps = $this->myApplicationsByShift;

        $shifts = Shift::whereHas('applications', fn ($q) => $q->where('user_id', $id)->whereIn('status', [Application::STATUS_INTERESTED, Application::STATUS_ACCEPTED]))
            ->where('creator_id', '!=', $id)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->reject(fn ($s) => $this->expired($s) || (bool) ($apps[$s->id]->confirmed ?? false));

        [$confirm, $analysis] = $shifts->partition(fn ($s) => ($apps[$s->id]->status ?? null) === Application::STATUS_ACCEPTED);

        return ['confirm' => $confirm->values(), 'analysis' => $analysis->values()];
    }

    /** Both sections in display order (waiting-for-confirmation first). */
    #[Computed]
    public function interestedShifts()
    {
        return $this->interestedSections['confirm']->concat($this->interestedSections['analysis'])->values();
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
        $chats = Chat::whereIn('shift_id', $this->interestedShifts->pluck('id')->merge($this->inProgressWorked->pluck('id')))
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
        $shiftIds = $this->myShifts->pluck('id')->merge($this->inProgressPublished->pluck('id'));
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

    /**
     * "Histórico de turnos" tab: only shifts that are over. They end up "Concluída"
     * (a partnership was confirmed) or "Expirada" (the time passed without one) — the
     * row works out which; anything still upcoming lives under Vagas publicadas /
     * Vagas interessadas instead.
     */
    #[Computed]
    public function historyShifts()
    {
        $id = Auth::id();

        $shifts = $this->historyTab === 'published'
            ? Shift::where('creator_id', $id)->with('applications')->orderByDesc('date')->get()
            : Shift::whereHas('applications', fn ($q) => $q->where('user_id', $id)->where('status', Application::STATUS_ACCEPTED))
                ->where('creator_id', '!=', $id)
                ->orderByDesc('date')
                ->get();

        return $shifts->filter(fn ($s) => $this->expired($s))->values();
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
