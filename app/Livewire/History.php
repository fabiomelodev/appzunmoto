<?php

namespace App\Livewire;

use App\Models\Application;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Histórico — GiroMoto')]
class History extends Component
{
    /** 'published' (Publiquei) | 'worked' (Trabalhei) */
    public string $tab = 'published';

    public function setTab(string $tab): void
    {
        $this->tab = $tab === 'worked' ? 'worked' : 'published';
    }

    public function render()
    {
        $id = Auth::id();

        $shifts = $this->tab === 'published'
            ? Shift::where('creator_id', $id)->orderByDesc('date')->get()
            : Shift::whereHas('applications', fn ($q) => $q->where('user_id', $id)->where('status', 'accepted'))
                ->where('creator_id', '!=', $id)
                ->orderByDesc('date')
                ->get();

        $myApplications = Application::where('user_id', $id)->get()->keyBy('shift_id');

        return view('livewire.history', compact('shifts', 'myApplications'));
    }
}
