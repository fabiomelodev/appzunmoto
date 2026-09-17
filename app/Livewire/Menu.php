<?php

namespace App\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Menu — ZunMoto')]
class Menu extends Component
{
    /**
     * $pushEndpoint: this browser's push subscription, unsubscribed
     * client-side right before this call (see menu.blade.php) — deleted here
     * too so the next account logged in on this device doesn't silently
     * inherit it (push subscriptions are per-browser, not per-account).
     */
    public function logout(?string $pushEndpoint = null)
    {
        if ($pushEndpoint) {
            Auth::user()?->deletePushSubscription($pushEndpoint);
        }

        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        return redirect()->route('login');
    }

    public function render()
    {
        return view('livewire.menu', [
            'profile' => Auth::user()->profile,
        ]);
    }
}
