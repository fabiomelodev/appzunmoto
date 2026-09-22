<?php

namespace App\Livewire;

use App\Models\Profile;
use App\Models\UserAddress;
use App\Support\Catalog;
use App\Support\Cpf;
use App\Support\Geocoder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Layout('components.layouts.guest')]
#[Title('Complete seu cadastro — ZunMoto')]
class Onboarding extends Component
{
    use WithFileUploads;

    /** 1 = Dados pessoais; 2 = Perfil + Endereço; 3 = Veículo (só motoboy). */
    public int $step = 1;

    public string $name = '';

    public string $phone = '';

    public string $birthDate = '';

    public string $cpf = '';

    /** 'courier' (motoboy) | 'business' (estabelecimento) — escolhido no passo 2. */
    public string $role = 'courier';

    // Courier-only: doubles as the "cidade base" used in Configurações to filter nearby shifts.
    public string $cep = '';

    public string $district = '';

    public string $city = '';

    public bool $cepBusy = false;

    public string $vehicle = '';

    // Business-only: creates the account's first real establishment (UserAddress).
    public string $label = '';

    public string $street = '';

    public string $number = '';

    public string $reference = '';

    public $photo;

    public function mount(): void
    {
        // Already onboarded (e.g. reached this URL directly): nothing to do here.
        if (Auth::user()->profile?->isOnboarded()) {
            $this->redirect(route('shifts.index'), navigate: true);

            return;
        }

        $profile = Auth::user()->profile;
        $this->name = $profile?->name ?: Auth::user()->name;
        $this->phone = $profile?->phone ?? '';
        $this->cpf = $profile?->cpf ?? '';
    }

    public function setRole(string $role): void
    {
        if ($role === 'business' && ! $this->isAdult) {
            return;
        }

        $newRole = $role === 'business' ? 'business' : 'courier';
        if ($newRole !== $this->role) {
            // Os campos de endereço têm significado diferente em cada perfil — não deixa
            // o que foi digitado num contexto vazar pro outro ao trocar de perfil.
            $this->reset('cep', 'district', 'city', 'label', 'street', 'number', 'reference', 'photo');
        }
        $this->role = $newRole;
    }

    /** Fills street / district / city from the CEP (ViaCEP), like the address forms. */
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

    public function updatedPhoto(): void
    {
        $this->validate(['photo' => ['image', 'max:4096']]);
    }

    public function clearPhoto(): void
    {
        $this->photo = null;
    }

    /** Volta uma etapa, preservando o que já foi preenchido. */
    public function previousStep(): void
    {
        $this->step = max(1, $this->step - 1);
    }

    /** Step 1: nome, telefone e data de nascimento. O piso absoluto (16 anos) já é aplicado aqui. */
    public function submitPersonalData()
    {
        $this->validate([
            'name' => ['required', 'min:2'],
            'phone' => ['required'],
            'birthDate' => ['required'],
            'cpf' => ['required'],
        ], [], ['cpf' => 'CPF']);

        $phoneDigits = preg_replace('/\D/', '', $this->phone);
        if (strlen($phoneDigits) < 10) {
            $this->addError('phone', 'Telefone inválido.');

            return null;
        }

        $birth = $this->parseBrDate($this->birthDate);
        if (! $birth) {
            $this->addError('birthDate', 'Data de nascimento inválida (use DD/MM/AAAA).');

            return null;
        }

        if (Carbon::parse($birth)->isAfter(now()->subYears(16))) {
            $this->addError('birthDate', 'Você precisa ter pelo menos 16 anos para usar o ZunMoto.');

            return null;
        }

        $cpfDigits = preg_replace('/\D/', '', $this->cpf);
        if (! Cpf::isValid($cpfDigits)) {
            $this->addError('cpf', 'CPF inválido.');

            return null;
        }

        if (Profile::where('cpf', $cpfDigits)->where('id', '!=', Auth::id())->exists()) {
            $this->addError('cpf', 'Esse CPF já está cadastrado em outra conta.');

            return null;
        }

        $this->step = 2;

        return null;
    }

