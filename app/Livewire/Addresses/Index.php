<?php

namespace App\Livewire\Addresses;

use App\Models\UserAddress;
use App\Support\Geocoder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.app')]
#[Title('Meus Endereços — ZunMoto')]
class Index extends Component
{
    use WithFileUploads;

    public bool $open = false;

    public ?string $editingId = null;

    public string $label = '';

    public string $cep = '';

    public string $street = '';

    public string $number = '';

    public string $district = '';

    public string $city = '';

    public string $reference = '';

    // Optional location photo.
    public $photo = null;

    public ?string $existingPhotoUrl = null;

    public bool $removePhoto = false;

    public bool $cepBusy = false;

    #[Computed]
    public function addresses()
    {
        return UserAddress::where('user_id', Auth::id())->latest()->get();
    }

    public function openNew(): void
    {
        $this->reset('editingId', 'label', 'cep', 'street', 'number', 'district', 'city', 'reference', 'photo', 'existingPhotoUrl', 'removePhoto');
        $this->resetErrorBag();
        $this->open = true;
    }

    public function openEdit(string $id): void
    {
        $a = UserAddress::where('user_id', Auth::id())->find($id);
        if (! $a) {
            return;
        }
        $this->editingId = $a->id;
        $this->label = $a->label;
        $this->cep = $a->postal_code ?? '';
        $this->street = $a->street;
        $this->number = $a->number;
        $this->district = $a->district;
        $this->city = $a->city;
        $this->reference = $a->reference ?? '';
        $this->reset('photo', 'removePhoto');
        $this->existingPhotoUrl = $a->photo_url;
        $this->resetErrorBag();
        $this->open = true;
    }

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'max:4096']]);
        $this->removePhoto = false;
    }

    public function clearPhoto(): void
    {
        $this->photo = null;
        $this->removePhoto = true;
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

    public function save(): void
    {
        $this->validate([
            'label' => ['required', 'min:2'],
            'street' => ['required', 'min:2'],
            'number' => ['required'],
        ], [], [
            'label' => 'apelido',
            'street' => 'rua',
            'number' => 'número',
        ]);

        $data = [
            'label' => trim($this->label),
            'postal_code' => preg_replace('/\D/', '', $this->cep) ?: null,
            'street' => trim($this->street),
            'number' => trim($this->number),
            'district' => trim($this->district),
            'city' => trim($this->city),
            'reference' => trim($this->reference) ?: null,
        ];

        $coords = app()->runningUnitTests()
            ? null
            : Geocoder::forAddress($data['street'], $data['number'], $data['district'], $data['city'], $data['postal_code']);
        if ($coords) {
            $data['lat'] = $coords['lat'];
            $data['lng'] = $coords['lng'];
        }

        if ($this->editingId) {
            $address = UserAddress::where('user_id', Auth::id())->find($this->editingId);
            if (! $address) {
                return;
            }
            $address->update($data);
            $msg = 'Endereço atualizado.';
        } else {
            $address = UserAddress::create($data + ['user_id' => Auth::id()]);
            $msg = 'Endereço adicionado.';
        }

        $this->persistPhoto($address);

        $this->dispatch('toast', message: $msg);
        $this->open = false;
    }

    /** Stores a newly uploaded location photo, or clears it when removed. */
    protected function persistPhoto(UserAddress $address): void
    {
        if ($this->photo) {
            $path = $this->photo->storePubliclyAs(
                'address-photos',
                $address->id.'.'.$this->photo->getClientOriginalExtension(),
                'public',
            );
            $address->update(['photo_url' => Storage::disk('public')->url($path)]);
        } elseif ($this->removePhoto) {
            $address->update(['photo_url' => null]);
        }
    }

    public function delete(string $id): void
    {
        UserAddress::where('user_id', Auth::id())->where('id', $id)->delete();
        $this->dispatch('toast', message: 'Endereço removido.');
    }

    public function render()
    {
        return view('livewire.addresses.index');
    }
}
