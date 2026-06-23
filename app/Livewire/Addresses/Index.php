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
#[Title('Meus Endereços — MotoReserva')]
class Index extends Component
{
    public bool $open = false;

    public ?string $editingId = null;

    public string $label = '';
    public string $cep = '';
    public string $street = '';
    public string $number = '';
    public string $district = '';
    public string $city = '';
    public string $reference = '';

    public bool $cepBusy = false;

    #[Computed]
    public function addresses()
    {
        return UserAddress::where('user_id', Auth::id())->latest()->get();
    }

    public function openNew(): void
    {
        $this->reset('editingId', 'label', 'cep', 'street', 'number', 'district', 'city', 'reference');
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
        $this->resetErrorBag();
        $this->open = true;
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

        if ($this->editingId) {
            UserAddress::where('user_id', Auth::id())->where('id', $this->editingId)->update($data);
            $this->dispatch('toast', message: 'Endereço atualizado.');
        } else {
            UserAddress::create($data + ['user_id' => Auth::id()]);
            $this->dispatch('toast', message: 'Endereço adicionado.');
        }

        $this->open = false;
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
