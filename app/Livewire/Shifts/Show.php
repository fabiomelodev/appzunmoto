<?php

namespace App\Livewire\Shifts;

use App\Models\Application;
use App\Models\Chat;
use App\Models\Profile;
use App\Models\Shift;
use App\Support\Catalog;
use App\Support\Partnerships;
use App\Support\Reviews;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Detalhes da vaga — ZunMoto')]
class Show extends Component
{
    public string $shiftId;

    public bool $confirmOpen = false;

    public bool $reviewOpen = false;

    /** Who the open review dialog is about (a shift can have several couriers to review). */
    public ?string $reviewTargetId = null;

    public int $rating = 0;

    public string $comment = '';

    public bool $confirmDeleteOpen = false;

    /** Live refresh when something about this shift happens (e.g. the creator accepts the courier). */
    public function getListeners(): array
    {
        return ['echo-private:user.'.Auth::id().',.notification.received' => 'onNotification'];
    }

    /** A round-trip re-runs render(), which reloads the application state and stepper. */
    public function onNotification(): void {}

    public function mount(string $id): void
    {
        $this->shiftId = $id;
        Shift::findOrFail($id); // 404 if missing
    }

    #[Computed]
    public function shift(): Shift
    {
        return Shift::with(['creator.profile', 'contact', 'applications.user.profile'])
            ->findOrFail($this->shiftId);
    }

    // ── Actions ───────────────────────────────────────────────────
    public function registerInterest(): void
    {
        $shift = $this->shift();
        $userId = Auth::id();

        if (! $this->canRegister($shift, $userId)) {
            $this->confirmOpen = false;

            // Say why when it's a schedule clash (the page already shows it, this covers a stale tab).
            if ($shift->creator_id !== $userId && ! $shift->applications->firstWhere('user_id', $userId)
                && Partnerships::confirmedConflict($shift, $userId)) {
                $this->dispatch('toast', message: 'Você já tem uma parceria confirmada nesse horário.', type: 'error');
            }

            return;
        }

        Application::firstOrCreate(
            ['shift_id' => $shift->id, 'user_id' => $userId],
            ['status' => Application::STATUS_INTERESTED],
        );

        unset($this->shift);
        $this->confirmOpen = false;
        $this->dispatch('toast', message: 'Interesse enviado!');
    }

    /** Courier backs out of a shift they showed interest in, before being
     *  accepted — once accepted, this is out of reach (see $wasAccepted in
     *  the view), a partnership already in motion needs the creator involved. */
    public function withdrawInterest(): void
    {
        $shift = $this->shift();

        $application = Application::where('shift_id', $shift->id)
            ->where('user_id', Auth::id())
            ->where('status', Application::STATUS_INTERESTED)
            ->first();

        if (! $application) {
            return;
        }

        $application->delete();

        unset($this->shift);
        $this->dispatch('toast', message: 'Interesse removido.');
    }

    /** Atalho pra confirmar que tem bag própria direto da tela da vaga, sem ir em "Meu Perfil". */
    public function confirmHasBag(): void
    {
        Auth::user()->profile?->update(['has_bag' => true]);
        $this->dispatch('toast', message: 'Bag própria confirmada no seu perfil.');
    }

    public function openReview(string $targetId): void
    {
        $shift = $this->shift();

        if (! Reviews::canReview($shift, Auth::id(), $targetId) || Reviews::hasReviewed($shift, Auth::id(), $targetId)) {
            return;
        }

        $this->reset('rating', 'comment');
        $this->reviewTargetId = $targetId;
        $this->reviewOpen = true;
    }

    public function submitReview(): void
    {
        // Server-side gating lives in Reviews::submit() (the UI gating is cosmetic).
        if (! $this->reviewTargetId
            || ! Reviews::submit($this->shift(), Auth::id(), $this->reviewTargetId, $this->rating, $this->comment)) {
            return;
        }

        $this->reviewOpen = false;
        $this->reviewTargetId = null;
        $this->rating = 0;
        $this->comment = '';
        unset($this->shift);
        $this->dispatch('toast', message: 'Avaliação enviada!');
    }

    public function openChat()
    {
        $shift = $this->shift();
        $userId = Auth::id();

        // An accepted courier may open (or start) the chat. A courier who is still
        // only interested can just reply: the chat must already exist, i.e. the
        // creator wrote first — they can't start one themselves.
        $accepted = $shift->applications()
            ->where('user_id', $userId)
            ->where('status', Application::STATUS_ACCEPTED)
            ->exists();
        $chat = $accepted
            ? Chat::findOrCreateBetween($shift->id, $shift->creator_id, $userId)
            : ($shift->applications()->where('user_id', $userId)->exists()
                ? Chat::findBetween($shift->id, $shift->creator_id, $userId)
                : null);
        if (! $chat) {
            return null;
        }

        return $this->redirect(route('chats.show', $chat->id), navigate: true);
    }

