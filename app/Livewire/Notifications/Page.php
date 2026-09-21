<?php

namespace App\Livewire\Notifications;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Notificações — ZunMoto')]
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

        $url = $notification->resolveUrl();

        return $url ? $this->redirect($url, navigate: true) : null;
    }

    public function render()
    {
        return view('livewire.notifications.index');
    }
}
