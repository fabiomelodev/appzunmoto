<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Entrar — ZunMoto')]
class Login extends Component
{
    /** 'signin' | 'signup' */
    public string $mode = 'signin';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    // signup-only
    public string $name = '';

    public string $testName = 'Usuário';

    /** Inline info/success message (not a validation error). */
    public ?string $notice = null;

    public function mount(): void
    {
        $this->email = (string) request('email', '');

        // Surface a message flashed by the Google OAuth callback (e.g. on error).
        if (session()->has('notice')) {
            $this->notice = (string) session('notice');
        }
    }

    public function setMode(string $mode): void
    {
        $this->mode = $mode === 'signup' ? 'signup' : 'signin';
        $this->notice = null;
        $this->resetErrorBag();
        $this->reset('password', 'passwordConfirmation', 'name');
    }

    public function submit()
    {
        $this->notice = null;

        return $this->mode === 'signup' ? $this->register() : $this->signIn();
    }

    protected function signIn()
    {
        $this->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'min:6'],
        ]);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password])) {
            $this->addError('email', 'E-mail ou senha incorretos.');

            return null;
        }

        session()->regenerate();

        return $this->redirect(route('shifts.index'), navigate: true);
    }

    protected function register()
    {
        $this->validate([
            'name' => ['required', 'min:2'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'min:6'],
        ], [
            'password.same' => 'As senhas não coincidem.',
        ]);

        // UserObserver provisions the base profile + settings. Role, address,
        // birth date, etc. are collected right after the first login (onboarding),
        // since they depend on whether the account will be a motoboy or a
        // restaurante and shouldn't be forced on either at this point.
        $user = User::create([
            'name' => trim($this->name),
            'email' => $this->email,
            'password' => $this->password,
        ]);
        $user->profile()->update(['onboarded_at' => null]);
        \App\Support\Acquisition::attach($user);

        // Mirror the original app: do not auto-login; switch to sign-in.
        $this->mode = 'signin';
        $this->reset('password', 'passwordConfirmation', 'name');
        $this->notice = 'Cadastro realizado com sucesso! Faça login para entrar.';

        return null;
    }

    public function testLogin()
    {
        abort_if(app()->environment('production'), 404);

        $name = trim($this->testName) ?: 'Usuário';

        $user = User::create([
            'name' => $name,
            'email' => 'guest_'.Str::lower(Str::random(16)).'@zunmoto.test',
            'password' => Str::random(40),
        ]);

        Auth::login($user);
        session()->regenerate();

        return $this->redirect(route('shifts.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