    /**
     * The accepted courier's own half of "Confirmar Parceria", right from the shift page
     * (the creator already confirmed when accepting). Same rules the chat applies, but
     * enforced here on the server instead of only by a disabled button.
     */
    public function confirmPartnership(): void
    {
        $shift = $this->shift();
        $userId = Auth::id();
        $app = $shift->applications->firstWhere('user_id', $userId);

        if (! $app || $app->status !== Application::STATUS_ACCEPTED || $app->confirmed) {
            return;
        }
        if ($this->expired($shift)) {
            $this->dispatch('toast', message: 'Esse turno já passou.', type: 'error');

            return;
        }
        if (Partnerships::confirmedConflict($shift, $userId)) {
            $this->dispatch('toast', message: 'Você já tem uma parceria confirmada nesse horário.', type: 'error');

            return;
        }

        Partnerships::confirm($shift, $userId, $userId);

        unset($this->shift);
        $this->dispatch('toast', message: 'Parceria confirmada!');
    }

    public function setRating(int $value): void
    {
        $this->rating = max(0, min(5, $value));
    }

    public function toggleActive(): void
    {
        $shift = $this->shift();
        if ($shift->creator_id !== Auth::id()) {
            return;
        }

        // Ended shifts are history and confirmed ones are a deal already made: no pausing either.
        if ($reason = $shift->lockReason()) {
            $this->dispatch('toast', message: $reason === 'ended'
                ? 'Esta vaga já terminou e não pode mais ser alterada.'
                : 'Esta vaga tem parceria confirmada e não pode mais ser alterada.', type: 'error');

            return;
        }

        $shift->update(['active' => ! $shift->active]);
        Partnerships::notifyPauseChange($shift);
        unset($this->shift);
        $this->dispatch('toast', message: $shift->active ? 'Vaga reativada' : 'Vaga pausada');
    }

    public function deleteShift()
    {
        $shift = $this->shift();
        if ($shift->creator_id !== Auth::id()) {
            return null;
        }

        if ($reason = $shift->lockReason()) {
            $this->confirmDeleteOpen = false;
            $this->dispatch('toast', message: $reason === 'ended'
                ? 'Esta vaga já terminou e não pode mais ser excluída.'
                : 'Esta vaga tem parceria confirmada e não pode mais ser excluída.', type: 'error');

            return null;
        }

        $shift->delete();
        $this->dispatch('toast', message: 'Vaga excluída');

        return $this->redirect(route('shifts.index'), navigate: true);
    }

    // ── Helpers ───────────────────────────────────────────────────
    protected function canRegister(Shift $shift, ?string $userId): bool
    {
        if (! $userId || $shift->creator_id === $userId) {
            return false;
        }
        // Only the courier profile can apply — an account switched to
        // "estabelecimento" keeps its old vehicle/bag data, which would
        // otherwise still pass the checks below.
        if (Auth::user()?->profile?->isBusiness()) {
            return false;
        }
        if ($shift->status !== Shift::STATUS_AVAILABLE || ! $shift->active) {
            return false;
        }

        $apps = $shift->applications;
        $accepted = $apps->where('status', Application::STATUS_ACCEPTED)->count();
        if ($accepted >= ($shift->couriers_needed ?? 1)) {
            return false;
        }
        if ($apps->firstWhere('user_id', $userId)) {
            return false; // already applied
        }

        // Already committed (confirmed) to another shift that overlaps this one.
        if (Partnerships::confirmedConflict($shift, $userId)) {
            return false;
        }

        return ! $this->expired($shift) && $this->compatible($shift) && ! $this->blockedByBag($shift);
    }

    protected function expired(Shift $shift): bool
    {
        return $shift->hasEnded();
    }

    protected function acceptedVehicles(Shift $shift): array
    {
        return $shift->accepted_vehicles ?? [];
    }

    protected function noRestriction(Shift $shift): bool
    {
        return Catalog::noVehicleRestriction($this->acceptedVehicles($shift));
    }

    protected function compatible(Shift $shift): bool
    {
        return Catalog::vehicleCompatible($this->acceptedVehicles($shift), Auth::user()?->profile?->vehicle);
    }

    protected function blockedByBag(Shift $shift): bool
    {
        return $shift->requires_own_bag && ! (Auth::user()?->profile?->has_bag);
    }

