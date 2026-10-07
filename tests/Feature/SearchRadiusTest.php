<?php

namespace Tests\Feature;

use App\Jobs\NotifyCouriersOfNewShift;
use App\Livewire\MapPage;
use App\Livewire\Onboarding;
use App\Livewire\Settings;
use App\Livewire\Shifts\Index;
use App\Models\Application;
use App\Models\Benefit;
use App\Models\ExpectedVolume;
use App\Models\Notification;
use App\Models\Shift;
use App\Models\User;
use App\Models\VenueType;
use App\Support\Radius;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** "Vagas perto de mim": the courier's search radius around their base location. */
class SearchRadiusTest extends TestCase
{
    use RefreshDatabase;

    // Sé (São Paulo) is the courier's base; Rio is ~360 km away.
    private const SP = [-23.5505, -46.6333];

    private const NEAR = [-23.5614, -46.6558]; // Paulista area, ~2.5 km

    private const FAR = [-23.4356, -46.4731]; // Guarulhos, ~21 km

    private const RIO = [-22.9068, -43.1729];

    protected function setUp(): void
    {
        parent::setUp();
        VenueType::create(['name' => 'Pizzaria', 'slug' => 'pizzaria', 'status' => 'active']);
        ExpectedVolume::create(['name' => 'Moderado', 'slug' => 'moderado', 'status' => 'active']);
        Benefit::create(['name' => 'Lanche', 'slug' => 'lanche', 'icon' => 'sandwich', 'status' => 'active']);
    }

    protected function user(string $name = 'User'): User
    {
        return User::create(['name' => $name, 'email' => strtolower($name).uniqid().'@test.dev', 'password' => 'secret123']);
    }

    protected function courier(array $profile = []): User
    {
        $user = $this->user('Moto');
        $user->profile->update($profile + [
            'role' => 'courier', 'vehicle' => 'moto', 'has_bag' => true,
            'district' => 'Sé', 'city' => 'São Paulo - SP', 'radius_km' => 15,
            'base_lat' => self::SP[0], 'base_lng' => self::SP[1],
        ]);

        return $user;
    }

    protected function shift(User $creator, string $venue, ?array $at, array $overrides = []): Shift
    {
        return Shift::create(array_merge([
            'creator_id' => $creator->id, 'creator_role' => 'business',
            'venue' => $venue, 'region' => 'Centro', 'address' => 'Rua A, 1',
            'date' => now('America/Sao_Paulo')->addDays(2)->toDateString(),
            'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12,
            'venue_type' => 'pizzaria', 'expected_volume' => 'moderado', 'benefits' => ['lanche'],
            'accepted_vehicles' => ['moto'], 'requires_own_bag' => false, 'couriers_needed' => 1,
            'status' => 'available', 'lat' => $at[0] ?? 0, 'lng' => $at[1] ?? 0,
        ], $overrides));
    }

    public function test_distance_helper_and_clamp(): void
    {
        $this->assertEqualsWithDelta(357, Radius::distanceKm(...self::SP, ...self::RIO), 6);
        $this->assertEqualsWithDelta(0, Radius::distanceKm(...self::SP, ...self::SP), 0.001);
        $this->assertSame(5, Radius::clamp(1));
        $this->assertSame(20, Radius::clamp(99));
        $this->assertSame(12, Radius::clamp(12));
    }