    /** Step 2: perfil (Estabelecimento exige 18+) + campos específicos dele. Estabelecimento finaliza aqui; motoboy segue pro veículo. */
    public function submitAddress()
    {
        $rules = [];
        if ($this->role === 'courier') {
            $rules['district'] = ['required', 'min:2'];
            $rules['city'] = ['required', 'min:2'];
        } else {
            $rules['label'] = ['required', 'min:2'];
            $rules['street'] = ['required', 'min:2'];
            $rules['number'] = ['required'];
            $rules['photo'] = ['nullable', 'image', 'max:4096'];
        }

        $this->validate($rules, [], ['label' => 'apelido do local']);

        // Defesa extra: motoboy já teve os 16 anos confirmados no passo 1; estabelecimento
        // reconfirma os 18, já que é a idade que libera essa opção no seletor.
        $birth = $this->parseBrDate($this->birthDate);
        $minAge = $this->role === 'business' ? 18 : 16;
        if (! $birth || Carbon::parse($birth)->isAfter(now()->subYears($minAge))) {
            $this->dispatch('toast', message: 'Não foi possível continuar. Recarregue a página e tente novamente.', type: 'error');

            return null;
        }

        $phoneDigits = preg_replace('/\D/', '', $this->phone);
        $cpfDigits = preg_replace('/\D/', '', $this->cpf);

        $user = Auth::user();

        try {
            $user->profile()->update([
                'role' => $this->role,
                'name' => trim($this->name),
                'phone' => $phoneDigits,
                'cpf' => $cpfDigits,
                'district' => $this->role === 'courier' ? trim($this->district) : null,
                'city' => $this->role === 'courier' ? trim($this->city) : null,
                'birth_date' => $birth,
            ]);
        } catch (\Illuminate\Database\QueryException $e) {
            // CPF is unique — covers the rare race where it got taken between
            // step 1's check and this save (step 1 only validates, doesn't
            // persist; see submitPersonalData()).
            if ($e->getCode() === '23000') {
                $this->step = 1;
                $this->addError('cpf', 'Esse CPF já está cadastrado em outra conta.');

                return null;
            }

            throw $e;
        }

        $user->update(['name' => trim($this->name)]);

        // Estabelecimento não escolhe veículo: cadastro termina aqui, já com o primeiro endereço.
        if ($this->role === 'business') {
            $this->createFirstAddress();
            $user->profile()->update(['onboarded_at' => now()]);

            return $this->redirect(route('shifts.index'), navigate: true);
        }

        $this->step = 3;

        return null;
    }

    /** Creates the account's first establishment, same shape as "Meus Endereços". */
    protected function createFirstAddress(): void
    {
        $data = [
            'user_id' => Auth::id(),
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

        $address = UserAddress::create($data);

        if ($this->photo) {
            $path = $this->photo->storePubliclyAs(
                'address-photos',
                $address->id.'.'.$this->photo->getClientOriginalExtension(),
                'public',
            );
            $address->update(['photo_url' => Storage::disk('public')->url($path)]);
        }
    }

    public function setVehicle(string $vehicle): void
    {
        if ($vehicle === 'moto' && ! $this->isAdult) {
            return;
        }
        if (in_array($vehicle, Catalog::VEHICLE_OPTIONS, true)) {
            $this->vehicle = $vehicle;
        }
    }

    /** Step 3 (motoboy only): escolher veículo e concluir o cadastro. */
    public function finish()
    {
        $this->validate([
            'vehicle' => ['required', 'in:'.implode(',', Catalog::VEHICLE_OPTIONS)],
        ], [], ['vehicle' => 'veículo']);

        if ($this->vehicle === 'moto' && ! $this->isAdult) {
            $this->addError('vehicle', 'Você precisa ter 18 anos para escolher moto.');

            return null;
        }

        Auth::user()->profile()->update([
            'vehicle' => $this->vehicle,
            'onboarded_at' => now(),
        ]);

        return $this->redirect(route('shifts.index'), navigate: true);
    }

    /** Whether the birth date entered in step 1 is a valid, parseable 18+ date. */
    #[Computed]
    public function isAdult(): bool
    {
        $birth = $this->parseBrDate($this->birthDate);

        return $birth && ! Carbon::parse($birth)->isAfter(now()->subYears(18));
    }

    /** Step number => label, shown in the progress indicator. Motoboy has an extra "Veículo" step. */
    #[Computed]
    public function steps(): array
    {
        $steps = [1 => 'Dados pessoais', 2 => 'Perfil + Endereço'];
        if ($this->role === 'courier') {
            $steps[3] = 'Veículo';
        }

        return $steps;
    }

    protected function parseBrDate(string $value): ?string
    {
        if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', trim($value), $m) && checkdate((int) $m[2], (int) $m[1], (int) $m[3])) {
            return "{$m[3]}-{$m[2]}-{$m[1]}";
        }

        return null;
    }

    public function render()
    {
        return view('livewire.onboarding');
    }
}
