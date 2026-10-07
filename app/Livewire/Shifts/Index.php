<?php

namespace App\Livewire\Shifts;

use App\Models\Application;
use App\Models\Banner;
use App\Models\Shift;
use App\Support\Catalog;
use App\Support\Radius;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Vagas — ZunMoto')]
class Index extends Component
{
    public string $q = '';

    public ?string $region = null;

    /** Applied filters (the draft lives client-side in Alpine until "Aplicar"). */
    public array $filters = [
        'vehicles' => [],
        'dailyMin' => '',
        'feeMin' => '',
        'startTime' => '',
        'benefits' => [],
        'ownBag' => 'any', // any | yes | no
        'date' => '',
        'onlyInterested' => false,
        'onlyMine' => false,
        'allRegions' => false, // true = ignore the courier's search radius
    ];

    public function getListeners(): array
    {
        return ['echo-private:user.'.Auth::id().',.notification.received' => 'onNotification'];
    }

    /** Refresh the unread badge live when a notification arrives. */
    public function onNotification(): void
    {
        unset($this->unreadCount);
    }

    public function setRegion(?string $region): void
    {
        $this->region = $region;
    }

    public function applyFilters(array $draft): void
    {
        $ownBag = $draft['ownBag'] ?? 'any';

        $this->filters = [
            'vehicles' => array_values(array_intersect((array) ($draft['vehicles'] ?? []), Catalog::VEHICLE_OPTIONS)),
            'dailyMin' => (string) ($draft['dailyMin'] ?? ''),
            'feeMin' => (string) ($draft['feeMin'] ?? ''),
            'startTime' => (string) ($draft['startTime'] ?? ''),
            'benefits' => array_values(array_intersect((array) ($draft['benefits'] ?? []), Catalog::benefitOptions())),
            'ownBag' => in_array($ownBag, ['any', 'yes', 'no'], true) ? $ownBag : 'any',
            'date' => (string) ($draft['date'] ?? ''),
            'onlyInterested' => (bool) ($draft['onlyInterested'] ?? false),
            'onlyMine' => (bool) ($draft['onlyMine'] ?? false),
            'allRegions' => (bool) ($draft['allRegions'] ?? false),
        ];
    }

    /** Header chip "Minhas vagas": same filter as the one in the sheet; they stay in sync. */
    public function toggleMine(): void
    {
        $this->filters['onlyMine'] = empty($this->filters['onlyMine']);
        if ($this->filters['onlyMine']) {
            $this->filters['onlyInterested'] = false;
        }

        // The sheet's draft lives client-side: hand it the new state.
        $this->dispatch('filters-synced', filters: $this->filters);
    }

    /** Banner button / sheet switch: show shifts from every region, or go back to the radius. */
    public function toggleAllRegions(): void
    {
        $this->filters['allRegions'] = empty($this->filters['allRegions']);
        $this->dispatch('filters-synced', filters: $this->filters);
    }

    /** The courier's profile when the search radius applies to them (courier with a located base), else null. */
    #[Computed]
    public function radiusProfile(): ?\App\Models\Profile
    {
        $profile = Auth::user()?->profile;
        $profile?->ensureBaseLocation();

        return Radius::applies($profile) ? $profile : null;
    }

    public function clearFilters(): void
    {
        $this->reset('filters');
    }

    public function setVehicle(string $vehicle): void
    {
        if (! in_array($vehicle, Catalog::VEHICLE_OPTIONS, true)) {
            return;
        }

        Auth::user()?->profile?->update(['vehicle' => $vehicle]);

        $this->dispatch('toast', message: 'Veículo atualizado: '.(Catalog::VEHICLE_LABEL[$vehicle] ?? $vehicle));
    }

