<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Notificações — GiroMoto')]
class Page extends Component
{
    public function getListeners(): array
    {
        return ['echo-private:user.'.Auth::id().',.notification.received' => 'onNotification'];
    }

    public function onNotification(): void
    {
        unset($this->notifications);
    }

    #[Computed]
    public function notifications()
    {
        return Auth::user()->notifications()->latest('created_at')->get();
    }

    public function markAllRead(): void
    {
        Auth::user()->notifications()->where('read', false)->update(['read' => true]);
        unset($this->notifications);
    }

    public function open(string $id)
    {
        $notification = Auth::user()->notifications()->find($id);
        if (! $notification) {
            return null;
        }

        $notification->update(['read' => true]);

        $payload = $notification->payload ?? [];
        $type = $notification->type;

        if ($type === 'mensagem' && ! empty($payload['chat_id'])) {
            return $this->redirect(route('chats.show', $payload['chat_id']), navigate: true);
        }
        if ($type === 'vaga') {
            if (! empty($payload['shift_id'])) {
                return $this->redirect(route('chats.index', ['tab' => 'candidaturas', 'vagaId' => $payload['shift_id']]), navigate: true);
            }

            return $this->redirect(route('shifts.index'), navigate: true);
        }
        if ($type === 'turno' && ! empty($payload['shift_id'])) {
            return $this->redirect(route('shifts.show', $payload['shift_id']), navigate: true);
        }
        if ($type === 'documento') {
            return $this->redirect(route('documents'), navigate: true);
        }
        if (! empty($payload['shift_id'])) {
            return $this->redirect(route('shifts.show', $payload['shift_id']), navigate: true);
        }

        return null;
    }

    public function render()
    {
        return view('livewire.notifications.index');
    }
}
