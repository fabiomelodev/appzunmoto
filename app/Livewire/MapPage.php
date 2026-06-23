<?php

namespace App\Livewire;

use App\Models\Shift;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Mapa — MotoReserva')]
class MapPage extends Component
{
    #[Computed]
    public function shifts(): array
    {
        return Shift::where('status', Shift::STATUS_AVAILABLE)
            ->where('active', true)
            ->whereDate('date', '>=', now()->toDateString())
            ->with('creator.profile')
            ->get()
            ->filter(function ($s) {
                return (float) $s->lat !== 0.0
                    && (float) $s->lng !== 0.0
                    && Carbon::parse($s->date->toDateString().' '.$s->end_time)->isFuture();
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
