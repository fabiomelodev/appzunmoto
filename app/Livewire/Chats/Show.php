<?php
// app/Livewire/Chats/Show.php

namespace App\Livewire\Chats;

use App\Models\Chat;
use App\Models\Message;
use App\Models\Profile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;

class Show extends Component
{
    public string $chatId;
    public string $texto = '';

    public function mount(string $id): void
    {
        $this->chatId = $id;

        // Garante que o usuário é participante
        $chat = Chat::findOrFail($id);
        abort_unless(
            in_array(Auth::id(), [$chat->user_a, $chat->user_b]),
            403
        );
    }

    public function enviar(): void
    {
        $this->validate(['texto' => 'required|max:1000']);

        Message::create([
            'chat_id'  => $this->chatId,
            'autor_id' => Auth::id(),
            'texto'    => trim($this->texto),
        ]);

        $this->texto = '';
    }

    public function render()
    {
        $userId   = Auth::id();
        $chat     = Chat::with(['vaga'])->findOrFail($this->chatId);
        $mensagens = Message::where('chat_id', $this->chatId)
            ->orderBy('created_at')
            ->get();

        $outroId  = $chat->outroParticipante($userId);
        $outro    = Profile::find($outroId);

        return view('livewire.chats.show', compact('chat','mensagens','outro','userId'))
            ->layout('layouts.app')
            ->title('Conversa — MotoReserva');
    }
}