    /** Switch the active profile (courier <-> business); the same account can be both, one at a time. */
    public function switchRole(string $role): void
    {
        if (! in_array($role, ['courier', 'business'], true)) {
            return;
        }

        $profile = Auth::user()?->profile;
        if (! $profile || $profile->role === $role) {
            return;
        }

        if ($role === 'business' && ! $profile->isAdult()) {
            $this->dispatch('toast', message: 'Você precisa ter 18 anos para virar Estabelecimento.', type: 'error');

            return;
        }

        if ($role === 'courier' && $profile->missingCourierFields()) {
            $this->redirect(route('switch-to-courier'), navigate: true);

            return;
        }

        $profile->update(['role' => $role]);
        $this->dispatch('toast', message: $role === 'business' ? 'Perfil alterado para Estabelecimento.' : 'Perfil alterado para Motoboy.');
    }

    /** Active carousel banners, in display order — falls back to the hero
     *  text on the page when this is empty (see shifts/index.blade.php). */
    #[Computed]
    public function banners()
    {
        return Banner::active()->orderBy('order')->get();
    }

    #[Computed]
    public function currentRole(): string
    {
        return Auth::user()?->profile?->role ?? 'courier';
    }

    #[Computed]
    public function regions(): array
    {
        return Shift::query()->distinct()->orderBy('region')->pluck('region')->all();
    }

    #[Computed]
    public function activeVehicle(): string
    {
        return Auth::user()?->profile?->vehicle ?? 'moto';
    }

    #[Computed]
    public function unreadCount(): int
    {
        return Auth::user()?->notifications()->where('read', false)->count() ?? 0;
    }

    #[Computed]
    public function activeFilterCount(): int
    {
        $f = $this->filters;

        return (count($f['vehicles']) > 0 ? 1 : 0)
            + ($f['dailyMin'] !== '' ? 1 : 0)
            + ($f['feeMin'] !== '' ? 1 : 0)
            + ($f['startTime'] !== '' ? 1 : 0)
            + (count($f['benefits']) > 0 ? 1 : 0)
            + ($f['ownBag'] !== 'any' ? 1 : 0)
            + ($f['date'] !== '' ? 1 : 0)
            + (! empty($f['onlyInterested']) ? 1 : 0)
            + (! empty($f['onlyMine']) ? 1 : 0)
            + (! empty($f['allRegions']) ? 1 : 0);
    }

    /** How many of my shifts are still open (the number on the "Minhas vagas" chip). */
    #[Computed]
    public function myOpenCount(): int
    {
        return Shift::where('creator_id', Auth::id())
            ->where('status', '!=', Shift::STATUS_FILLED)
            ->whereDate('date', '>=', now('America/Sao_Paulo')->subDay()->toDateString())
            ->whereDoesntHave('applications', fn ($q) => $q->where('status', Application::STATUS_ACCEPTED)->where('confirmed', true))
            ->get()
            ->filter(fn ($s) => ! $s->hasEnded())
            ->count();
    }

    /** Shift ids the current user has applied to (any status). */
    #[Computed]
    public function myInterestIds()
    {
        return Application::where('user_id', Auth::id())->pluck('shift_id');
    }

    /** Shift ids the current user was ACCEPTED on (their card shows the success banner). */
    #[Computed]
    public function myAcceptedIds()
    {
        return Application::where('user_id', Auth::id())
            ->where('status', Application::STATUS_ACCEPTED)
            ->pluck('shift_id');
    }