    public function render()
    {
        $shift = $this->shift();
        $me = Auth::user();
        $userId = $me->id;

        $apps = $shift->applications;
        $interestedIds = $apps->whereIn('status', [Application::STATUS_INTERESTED, Application::STATUS_ACCEPTED])
            ->pluck('user_id');
        $acceptedIds = $apps->where('status', Application::STATUS_ACCEPTED)->pluck('user_id')->all();

        // Accepted couriers were interested too — they stay in the list, just
        // flagged with the "Aceito" badge instead of disappearing from it.
        $interested = $apps
            ->whereIn('status', [Application::STATUS_INTERESTED, Application::STATUS_ACCEPTED])
            ->map(fn ($a) => [
                'id' => $a->user_id,
                'profile' => $a->user?->profile,
                'accepted' => $a->status === Application::STATUS_ACCEPTED,
            ])
            ->values();

        $needed = $shift->couriers_needed ?? 1;

        // Courier-facing progress tracker for their own application, shown at
        // the top of the page. The creator always auto-confirms on acceptance
        // (see Partnerships::accept()), so acceptance and "waiting on this
        // courier's own confirmation" happen in the very same write — steps 3
        // and 4 both land as soon as status flips to accepted, only step 4
        // ("Sua confirmação") is the active one until this courier confirms.
        $myApp = $shift->creator_id === $userId ? null : $apps->firstWhere('user_id', $userId);
        $applicationStep = match (true) {
            ! $myApp => null,
            $myApp->status === Application::STATUS_ACCEPTED && $myApp->confirmed => 5,
            $myApp->status === Application::STATUS_ACCEPTED => 4,
            default => 2,
        };

        // Who the viewer can review here: the creator reviews every courier
        // with a confirmed partnership, and each of those couriers reviews the
        // creator — only once the shift is over.
        $reviewables = collect();
        if ($shift->hasEnded()) {
            $ids = Reviews::reviewableIds($shift, $userId);
            $names = Profile::publicColumns()->whereIn('id', $ids)->get()->keyBy('id');
            $reviewables = $ids->map(fn (string $id) => [
                'id' => $id,
                'name' => $names->get($id)?->name ?: 'Usuário',
                'reviewed' => Reviews::hasReviewed($shift, $userId, $id),
            ]);
        }

        $vm = [
            'shift' => $shift,
            'isCreator' => $shift->creator_id === $userId,
            'reviewables' => $reviewables,
            'reviewTargetName' => $reviewables->firstWhere('id', $this->reviewTargetId)['name'] ?? null,
            'alreadyInterested' => $interestedIds->contains($userId),
            'wasAccepted' => in_array($userId, $acceptedIds, true),
            'needed' => $needed,
            'totalAccepted' => count($acceptedIds),
            'totalConfirmed' => $apps->where('status', Application::STATUS_ACCEPTED)->where('confirmed', true)->count(),
            'full' => count($acceptedIds) >= $needed,
            'isBusinessProfile' => (bool) $me->profile?->isBusiness(),
            'isCoverage' => $shift->creator_role === 'courier',
            'acceptedVehicles' => $this->acceptedVehicles($shift),
            'noRestriction' => $this->noRestriction($shift),
            'compatible' => $this->compatible($shift),
            'requiresBag' => (bool) $shift->requires_own_bag,
            'blockedByBag' => $this->blockedByBag($shift),
            'expired' => $this->expired($shift),
            'lockReason' => $shift->creator_id === $userId ? $shift->lockReason() : null,
            'userVehicle' => $me->profile?->vehicle,
            'interested' => $interested,
            'contact' => in_array($userId, $acceptedIds, true) ? $shift->contact : null,
            'applicationStep' => $applicationStep,
            'awaitingMyConfirmation' => $applicationStep === 4,
            'confirmConflict' => $applicationStep === 4 ? Partnerships::confirmedConflict($shift, $userId) : null,
            'myConfirmed' => $applicationStep === 5,
            // Only matters before applying: once interested/accepted the stepper takes over.
            'registerConflict' => (! $myApp && $shift->creator_id !== $userId) ? Partnerships::confirmedConflict($shift, $userId) : null,
            'chatId' => $applicationStep ? Chat::findBetween($shift->id, $shift->creator_id, $userId)?->id : null,
            'applicationStepLabels' => [
                1 => 'Interesse enviado',
                2 => 'Em análise',
                3 => 'Interesse aceito',
                4 => 'Sua confirmação',
                5 => 'Concluído',
            ],
        ];

        return view('livewire.shifts.show', $vm);
    }
}
