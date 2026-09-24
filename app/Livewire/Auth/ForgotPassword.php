<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Recuperar senha — ZunMoto')]
class ForgotPassword extends Component
{
    protected const MAX_ATTEMPTS = 5;

    protected const DECAY_SECONDS = 600;

    public string $email = '';

    public bool $sent = false;

    public function mount(): void
    {
        $this->email = (string) request('email', '');
    }

    public function submit(): void
    {
        $this->validate(['email' => ['required', 'email']], [], ['email' => 'e-mail']);

        $key = 'forgot-password:'.strtolower($this->email).'|'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->addError('email', 'Muitas tentativas. Aguarde alguns minutos e tente de novo.');

            return;
        }
        RateLimiter::hit($key, self::DECAY_SECONDS);

        // The outcome is deliberately not inspected: the screen answers the same
        // whether or not an account exists for this e-mail, so it can't be used
        // to find out who is registered (the broker also throttles per account).
        try {
            Password::sendResetLink(['email' => $this->email]);
        } catch (\Throwable $e) {
            // A mail transport failure must not reveal anything either.
            report($e);
        }

        $this->sent = true;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}
