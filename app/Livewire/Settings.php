<?php

namespace App\Livewire;

use App\Models\UserSetting;
use Illuminate\Support\Facades\Auth;
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

        Auth::user()->update(['email' => $this->newEmail]);
        $this->emailOpen = false;
        $this->newEmail = '';
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

        Auth::user()->update(['password' => $this->newPassword]);
        $this->passwordOpen = false;
        $this->reset('newPassword', 'passwordConfirmation');
        $this->dispatch('toast', message: 'Senha alterada com sucesso!');
    }

    public function render()
    {
        return view('livewire.settings', [
            'user' => Auth::user(),
            'profile' => Auth::user()->profile,
        ]);
    }
}
