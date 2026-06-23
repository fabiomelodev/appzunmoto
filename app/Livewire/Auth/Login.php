<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.guest')]
#[Title('Entrar — MotoReserva')]
class Login extends Component
{
    /** 'signin' | 'signup' */
    public string $mode = 'signin';

    public string $email = '';
    public string $password = '';
    public string $passwordConfirmation = '';

    // signup-only
    public string $name = '';
    public string $cpf = '';
    public string $birthDate = '';
    public string $phone = '';
    public string $street = '';
    public string $number = '';
    public string $district = '';
    public string $city = '';

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
        $this->reset('password', 'passwordConfirmation', 'name', 'cpf', 'birthDate', 'phone', 'street', 'number', 'district', 'city');
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
            'cpf' => ['required'],
            'birthDate' => ['required'],
            'phone' => ['required'],
            'street' => ['required', 'min:2'],
            'number' => ['required'],
            'district' => ['required', 'min:2'],
            'city' => ['required', 'min:2'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'min:6', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'min:6'],
        ], [
            'password.same' => 'As senhas não coincidem.',
        ]);

        $cpfDigits = preg_replace('/\D/', '', $this->cpf);
        $phoneDigits = preg_replace('/\D/', '', $this->phone);
        $birth = $this->parseBrDate($this->birthDate);

        if (strlen($cpfDigits) !== 11) {
            $this->addError('cpf', 'CPF inválido.');

            return null;
        }
        if (strlen($phoneDigits) < 10) {
            $this->addError('phone', 'Telefone inválido.');

            return null;
        }
        if (! $birth) {
            $this->addError('birthDate', 'Data de nascimento inválida (use DD/MM/AAAA).');

            return null;
        }

        DB::transaction(function () use ($cpfDigits, $phoneDigits, $birth) {
            $user = User::create([
                'name' => trim($this->name),
                'email' => $this->email,
                'password' => $this->password,
            ]);

            // UserObserver already created a base profile + settings.
            $user->profile()->update([
                'role' => 'courier',
                'name' => trim($this->name),
                'cpf' => $cpfDigits,
                'birth_date' => $birth,
                'phone' => $phoneDigits,
                'street' => trim($this->street),
                'street_number' => trim($this->number),
                'district' => trim($this->district),
                'city' => trim($this->city),
            ]);
        });

        // Mirror the original app: do not auto-login; switch to sign-in.
        $this->mode = 'signin';
        $this->reset('password', 'passwordConfirmation', 'name', 'cpf', 'birthDate', 'phone', 'street', 'number', 'district', 'city');
        $this->notice = 'Cadastro realizado com sucesso! Faça login para entrar.';

        return null;
    }

    public function testLogin()
    {
        $name = trim($this->testName) ?: 'Usuário';

        $user = User::create([
            'name' => $name,
            'email' => 'guest_'.Str::lower(Str::random(16)).'@motoreserva.test',
            'password' => Str::random(40),
        ]);

        Auth::login($user);
        session()->regenerate();

        return $this->redirect(route('shifts.index'), navigate: true);
    }

    protected function parseBrDate(string $value): ?string
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', trim($value), $m)) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
