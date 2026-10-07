<?php

namespace App\Livewire;

use App\Support\Catalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Reached when an "estabelecimento" account switches to motoboy but is
 * missing courier-only data (cidade base + veículo — see
 * Profile::missingCourierFields()). Not part of the first-time onboarding
 * gate: only ever entered explicitly from the profile-switch action.
 */
#[Layout('components.layouts.guest')]
#[Title('Completar cadastro de motoboy — ZunMoto')]
class SwitchToCourier extends Component
{
    public string $cep = '';

    public string $district = '';

    public string $city = '';

    public bool $cepBusy = false;

    public string $vehicle = '';

    public int $radiusKm = \App\Support\Radius::DEFAULT_KM;

    public function mount(): void
    {
        $profile = Auth::user()->profile;

        // Nothing missing (e.g. was a courier before): just flip back, no form needed.
        if ($profile && ! $profile->missingCourierFields()) {
            $profile->update(['role' => 'courier']);
            $this->redirect(route('shifts.index'), navigate: true);

            return;
        }

        $this->district = $profile?->district ?? '';
        $this->city = $profile?->city ?? '';
        $this->vehicle = $profile?->vehicle ?? '';
        $this->radiusKm = (int) ($profile?->radius_km ?: \App\Support\Radius::DEFAULT_KM);
    }

    /** Fills district / city from the CEP (ViaCEP), like the onboarding form. */
    public function lookupCep(): void
    {
        $digits = preg_replace('/\D/', '', $this->cep);
        if (strlen($digits) !== 8) {
            return;
        }

        $this->cepBusy = true;
        try {
            $data = Http::timeout(6)->get("https://viacep.com.br/ws/{$digits}/json/")->json();
            if (is_array($data) && empty($data['erro'])) {
                $this->district = $data['bairro'] ?: $this->district;
                $this->city = ($data['localidade'] ?? '')
                    ? trim(($data['localidade'] ?? '').(isset($data['uf']) ? ' - '.$data['uf'] : ''))
                    : $this->city;
            } else {
                $this->dispatch('toast', message: 'CEP não encontrado.');
            }
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: 'Falha ao consultar o CEP.');
        } finally {
            $this->cepBusy = false;
        }
    }

    public function setVehicle(string $vehicle): void
    {
        if ($vehicle === 'moto' && ! Auth::user()->profile?->isAdult()) {
            return;
        }
        if (in_array($vehicle, Catalog::VEHICLE_OPTIONS, true)) {
            $this->vehicle = $vehicle;
        }
    }

    public function finish()
    {
        $this->validate([
            'district' => ['required', 'min:2'],
            'city' => ['required', 'min:2'],
            'vehicle' => ['required', 'in:'.implode(',', Catalog::VEHICLE_OPTIONS)],
        ], [], ['vehicle' => 'veículo']);

        $profile = Auth::user()->profile;

        if ($this->vehicle === 'moto' && ! $profile->isAdult()) {
            $this->addError('vehicle', 'Você precisa ter 18 anos para escolher moto.');

            return null;
        }

        $profile->update([
            'role' => 'courier',
            'district' => trim($this->district),
            'city' => trim($this->city),
            'vehicle' => $this->vehicle,
            'radius_km' => \App\Support\Radius::clamp($this->radiusKm),
        ]);
        $profile->refreshBaseLocation($this->cep);

        $this->dispatch('toast', message: 'Perfil alterado para Motoboy.');

        return $this->redirect(route('shifts.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.switch-to-courier');
    }
}
