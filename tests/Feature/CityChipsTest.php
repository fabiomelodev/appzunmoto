<?php

namespace Tests\Feature;

use App\Livewire\Shifts\Create;
use App\Livewire\Shifts\Index;
use App\Models\Benefit;
use App\Models\ExpectedVolume;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\VenueType;
use App\Support\City;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

/** The neighbourhood chips on "Vagas": only the selected city's neighbourhoods, only shifts that are open. */
class CityChipsTest extends TestCase
{
    use RefreshDatabase;

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

    protected function shift(User $creator, string $venue, string $region, ?string $city, array $overrides = []): Shift
    {
        return Shift::create(array_merge([
            'creator_id' => $creator->id, 'creator_role' => 'business',
            'venue' => $venue, 'region' => $region, 'city' => $city, 'address' => 'Rua A, 1',
            'date' => now('America/Sao_Paulo')->addDays(2)->toDateString(),
            'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12,
            'venue_type' => 'pizzaria', 'expected_volume' => 'moderado', 'benefits' => ['lanche'],
            'accepted_vehicles' => ['moto'], 'requires_own_bag' => false, 'couriers_needed' => 1,
            'status' => 'available', 'lat' => 0, 'lng' => 0,
        ], $overrides));
    }

    protected function courierIn(string $city): User
    {
        $user = $this->user('Moto');
        $user->profile->update(['role' => 'courier', 'vehicle' => 'moto', 'has_bag' => true, 'district' => 'Centro', 'city' => $city, 'onboarded_at' => now()]);

        return $user;
    }

    public function test_city_helper_normalizes_and_compares_spellings(): void
    {
        $this->assertSame('Mogi das Cruzes', City::normalize('Mogi das Cruzes - SP'));
        $this->assertSame('São Paulo', City::normalize(' São Paulo/SP '));
        $this->assertSame('São Paulo', City::normalize('São Paulo'));
        $this->assertSame('', City::normalize(null));
        $this->assertTrue(City::same('Sao Paulo', 'são paulo - SP'));
        $this->assertFalse(City::same('Mogi das Cruzes', 'Suzano'));
        $this->assertFalse(City::same('', ''));
    }

    public function test_a_courier_sees_the_neighbourhoods_of_their_profile_city_by_default(): void
    {
        $owner = $this->user('Dono');
        $this->shift($owner, 'Loja SP 1', 'Pinheiros', 'São Paulo');
        $this->shift($owner, 'Loja SP 2', 'Centro', 'São Paulo');
        $this->shift($owner, 'Loja Mogi', 'Braz Cubas', 'Mogi das Cruzes');
        $this->shift($owner, 'Loja Antiga', 'Bairro Sem Cidade', null); // created before shifts stored a city

        $this->actingAs($this->courierIn('São Paulo - SP'));
        $component = Livewire::test(Index::class);

        // Own city + shifts that don't have a city yet; never another city's neighbourhoods.
        $this->assertSame(['Bairro Sem Cidade', 'Centro', 'Pinheiros'], $component->instance()->regions);
        $component->assertSee('Bairros em')->assertSee('Todas as cidades');
        $this->assertSame(['mogi das cruzes' => 'Mogi das Cruzes', 'sao paulo' => 'São Paulo'], $component->instance()->cities);

        // Switching city swaps the chips and drops the neighbourhood picked before.
        $component->call('setRegion', 'Pinheiros')
            ->call('setCity', 'mogi das cruzes');
        $this->assertNull($component->instance()->region);
        $this->assertSame(['Bairro Sem Cidade', 'Braz Cubas'], $component->instance()->regions);

        $component->call('setCity', Index::ALL_CITIES);
        $this->assertSame(['Bairro Sem Cidade', 'Braz Cubas', 'Centro', 'Pinheiros'], $component->instance()->regions);
    }

    public function test_a_neighbourhood_chip_only_matches_inside_the_selected_city(): void
    {
        $owner = $this->user('Dono');
        $this->shift($owner, 'Centro de SP', 'Centro', 'São Paulo');
        $this->shift($owner, 'Centro de Mogi', 'Centro', 'Mogi das Cruzes');

        $this->actingAs($this->courierIn('Mogi das Cruzes - SP'));
        Livewire::test(Index::class)
            ->call('setRegion', 'Centro')
            ->assertSee('Centro de Mogi')
            ->assertDontSee('Centro de SP')
            ->call('setCity', Index::ALL_CITIES)
            ->call('setRegion', 'Centro')
            ->assertSee('Centro de Mogi')->assertSee('Centro de SP');
    }

    public function test_only_neighbourhoods_with_open_shifts_are_listed(): void
    {
        $owner = $this->user('Dono');
        $this->shift($owner, 'Aberta', 'Pinheiros', 'São Paulo');
        $this->shift($owner, 'Encerrada', 'Bairro Encerrado', 'São Paulo', ['date' => now('America/Sao_Paulo')->subDays(3)->toDateString()]);
        $this->shift($owner, 'Cheia', 'Bairro Cheio', 'São Paulo', ['status' => 'filled']);
        $this->shift($owner, 'Pausada', 'Bairro Pausado', 'São Paulo', ['active' => false]);

        $this->actingAs($this->courierIn('São Paulo'));
        $this->assertSame(['Pinheiros'], Livewire::test(Index::class)->instance()->regions);
    }

