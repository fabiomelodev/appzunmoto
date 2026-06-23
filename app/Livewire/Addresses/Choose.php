<?php

namespace App\Livewire\Addresses;

use App\Models\UserAddress;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.app')]
#[Title('Onde será o turno? — MotoReserva')]
class Choose extends Component
{
    /** 'business' | 'courier' — carried through the publish flow. */
    public string $as = 'business';

    /** 'list' | 'new' */
    public string $mode = 'list';

    public string $label = '';
    public string $cep = '';
    public string $street = '';
    public string $number = '';
    public string $district = '';
    public string $city = '';

    public bool $cepBusy = false;

    public function mount(): void
    {
        $this->as = request('as') === 'courier' ? 'courier' : 'business';

        if ($this->addresses->isEmpty()) {
            $this->mode = 'new';
        }
    }

    #[Computed]
    public function addresses()
    {
        return UserAddress::where('user_id', Auth::id())->latest()->get();
    }

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
                $this->street = $data['logradouro'] ?: $this->street;
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

    public function select(string $addressId)
    {
        $address = UserAddress::where('user_id', Auth::id())->find($addressId);
        if (! $address) {
            return null;
        }

        // Self-heal addresses saved before geocoding worked, so the published
        // shift inherits coordinates and shows up on the map.
        if (! app()->runningUnitTests() && (! $address->lat || ! $address->lng)) {
            $coords = \App\Support\Geocoder::forAddress(
                $address->street, $address->number, $address->district, $address->city, $address->postal_code,
            );
            if ($coords) {
                $address->update(['lat' => $coords['lat'], 'lng' => $coords['lng']]);
            }
        }

        return $this->redirect(route('shifts.create', ['as' => $this->as, 'address' => $address->id]), navigate: true);
    }

    public function saveNew()
    {
        $digits = preg_replace('/\D/', '', $this->cep);

        $this->validate([
            'label' => ['required', 'min:2'],
            'street' => ['required', 'min:2'],
            'number' => ['required'],
            'district' => ['required', 'min:2'],
            'city' => ['required', 'min:2'],
        ], [], [
            'label' => 'apelido',
            'street' => 'rua',
            'number' => 'número',
            'district' => 'bairro',
            'city' => 'cidade',
        ]);

        if (strlen($digits) !== 8) {
            $this->addError('cep', 'Informe um CEP válido com 8 dígitos.');

            return null;
        }

        $coords = app()->runningUnitTests()
            ? null
            : \App\Support\Geocoder::forAddress(trim($this->street), trim($this->number), trim($this->district), trim($this->city), $digits);

        $address = UserAddress::create([
            'user_id' => Auth::id(),
            'label' => trim($this->label),
            'postal_code' => $digits,
            'street' => trim($this->street),
            'number' => trim($this->number),
            'district' => trim($this->district),
            'city' => trim($this->city),
            'lat' => $coords['lat'] ?? null,
            'lng' => $coords['lng'] ?? null,
        ]);

        $this->dispatch('toast', message: 'Endereço salvo.');

        return $this->redirect(route('shifts.create', ['as' => $this->as, 'address' => $address->id]), navigate: true);
    }

    public function render()
    {
        return view('livewire.addresses.choose');
    }
}
