<?php

namespace App\Livewire\Shifts;

use App\Models\Application;
use App\Models\Chat;
use App\Models\Review;
use App\Models\Shift;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Detalhes da vaga — GiroMoto')]
class Show extends Component
{
    public string $shiftId;

    public bool $confirmOpen = false;

    public bool $reviewOpen = false;

    public int $rating = 0;

    public string $comment = '';

    public bool $confirmDeleteOpen = false;

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

    public function submitReview(): void
    {
        $shift = $this->shift();

        // Server-side gating (the UI gating is cosmetic): only the shift creator
        // reviews the reserved courier, only after the shift ended, and never self.
        if ($shift->creator_id !== Auth::id()) {
            return;
        }
        if (! $shift->reserved_by || $shift->reserved_by === Auth::id()) {
            return;
        }
        if (! $this->expired($shift) || $this->rating < 1) {
            return;
        }

        Review::updateOrCreate(
            ['shift_id' => $shift->id, 'author_id' => Auth::id(), 'target_id' => $shift->reserved_by],
            ['rating' => $this->rating, 'comment' => trim($this->comment)],
        );

        $this->reviewOpen = false;
        $this->rating = 0;
        $this->comment = '';
        $this->dispatch('toast', message: 'Avaliação enviada!');
    }

    public function openChat()
    {
        $shift = $this->shift();
        $userId = Auth::id();

        // Only a courier who was accepted on this shift may open the chat.
        $accepted = $shift->applications()
            ->where('user_id', $userId)
            ->where('status', Application::STATUS_ACCEPTED)
            ->exists();
        if (! $accepted) {
            return null;
        }

        $chat = Chat::findOrCreateBetween($shift->id, $shift->creator_id, $userId);

        return $this->redirect(route('chats.show', $chat->id), navigate: true);
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

        $shift->update(['active' => ! $shift->active]);
        unset($this->shift);
        $this->dispatch('toast', message: $shift->active ? 'Vaga reativada' : 'Vaga pausada');
    }

    public function deleteShift()
    {
        $shift = $this->shift();
        if ($shift->creator_id !== Auth::id()) {
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

        return ! $this->expired($shift) && $this->compatible($shift) && ! $this->blockedByBag($shift);
    }

    protected function expired(Shift $shift): bool
    {
        return Carbon::parse($shift->date->toDateString().' '.$shift->end_time)->isPast();
    }

    protected function acceptedVehicles(Shift $shift): array
    {
        return $shift->accepted_vehicles ?? [];
    }

    protected function noRestriction(Shift $shift): bool
    {
        $v = $this->acceptedVehicles($shift);

        return count($v) === 0 || count($v) === 3;
    }

    protected function compatible(Shift $shift): bool
    {
        if ($this->noRestriction($shift)) {
            return true;
        }
        $vehicle = Auth::user()?->profile?->vehicle;

        return $vehicle && in_array($vehicle, $this->acceptedVehicles($shift), true);
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

        // Mirrors the React detail screen: list shows only couriers still
        // "interested" (accepted ones move out of this list).
        $interested = $apps
            ->where('status', Application::STATUS_INTERESTED)
            ->map(fn ($a) => [
                'id' => $a->user_id,
                'profile' => $a->user?->profile,
                'accepted' => false,
            ])
            ->values();

        $needed = $shift->couriers_needed ?? 1;

        $vm = [
            'shift' => $shift,
            'isCreator' => $shift->creator_id === $userId,
            'alreadyInterested' => $interestedIds->contains($userId),
            'wasAccepted' => in_array($userId, $acceptedIds, true),
            'needed' => $needed,
            'totalAccepted' => count($acceptedIds),
            'totalConfirmed' => $apps->where('status', Application::STATUS_ACCEPTED)->where('confirmed', true)->count(),
            'full' => count($acceptedIds) >= $needed,
            'isCoverage' => $shift->creator_role === 'courier',
            'acceptedVehicles' => $this->acceptedVehicles($shift),
            'noRestriction' => $this->noRestriction($shift),
            'compatible' => $this->compatible($shift),
            'requiresBag' => (bool) $shift->requires_own_bag,
            'blockedByBag' => $this->blockedByBag($shift),
            'expired' => $this->expired($shift),
            'userVehicle' => $me->profile?->vehicle,
            'interested' => $interested,
            'contact' => in_array($userId, $acceptedIds, true) ? $shift->contact : null,
        ];

        return view('livewire.shifts.show', $vm);
    }
}
