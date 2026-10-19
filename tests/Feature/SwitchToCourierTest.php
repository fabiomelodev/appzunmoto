<?php

namespace Tests\Feature;

use App\Livewire\SwitchToCourier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SwitchToCourierTest extends TestCase
{
    use RefreshDatabase;

    /** An "estabelecimento" account (18+, as required to become one) missing courier-only data. */
    protected function businessUser(): User
    {
        $user = User::create([
            'name' => 'Dono',
            'email' => 'dono'.uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
        $user->profile()->update(['role' => 'business', 'birth_date' => now()->subYears(25)->toDateString()]);

        return $user;
    }

    public function test_form_is_shown_when_data_is_missing(): void
    {
        $this->actingAs($this->businessUser());

        $this->get(route('switch-to-courier'))->assertOk()->assertSee('Complete seu perfil de motoboy');
    }

    public function test_user_can_back_out_and_stays_as_establishment(): void
    {
        $user = $this->businessUser();
        $user->profile()->update(['onboarded_at' => now()]);
        $this->actingAs($user);

        // The form offers a way back that isn't saving anything...
        $this->get(route('switch-to-courier'))
            ->assertOk()
            ->assertSee('Continuar como Estabelecimento')
            ->assertSee('href="'.route('shifts.index').'"', false);

        // ...so just opening it leaves the account as it was, and the shifts page works as an establishment.
        $this->assertSame('business', $user->profile->fresh()->role);
        $this->get(route('shifts.index'))->assertOk();
        $this->assertSame('business', $user->profile->fresh()->role);
    }
    public function test_flips_role_immediately_when_nothing_is_missing(): void
    {
        $user = $this->businessUser();
        $user->profile()->update(['district' => 'Centro', 'city' => 'São Paulo', 'vehicle' => 'bike']);
        $this->actingAs($user);

        $this->get(route('switch-to-courier'))->assertRedirect(route('shifts.index'));

        $this->assertSame('courier', $user->profile->fresh()->role);
    }

    public function test_finish_sets_role_and_courier_data(): void
    {
        $user = $this->businessUser();
        $this->actingAs($user);

        Livewire::test(SwitchToCourier::class)
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->call('setVehicle', 'moto')
            ->call('finish')
            ->assertHasNoErrors()
            ->assertRedirect(route('shifts.index'));

        $profile = $user->profile->fresh();
        $this->assertSame('courier', $profile->role);
        $this->assertSame('Centro', $profile->district);
        $this->assertSame('São Paulo', $profile->city);
        $this->assertSame('moto', $profile->vehicle);
    }

    public function test_finish_requires_district_city_and_vehicle(): void
    {
        $this->actingAs($this->businessUser());

        Livewire::test(SwitchToCourier::class)
            ->call('finish')
            ->assertHasErrors(['district', 'city', 'vehicle']);
    }

    public function test_moto_stays_blocked_here_too_for_an_underage_profile(): void
    {
        // Edge case: reaches this screen directly with an incomplete, underage profile.
        $user = User::create([
            'name' => 'Jovem',
            'email' => 'jovem'.uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
        $user->profile()->update(['birth_date' => now()->subYears(17)->toDateString()]);
        $this->actingAs($user);

        Livewire::test(SwitchToCourier::class)
            ->call('setVehicle', 'moto')
            ->assertSet('vehicle', '')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->set('vehicle', 'moto') // bypasses the UI guard in setVehicle()
            ->call('finish')
            ->assertHasErrors('vehicle');
    }
}