    public function test_listing_hides_shifts_outside_the_radius_but_keeps_unlocated_and_applied_ones(): void
    {
        $owner = $this->user('Dono');
        $courier = $this->courier();
        $this->shift($owner, 'Vaga Perto', self::NEAR);
        $this->shift($owner, 'Vaga Longe', self::FAR);
        $this->shift($owner, 'Vaga Rio', self::RIO);
        $this->shift($owner, 'Vaga Sem Coordenada', null);
        $applied = $this->shift($owner, 'Vaga Candidatada', self::RIO);
        Application::create(['shift_id' => $applied->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($courier);
        Livewire::test(Index::class)
            ->assertSee('Vaga Perto')
            ->assertSee('Vaga Sem Coordenada')
            ->assertSee('Vaga Candidatada')
            ->assertDontSee('Vaga Longe')
            ->assertDontSee('Vaga Rio')
            ->assertSee('Vagas até')->assertSee('15 km')
            // "Ver todas" lifts the radius.
            ->call('toggleAllRegions')
            ->assertDispatched('filters-synced')
            ->assertSee('Vaga Rio')->assertSee('Vaga Longe')
            ->assertSee('todas as regiões')
            ->call('toggleAllRegions')
            ->assertDontSee('Vaga Rio');

        // The ~21 km shift is out of the 20 km maximum, in once the radius is stretched past it.
        $courier->profile->update(['radius_km' => 20]);
        Livewire::test(Index::class)->assertDontSee('Vaga Longe');
        $courier->profile->update(['radius_km' => 25]);
        Livewire::test(Index::class)->assertSee('Vaga Longe');
    }

    public function test_courier_without_coordinates_is_located_on_first_use_so_the_radius_really_filters(): void
    {
        config(['app.geocode_in_tests' => true]);
        \Illuminate\Support\Facades\Http::fake([
            'nominatim.openstreetmap.org/*' => \Illuminate\Support\Facades\Http::response([['lat' => (string) self::SP[0], 'lon' => (string) self::SP[1]]]),
        ]);

        $owner = $this->user('Dono');
        $this->shift($owner, 'Vaga Perto', self::NEAR);
        $this->shift($owner, 'Vaga Longe', self::FAR);
        // An account from before the radius existed: district/city only, no coordinates, radius set to 5 km.
        $courier = $this->courier(['base_lat' => null, 'base_lng' => null, 'radius_km' => 5]);

        $this->actingAs($courier);
        Livewire::test(Index::class)
            ->assertSee('Vaga Perto')
            ->assertDontSee('Vaga Longe')
            ->assertSee('Vagas até')->assertSee('5 km');

        $this->assertEqualsWithDelta(self::SP[0], (float) $courier->profile->fresh()->base_lat, 0.0001);
    }

    public function test_when_the_neighbourhood_cannot_be_located_the_courier_is_told_and_nothing_is_hidden(): void
    {
        config(['app.geocode_in_tests' => true]);
        \Illuminate\Support\Facades\Http::fake(['nominatim.openstreetmap.org/*' => \Illuminate\Support\Facades\Http::response([])]);

        $owner = $this->user('Dono');
        $this->shift($owner, 'Vaga Rio', self::RIO);
        $courier = $this->courier(['base_lat' => null, 'base_lng' => null, 'onboarded_at' => now()]);

        $this->actingAs($courier);
        Livewire::test(Index::class)
            ->assertSee('Vaga Rio')
            ->assertSee('raio de busca está desligado');
        $attempts = count(\Illuminate\Support\Facades\Http::recorded());
        $this->assertGreaterThan(0, $attempts);

        // A failed lookup isn't retried on every page load.
        Livewire::test(Index::class)->assertSee('raio de busca está desligado');
        $this->assertCount($attempts, \Illuminate\Support\Facades\Http::recorded());

        Livewire::test(Settings::class)->assertSee('Não conseguimos localizar o seu bairro');
    }
    public function test_the_radius_never_applies_to_businesses_or_couriers_without_a_base_location(): void
    {
        $owner = $this->user('Dono');
        $this->shift($owner, 'Vaga Rio', self::RIO);

        $business = $this->courier(['role' => 'business']);
        $this->actingAs($business);
        Livewire::test(Index::class)->assertSee('Vaga Rio')->assertDontSee('Vagas até');

        $unlocated = $this->courier(['base_lat' => null, 'base_lng' => null]);
        $this->actingAs($unlocated);
        Livewire::test(Index::class)->assertSee('Vaga Rio')->assertDontSee('Vagas até');
    }

    public function test_my_own_shifts_are_never_hidden_by_my_radius(): void
    {
        $courier = $this->courier();
        $this->shift($courier, 'Minha Vaga Longe', self::RIO, ['creator_role' => 'courier']);

        $this->actingAs($courier);
        Livewire::test(Index::class)->assertSee('Minha Vaga Longe');
    }

    public function test_map_respects_the_radius(): void
    {
        $owner = $this->user('Dono');
        $this->shift($owner, 'Vaga Perto', self::NEAR);
        $this->shift($owner, 'Vaga Rio', self::RIO);

        $this->actingAs($this->courier());
        $shifts = Livewire::test(MapPage::class)->assertSee('1 vagas no mapa')->instance()->shifts;
        $this->assertSame(['Vaga Perto'], array_column($shifts, 'venue'));

        $this->actingAs($this->courier(['role' => 'business']));
        Livewire::test(MapPage::class)->assertSee('2 vagas no mapa');
    }

    public function test_new_shift_notifications_skip_couriers_outside_the_radius(): void
    {
        $owner = $this->user('Dono');
        $near = $this->courier();
        $far = $this->courier(['base_lat' => -22.9, 'base_lng' => -43.2]); // lives in Rio
        $unlocated = $this->courier(['base_lat' => null, 'base_lng' => null]);
        $shift = $this->shift($owner, 'Vaga Nova', self::NEAR);
        Notification::query()->delete();

        (new NotifyCouriersOfNewShift($shift))->handle();

        $this->assertTrue(Notification::where('user_id', $near->id)->exists());
        $this->assertFalse(Notification::where('user_id', $far->id)->exists());
        $this->assertTrue(Notification::where('user_id', $unlocated->id)->exists(), 'no base location → not filtered');
    }

    public function test_settings_saves_the_radius_clamped_and_only_for_couriers(): void
    {
        $courier = $this->courier();
        $this->actingAs($courier);
        Livewire::test(Settings::class)
            ->assertSee('Região')->assertSee('Raio de busca')->assertSee('Sé')
            ->assertDontSee('Cidade base')
            ->call('saveRadius', 30)
            ->assertSet('radiusKm', 20)
            ->assertDispatched('toast', message: 'Raio atualizado para 20 km.')
            ->call('saveRadius', 2)
            ->assertSet('radiusKm', 5);
        $this->assertSame(5, $courier->profile->fresh()->radius_km);

        $business = $this->courier(['role' => 'business']);
        $this->actingAs($business);
        Livewire::test(Settings::class)->assertDontSee('Raio de busca')->call('saveRadius', 8);
        $this->assertSame(15, $business->profile->fresh()->radius_km);
    }

    public function test_onboarding_saves_the_chosen_radius_and_defaults_to_15(): void
    {
        $user = $this->user('Novo');
        $user->profile->update(['onboarded_at' => null, 'role' => 'courier']);
        $this->actingAs($user);

        $component = Livewire::test(Onboarding::class)
            ->set('name', 'João Silva')->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->set('district', 'Centro')->set('city', 'São Paulo')
            ->call('submitAddress')
            ->assertSet('radiusKm', 15)
            ->assertSee('Raio de busca');

        $component->set('radiusKm', 40)->call('setVehicle', 'bike')->call('finish')->assertHasNoErrors();
        $this->assertSame(20, $user->profile->fresh()->radius_km, 'clamped to the maximum');
    }
}
