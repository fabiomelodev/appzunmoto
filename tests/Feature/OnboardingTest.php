<?php

namespace Tests\Feature;

use App\Livewire\Onboarding;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    /** A user pending onboarding, as if just created via email/password signup. */
    protected function pendingUser(): User
    {
        $user = User::create([
            'name' => 'João Silva',
            'email' => 'joao'.uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
        $user->profile()->update(['onboarded_at' => null]);

        return $user;
    }

    public function test_pending_user_is_redirected_to_onboarding(): void
    {
        $this->actingAs($this->pendingUser());

        $this->get(route('shifts.index'))->assertRedirect(route('onboarding'));
    }

    public function test_onboarded_user_is_not_redirected(): void
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana'.uniqid().'@test.dev', 'password' => 'secret123']);
        $this->actingAs($user);

        $this->get(route('shifts.index'))->assertOk();
    }

    public function test_step1_rejects_under_16(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'Muito Jovem')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(15)->format('d/m/Y'))
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->assertHasErrors('birthDate')
            ->assertSet('step', 1);
    }

    public function test_step1_rejects_a_calendar_invalid_date(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', '31/02/1990') // fevereiro não tem dia 31
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->assertHasErrors('birthDate')
            ->assertSet('step', 1);
    }

    public function test_step1_requires_a_cpf(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(30)->format('d/m/Y'))
            ->call('submitPersonalData')
            ->assertHasErrors('cpf')
            ->assertSet('step', 1);
    }

    public function test_step1_rejects_an_invalid_cpf(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(30)->format('d/m/Y'))
            ->set('cpf', '111.111.111-11') // dígitos repetidos, check-digit inválido
            ->call('submitPersonalData')
            ->assertHasErrors('cpf')
            ->assertSet('step', 1);
    }

    public function test_step1_rejects_a_cpf_already_registered_by_another_account(): void
    {
        $other = $this->pendingUser();
        $other->profile()->update(['cpf' => '52998224725']);

        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(30)->format('d/m/Y'))
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->assertHasErrors('cpf')
            ->assertSet('step', 1);
    }

    public function test_step1_advances_to_the_profile_step(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(30)->format('d/m/Y'))
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->assertHasNoErrors()
            ->assertSet('step', 2);
    }

    public function test_previous_step_goes_back_and_keeps_the_data_filled(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(30)->format('d/m/Y'))
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->assertSet('step', 2)
            ->call('previousStep')
            ->assertSet('step', 1)
            ->assertSet('name', 'João Silva')
            ->assertSet('birthDate', now()->subYears(30)->format('d/m/Y'))
            ->assertSet('cpf', '529.982.247-25');
    }

    public function test_cannot_select_business_before_entering_an_adult_birth_date(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->call('setRole', 'business')
            ->assertSet('role', 'courier');
    }

    public function test_can_select_business_once_an_adult_birth_date_is_entered(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('birthDate', now()->subYears(25)->format('d/m/Y'))
            ->call('setRole', 'business')
            ->assertSet('role', 'business');
    }

    public function test_switching_from_courier_to_business_clears_address_fields(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('birthDate', now()->subYears(25)->format('d/m/Y'))
            ->set('cep', '01310-100')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('setRole', 'business')
            ->assertSet('cep', '')
            ->assertSet('district', '')
            ->assertSet('city', '');
    }

    public function test_switching_from_business_to_courier_clears_address_fields(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('birthDate', now()->subYears(25)->format('d/m/Y'))
            ->call('setRole', 'business')
            ->set('label', 'Restaurante da Ana')
            ->set('cep', '01310-100')
            ->set('street', 'Av Paulista')
            ->set('number', '100')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->set('reference', 'Perto do metrô')
            ->call('setRole', 'courier')
            ->assertSet('label', '')
            ->assertSet('cep', '')
            ->assertSet('street', '')
            ->assertSet('number', '')
            ->assertSet('district', '')
            ->assertSet('city', '')
            ->assertSet('reference', '');
    }

    public function test_reselecting_the_same_role_does_not_clear_address_fields(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('setRole', 'courier') // already the default role
            ->assertSet('district', 'Centro')
            ->assertSet('city', 'São Paulo');
    }

    public function test_lookup_cep_fills_street_district_and_city(): void
    {
        Http::fake([
            'viacep.com.br/*' => Http::response([
                'logradouro' => 'Avenida Paulista',
                'bairro' => 'Bela Vista',
                'localidade' => 'São Paulo',
                'uf' => 'SP',
                'erro' => false,
            ]),
        ]);
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('birthDate', now()->subYears(25)->format('d/m/Y'))
            ->call('setRole', 'business')
            ->set('cep', '01310-100')
            ->call('lookupCep')
            ->assertSet('street', 'Avenida Paulista')
            ->assertSet('district', 'Bela Vista')
            ->assertSet('city', 'São Paulo - SP');
    }

    public function test_courier_profile_step_saves_data_and_advances_to_vehicle_step(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->call('setRole', 'courier')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('submitAddress')
            ->assertHasNoErrors()
            ->assertSet('step', 3);

        $profile = auth()->user()->profile->fresh();
        $this->assertSame('courier', $profile->role);
        $this->assertSame('1990-05-10', $profile->birth_date->toDateString());
        $this->assertNull($profile->street, 'motoboy não precisa de rua/número, só CEP/bairro/cidade');
        $this->assertNull($profile->street_number);
        $this->assertSame('Centro', $profile->district);
        $this->assertSame('52998224725', $profile->cpf);
        $this->assertFalse($profile->isOnboarded(), 'ainda falta escolher o veículo');
    }

    public function test_courier_finishes_onboarding_by_choosing_a_vehicle(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('submitAddress')
            ->call('setVehicle', 'bike-eletrica')
            ->assertSet('vehicle', 'bike-eletrica')
            ->call('finish')
            ->assertHasNoErrors()
            ->assertRedirect(route('shifts.index'));

        $profile = auth()->user()->profile->fresh();
        $this->assertSame('bike-eletrica', $profile->vehicle);
        $this->assertTrue($profile->isOnboarded());
    }

    public function test_vehicle_is_required_to_finish(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('submitAddress')
            ->call('finish')
            ->assertHasErrors('vehicle');

        $this->assertFalse(auth()->user()->profile->fresh()->isOnboarded());
    }

    public function test_vehicle_step_also_has_a_back_button(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('submitAddress')
            ->assertSet('step', 3)
            ->call('previousStep')
            ->assertSet('step', 2);
    }

    public function test_minor_courier_cannot_select_moto_but_can_pick_other_vehicles(): void
    {
        $this->actingAs($this->pendingUser());

        $component = Livewire::test(Onboarding::class)
            ->set('name', 'Jovem Demais')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(17)->format('d/m/Y'))
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('submitAddress')
            ->assertHasNoErrors()
            ->assertSet('step', 3);

        // Trying to pick "moto" is silently ignored while under 18.
        $component->call('setVehicle', 'moto')->assertSet('vehicle', '');

        $component->call('setVehicle', 'bike')
            ->assertSet('vehicle', 'bike')
            ->call('finish')
            ->assertHasNoErrors()
            ->assertRedirect(route('shifts.index'));

        $profile = auth()->user()->profile->fresh();
        $this->assertSame('bike', $profile->vehicle);
        $this->assertTrue($profile->isOnboarded());
    }

    public function test_minor_courier_is_rejected_if_moto_is_forced_on_finish(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'Jovem Demais')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(17)->format('d/m/Y'))
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('submitAddress')
            ->set('vehicle', 'moto') // bypasses the UI guard in setVehicle()
            ->call('finish')
            ->assertHasErrors('vehicle');

        $this->assertFalse(auth()->user()->profile->fresh()->isOnboarded());
    }

    public function test_completing_as_business_creates_the_first_address_and_finishes(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'Ana Souza')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->call('setRole', 'business')
            ->set('label', 'Restaurante da Ana')
            ->set('cep', '01310-100')
            ->set('street', 'Av Paulista')
            ->set('number', '100')
            ->set('district', 'Bela Vista')
            ->set('city', 'São Paulo - SP')
            ->set('reference', 'Perto do metrô')
            ->call('submitAddress')
            ->assertHasNoErrors()
            ->assertRedirect(route('shifts.index'));

        $profile = auth()->user()->profile->fresh();
        $this->assertSame('business', $profile->role);
        $this->assertSame('1990-05-10', $profile->birth_date->toDateString());
        $this->assertSame('52998224725', $profile->cpf);
        $this->assertNull($profile->district, 'endereço fica no UserAddress, não no profile');
        $this->assertNull($profile->city);
        $this->assertTrue($profile->isOnboarded());

        $address = UserAddress::where('user_id', auth()->id())->first();
        $this->assertNotNull($address, 'primeiro estabelecimento deve ser criado no onboarding');
        $this->assertSame('Restaurante da Ana', $address->label);
        $this->assertSame('01310100', $address->postal_code);
        $this->assertSame('Av Paulista', $address->street);
        $this->assertSame('100', $address->number);
        $this->assertSame('Bela Vista', $address->district);
        $this->assertSame('Perto do metrô', $address->reference);
        $this->assertNull($address->photo_url, 'foto do local é opcional');
    }

    public function test_business_requires_label_street_and_number(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'Ana Souza')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->call('setRole', 'business')
            ->call('submitAddress')
            ->assertHasErrors(['label', 'street', 'number']);

        $this->assertFalse(auth()->user()->profile->fresh()->isOnboarded());
        $this->assertSame(0, UserAddress::where('user_id', auth()->id())->count());
    }

    public function test_business_age_is_enforced_even_if_role_is_forced_past_the_ui_guard(): void
    {
        $this->actingAs($this->pendingUser());

        Livewire::test(Onboarding::class)
            ->set('name', 'Jovem Demais')
            ->set('phone', '(11) 99999-0000')
            ->set('birthDate', now()->subYears(17)->format('d/m/Y')) // passa no piso de 16 do passo 1
            ->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->set('role', 'business') // bypassa o guard de setRole() no passo 2
            ->set('label', 'Restaurante do Jovem')
            ->set('street', 'Av Paulista')
            ->set('number', '100')
            ->call('submitAddress')
            ->assertDispatched('toast');

        $this->assertFalse(auth()->user()->profile->fresh()->isOnboarded());
        $this->assertSame(0, UserAddress::where('user_id', auth()->id())->count());
    }
}
