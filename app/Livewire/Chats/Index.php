<?php
// app/Livewire/Chats/Index.php

namespace App\Livewire\Chats;

use App\Models\Chat;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Index extends Component
{
    public function render()
    {
        $userId = Auth::id();

        $chats = Chat::where('user_a', $userId)
            ->orWhere('user_b', $userId)
            ->with(['vaga','mensagens' => fn($q) => $q->latest()->limit(1)])
            ->latest()
            ->get();

        // Carrega perfis dos outros participantes
        $outrosIds = $chats->map(fn($c) => $c->outroParticipante($userId))->unique()->values();
        $profiles  = Profile::whereIn('id', $outrosIds)->get()->keyBy('id');

        return view('livewire.chats.index', compact('chats','profiles','userId'))
            ->layout('layouts.app')
            ->title('Chats — MotoReserva');
    }
}
