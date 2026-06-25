<?php

namespace App\Livewire\Shifts;

use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Vagas — MotoReserva')]
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
            'vehicles' => array_values(array_intersect((array) ($draft['vehicles'] ?? []), \App\Support\Catalog::VEHICLE_OPTIONS)),
            'dailyMin' => (string) ($draft['dailyMin'] ?? ''),
            'feeMin' => (string) ($draft['feeMin'] ?? ''),
            'startTime' => (string) ($draft['startTime'] ?? ''),
            'benefits' => array_values(array_intersect((array) ($draft['benefits'] ?? []), \App\Support\Catalog::BENEFIT_OPTIONS)),
            'ownBag' => in_array($ownBag, ['any', 'yes', 'no'], true) ? $ownBag : 'any',
            'date' => (string) ($draft['date'] ?? ''),
            'onlyInterested' => (bool) ($draft['onlyInterested'] ?? false),
        ];
    }

    public function clearFilters(): void
    {
        $this->reset('filters');
    }

    public function setVehicle(string $vehicle): void
    {
        if (! in_array($vehicle, \App\Support\Catalog::VEHICLE_OPTIONS, true)) {
            return;
        }

        Auth::user()?->profile?->update(['vehicle' => $vehicle]);

        $this->dispatch('toast', message: 'Veículo atualizado: '.(\App\Support\Catalog::VEHICLE_LABEL[$vehicle] ?? $vehicle));
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
            + (! empty($f['onlyInterested']) ? 1 : 0);
    }

    /** Shift ids the current user has applied to (any status). */
    #[Computed]
    public function myInterestIds()
    {
        return \App\Models\Application::where('user_id', Auth::id())->pluck('shift_id');
    }

    /** Shift ids the current user was ACCEPTED on (their card shows the success banner). */
    #[Computed]
    public function myAcceptedIds()
    {
        return \App\Models\Application::where('user_id', Auth::id())
            ->where('status', \App\Models\Application::STATUS_ACCEPTED)
            ->pluck('shift_id');
    }

    #[Computed]
    public function shifts()
    {
        $f = $this->filters;
        $acceptedIds = $this->myAcceptedIds;

        $query = Shift::query()
            ->with('creator.profile')
            ->withCount(['applications as accepted_count' => fn ($q) => $q->where('status', 'accepted')])
            // Filled shifts leave the marketplace, but the accepted courier keeps
            // seeing the one they're committed to (until its date passes).
            ->where(fn ($q) => $q->where('status', '!=', Shift::STATUS_FILLED)
                ->orWhereIn('id', $acceptedIds->all()))
            // Paused shifts leave the marketplace, but the owner still sees their
            // own (with a "Pausada" badge) so they can manage/resume them.
            ->where(fn ($q) => $q->where('active', true)->orWhere('creator_id', Auth::id()))
            // Coarse prune of past dates (portable); exact end_time expiry is refined in PHP below.
            ->whereDate('date', '>=', now()->toDateString());

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

        $now = now();

        return $query
            ->orderByRaw("CASE WHEN status = '".Shift::STATUS_AVAILABLE."' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')
            ->get()
            ->filter(function ($s) use ($now, $acceptedIds) {
                // A full shift is hidden — except keep it visible to the courier
                // who was accepted on it, so they still see their confirmation.
                $hasRoom = $s->accepted_count < ($s->couriers_needed ?? 1);
                $mineAccepted = $acceptedIds->contains($s->id);
                $endsAt = \Illuminate\Support\Carbon::parse($s->date->toDateString().' '.$s->end_time);

                return ($hasRoom || $mineAccepted) && $endsAt->gte($now);
            })
            ->values();
    }

    public function render()
    {
        return view('livewire.shifts.index');
    }
}
