<?php

namespace App\Livewire\Auth;

use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Nova senha — ZunMoto')]
class ResetPassword extends Component
{
    protected const MAX_ATTEMPTS = 10;

    protected const DECAY_SECONDS = 600;

    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = (string) request('email', '');
    }

    public function submit()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required'],
        ], [
            'password.same' => 'As senhas não coincidem.',
        ], ['email' => 'e-mail', 'password' => 'senha']);

        $key = 'reset-password:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->addError('password', 'Muitas tentativas. Aguarde alguns minutos e tente de novo.');

            return null;
        }
        RateLimiter::hit($key, self::DECAY_SECONDS);

        $status = Password::reset(
            ['email' => $this->email, 'password' => $this->password, 'token' => $this->token],
            function ($user, string $password) {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                // Whoever knew the old password (or had the account open) must not
                // stay signed in on other devices after a recovery.
                DB::table('sessions')->where('user_id', $user->id)->delete();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            // Unknown e-mail, wrong/expired/used token: one message for all of them.
            $this->addError('email', 'Este link é inválido ou expirou. Peça um novo link de recuperação.');

            return null;
        }

        session()->flash('notice', 'Senha redefinida com sucesso! Entre com a nova senha.');

        return $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
