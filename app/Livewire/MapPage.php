<?php

namespace App\Livewire;

use App\Models\Shift;
use Illuminate\Support\Carbon;
use App\Support\Radius;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Mapa — ZunMoto')]
class MapPage extends Component
{
    #[Computed]
    public function shifts(): array
    {
        $profile = \Illuminate\Support\Facades\Auth::user()?->profile;
        $profile = Radius::applies($profile) ? $profile : null;

        return Shift::where('status', Shift::STATUS_AVAILABLE)
            ->where('active', true)
            ->whereDate('date', '>=', now('America/Sao_Paulo')->subDay()->toDateString())
            ->with('creator.profile')
            ->get()
            ->filter(function ($s) use ($profile) {
                // Couriers only see what's inside their search radius (see App\Support\Radius).
                if ($profile && ! Radius::includes($profile, $s)) {
                    return false;
                }

                return (float) $s->lat !== 0.0
                    && (float) $s->lng !== 0.0
                    && ! $s->hasEnded();
            })
            ->map(fn ($s) => [
                'id' => $s->id,
                'venue' => $s->venue,
                'region' => $s->region,
                'rate' => $s->daily_rate + 0,
                'start' => $s->start_time,
                'end' => $s->end_time,
                'lat' => (float) $s->lat,
                'lng' => (float) $s->lng,
                'url' => route('shifts.show', $s->id),
            ])
            ->values()
            ->all();
    }

    public function render()
    {
        return view('livewire.map');
    }
}