    #[Computed]
    public function shifts()
    {
        $f = $this->filters;
        $acceptedIds = $this->myAcceptedIds;

        $query = Shift::query()
            ->with('creator.profile')
            ->withCount([
                'applications as accepted_count' => fn ($q) => $q->where('status', 'accepted'),
                // For the owner's own cards: everyone who showed interest / those still awaiting an answer.
                'applications as candidates_count',
                'applications as pending_count' => fn ($q) => $q->where('status', Application::STATUS_INTERESTED),
            ])
            // Filled shifts leave the marketplace, but the accepted courier keeps
            // seeing the one they're committed to (until its date passes).
            ->where(fn ($q) => $q->where('status', '!=', Shift::STATUS_FILLED)
                ->orWhereIn('id', $acceptedIds->all()))
            // Paused shifts leave the marketplace, but the owner still sees their
            // own (with a "Pausada" badge) so they can manage/resume them.
            ->where(fn ($q) => $q->where('active', true)->orWhere('creator_id', Auth::id()))
            // Coarse prune of past dates (portable; yesterday's overnight shifts may still be running); exact expiry is refined in PHP below.
            ->whereDate('date', '>=', now('America/Sao_Paulo')->subDay()->toDateString());

        if ($term = trim($this->q)) {
            $like = '%'.$term.'%';
            $query->where(function ($w) use ($like) {
                $w->where('venue', 'like', $like)
                    ->orWhere('region', 'like', $like)
                    ->orWhere('address', 'like', $like);
            });
        }

        if ($this->region) {
            $query->where('region', $this->region);
        }

        if (! empty($f['vehicles'])) {
            $query->where(function ($w) use ($f) {
                foreach ($f['vehicles'] as $v) {
                    $w->orWhereJsonContains('accepted_vehicles', $v);
                }
            });
        }

        if ($f['dailyMin'] !== '') {
            $query->where('daily_rate', '>=', (float) $f['dailyMin']);
        }
        if ($f['feeMin'] !== '') {
            $query->where('delivery_fee_min', '>=', (float) $f['feeMin']);
        }
        if ($f['startTime'] !== '') {
            $query->where('start_time', '>=', $f['startTime']);
        }
        if ($f['date'] !== '') {
            $query->whereDate('date', $f['date']);
        }
        if (! empty($f['benefits'])) {
            foreach ($f['benefits'] as $b) {
                $query->whereJsonContains('benefits', $b);
            }
        }
        if ($f['ownBag'] === 'yes') {
            $query->where('requires_own_bag', true);
        } elseif ($f['ownBag'] === 'no') {
            $query->where('requires_own_bag', false);
        }
        if (! empty($f['onlyInterested'])) {
            $query->whereIn('id', $this->myInterestIds->all());
        }
        // "Published by me": only my still-open shifts — same notion of open as the
        // Parcerias tab (not filled, no confirmed courier yet; the listing already drops ended ones).
        if (! empty($f['onlyMine'])) {
            $query->where('creator_id', Auth::id())
                ->where('status', '!=', Shift::STATUS_FILLED)
                ->whereDoesntHave('applications', fn ($q) => $q->where('status', Application::STATUS_ACCEPTED)->where('confirmed', true));
        }

        $now = now();

        return $query
            // My own shifts first, then open ones before reserved, newest first.
            ->orderByRaw('CASE WHEN creator_id = ? THEN 0 ELSE 1 END', [Auth::id()])
            ->orderByRaw("CASE WHEN status = '".Shift::STATUS_AVAILABLE."' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get()
            ->filter(fn ($s) => $this->insideRadius($s))
            ->filter(function ($s) use ($now, $acceptedIds) {
                // A full shift is hidden — except keep it visible to the courier
                // who was accepted on it, so they still see their confirmation.
                $hasRoom = $s->accepted_count < ($s->couriers_needed ?? 1);
                $mineAccepted = $acceptedIds->contains($s->id);

                return ($hasRoom || $mineAccepted) && $s->endsAt()->gte($now);
            })
            ->values();
    }

    /**
     * "Vagas perto de mim": outside the courier's radius a shift is hidden — unless the filter
     * is off ("Ver todas as regiões"), it's the courier's own, or they already applied to it.
     */
    protected function insideRadius(Shift $s): bool
    {
        $profile = $this->radiusProfile;
        if (! $profile || ! empty($this->filters['allRegions']) || $s->creator_id === Auth::id()) {
            return true;
        }

        return $this->myInterestIds->contains($s->id) || Radius::includes($profile, $s);
    }

    public function render()
    {
        return view('livewire.shifts.index');
    }
}