    public function test_neighbourhoods_outside_the_radius_are_not_listed(): void
    {
        $owner = $this->user('Dono');
        $this->shift($owner, 'Perto', 'Bairro Perto', 'São Paulo', ['lat' => -23.5614, 'lng' => -46.6558]);
        $this->shift($owner, 'Longe', 'Bairro Longe', 'São Paulo', ['lat' => -23.4356, 'lng' => -46.4731]); // ~21 km

        $courier = $this->courierIn('São Paulo');
        $courier->profile->update(['radius_km' => 15, 'base_lat' => -23.5505, 'base_lng' => -46.6333]);
        $this->actingAs($courier);

        $component = Livewire::test(Index::class);
        $this->assertSame(['Bairro Perto'], $component->instance()->regions);
        $component->call('toggleAllRegions');
        $this->assertSame(['Bairro Longe', 'Bairro Perto'], Livewire::test(Index::class)->call('toggleAllRegions')->instance()->regions);
    }

    public function test_an_establishment_starts_on_the_city_of_the_address_its_radius_is_measured_from(): void
    {
        $owner = $this->user('Dono');
        $this->shift($owner, 'Loja SP', 'Pinheiros', 'São Paulo');
        $this->shift($owner, 'Loja Mogi', 'Braz Cubas', 'Mogi das Cruzes');

        $business = $this->user('Estab');
        $business->profile->update(['role' => 'business', 'onboarded_at' => now()]);
        UserAddress::create([
            'user_id' => $business->id, 'label' => 'Loja Mogi', 'postal_code' => '08730000', 'street' => 'Rua A', 'number' => '1',
            'district' => 'Braz Cubas', 'city' => 'Mogi das Cruzes - SP', 'lat' => -23.52, 'lng' => -46.19,
        ]);
        $this->actingAs($business);

        $component = Livewire::test(Index::class);
        // Radius centred on the Mogi address, no shift coordinates → nothing is hidden; chips follow the base city.
        $this->assertSame('mogi das cruzes', $component->instance()->selectedCityKey);
        $this->assertSame(['Braz Cubas'], $component->instance()->regions);

        // The establishment can compare other cities through the selector.
        $component->call('setCity', 'sao paulo');
        $this->assertSame(['Pinheiros'], $component->instance()->regions);
        $component->call('setCity', Index::ALL_CITIES);
        $this->assertSame(['Braz Cubas', 'Pinheiros'], $component->instance()->regions);
    }

    public function test_a_new_shift_stores_the_city_of_its_address_without_the_uf(): void
    {
        $owner = $this->user('Dono');
        $address = UserAddress::create([
            'user_id' => $owner->id, 'label' => 'Minha Loja', 'postal_code' => '08730000', 'street' => 'Rua A', 'number' => '1',
            'district' => 'Braz Cubas', 'city' => 'Mogi das Cruzes - SP', 'lat' => -23.52, 'lng' => -46.19,
        ]);
        $this->actingAs($owner);

        Livewire::withQueryParams(['as' => 'business', 'address' => $address->id])
            ->test(Create::class)
            ->call('save', [
                'date' => now()->addDay()->toDateString(), 'startTime' => '18:00', 'endTime' => '23:00',
                'dailyRate' => '150', 'feeMin' => '8', 'feeMax' => '12', 'contactName' => '', 'contactPhone' => '', 'notes' => '',
                'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
                'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
            ])
            ->assertRedirect();

        $shift = Shift::where('creator_id', $owner->id)->firstOrFail();
        $this->assertSame('Mogi das Cruzes', $shift->city);
        $this->assertSame('Braz Cubas', $shift->region);
    }

    public function test_backfill_command_fills_the_city_from_the_publishers_addresses_then_from_the_map(): void
    {
        Http::fake([
            'nominatim.openstreetmap.org/reverse*' => Http::response(['address' => ['town' => 'Suzano', 'state' => 'São Paulo']]),
        ]);

        $owner = $this->user('Dono');
        UserAddress::create([
            'user_id' => $owner->id, 'label' => 'Loja', 'postal_code' => '08730000', 'street' => 'Rua A', 'number' => '1',
            'district' => 'Braz Cubas', 'city' => 'Mogi das Cruzes - SP', 'lat' => -23.52, 'lng' => -46.19,
        ]);
        $fromAddress = $this->shift($owner, 'Da Loja', 'Braz Cubas', null, ['lat' => -23.52, 'lng' => -46.19]);
        $fromMap = $this->shift($owner, 'Outro Lugar', 'Centro', null, ['lat' => -23.54, 'lng' => -46.31]);
        $noCoords = $this->shift($owner, 'Sem Mapa', 'Centro', null);
        $already = $this->shift($owner, 'Ja Tem', 'Centro', 'Itaquaquecetuba', ['lat' => -23.54, 'lng' => -46.31]);

        $this->artisan('shifts:backfill-city')->assertSuccessful();

        $this->assertSame('Mogi das Cruzes', $fromAddress->fresh()->city);
        $this->assertSame('Suzano', $fromMap->fresh()->city);
        $this->assertNull($noCoords->fresh()->city);
        $this->assertSame('Itaquaquecetuba', $already->fresh()->city, 'shifts that already have a city are left alone');
    }
}
