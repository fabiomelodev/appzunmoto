<?php

namespace App\Livewire;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Configurações — MotoReserva')]
class Settings extends Component
{
    public bool $notifyShifts = true;
    public bool $notifyChat = true;
    public bool $notifyEmail = false;

    public string $city = '';

    // Account dialogs
    public bool $emailOpen = false;
    public bool $passwordOpen = false;
    public string $newEmail = '';
    public string $newPassword = '';
    public string $passwordConfirmation = '';
    public string $currentPassword = '';

    public function mount(): void
    {
        $settings = $this->settings();
        $this->notifyShifts = (bool) $settings->notify_shifts;
        $this->notifyChat = (bool) $settings->notify_chat;
        $this->notifyEmail = (bool) $settings->notify_email;
        $this->city = Auth::user()->profile?->city ?? '';
    }

    protected function settings(): UserSetting
    {
        return UserSetting::firstOrCreate(['user_id' => Auth::id()]);
    }

    public function updated($property): void
    {
        if (in_array($property, ['notifyShifts', 'notifyChat', 'notifyEmail'], true)) {
            $this->settings()->update([
                'notify_shifts' => $this->notifyShifts,
                'notify_chat' => $this->notifyChat,
                'notify_email' => $this->notifyEmail,
            ]);
        }
    }

    public function persistTheme(string $theme): void
    {
        if (! in_array($theme, ['dark', 'light', 'urbano'], true)) {
            return;
        }
        $this->settings()->update(['theme' => $theme]);
    }

    public function saveCity(): void
    {
        Auth::user()->profile?->update(['city' => trim($this->city)]);
        $this->dispatch('toast', message: 'Cidade atualizada.');
    }

    public function updateEmail(): void
    {
        $this->validate(
            ['newEmail' => ['required', 'email', 'unique:users,email,'.Auth::id()]],
            [],
            ['newEmail' => 'novo e-mail'],
        );
        if (! $this->currentPasswordValid()) {
            return;
        }

        Auth::user()->update(['email' => $this->newEmail]);
        $this->emailOpen = false;
        $this->reset('newEmail', 'currentPassword');
        $this->dispatch('toast', message: 'E-mail atualizado com sucesso!');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'newPassword' => ['required', 'min:6', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required'],
        ], [
            'newPassword.same' => 'As senhas não coincidem.',
        ]);

        // Google-only accounts have no local password yet — let them define one
        // without re-authenticating; accounts that already have a password must
        // confirm it.
        $hasPassword = ! is_null(Auth::user()->password);
        if ($hasPassword && ! $this->currentPasswordValid()) {
            return;
        }

        Auth::user()->update(['password' => $this->newPassword]);
        $this->passwordOpen = false;
        $this->reset('newPassword', 'passwordConfirmation', 'currentPassword');
        $this->dispatch('toast', message: $hasPassword ? 'Senha alterada com sucesso!' : 'Senha definida com sucesso!');
    }

    /** Disconnect the Google account, keeping email/password as the way back in. */
    public function unlinkGoogle(): void
    {
        $user = Auth::user();
        if (! $user->google_id) {
            return;
        }

        // Never lock the user out: a Google-only account (no password) must set
        // a password before disconnecting Google.
        if (is_null($user->password)) {
            $this->passwordOpen = true;
            $this->dispatch('toast', message: 'Defina uma senha antes de desvincular o Google.', type: 'error');

            return;
        }

        $user->update(['google_id' => null]);
        $this->dispatch('toast', message: 'Conta Google desvinculada.');
    }

    /** Validates the re-authentication field against the user's current password. */
    protected function currentPasswordValid(): bool
    {
        $this->validate(['currentPassword' => ['required']], [], ['currentPassword' => 'senha atual']);

        if (! Hash::check($this->currentPassword, Auth::user()->password)) {
            $this->addError('currentPassword', 'Senha atual incorreta.');

            return false;
        }

        return true;
    }

    public function render()
    {
        $user = Auth::user();

        return view('livewire.settings', [
            'user' => $user,
            'profile' => $user->profile,
            'hasPassword' => ! is_null($user->password),
            'linkedGoogle' => ! is_null($user->google_id),
        ]);
    }
}
