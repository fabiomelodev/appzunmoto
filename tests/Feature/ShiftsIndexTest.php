<?php

namespace Tests\Feature;

use App\Livewire\Shifts\Index;
use App\Models\Application;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftsIndexTest extends TestCase
{
    use RefreshDatabase;

    protected function creator(): User
    {
        return User::create([
            'name' => 'Dono Teste',
            'email' => 'dono'.uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
    }

    protected function makeShift(array $overrides = []): Shift
    {
        return Shift::create(array_merge([
            'creator_id' => $this->creator()->id,
            'creator_role' => 'business',
            'venue' => 'Local '.uniqid(),
            'region' => 'Centro',
            'date' => now()->toDateString(),
            'start_time' => '08:00',
            'end_time' => '23:59',
            'daily_rate' => 150,
            'delivery_fee_min' => 8,
            'delivery_fee_max' => 12,
            'benefits' => ['lanche'],
            'accepted_vehicles' => ['moto'],
            'couriers_needed' => 1,
            'status' => 'available',
            'lat' => 0,
            'lng' => 0,
        ], $overrides));
    }

    public function test_shows_available_and_hides_filled_expired_and_full(): void
    {
        $this->actingAs($this->creator());

        $this->makeShift(['venue' => 'VagaVisivel']);
        $this->makeShift(['venue' => 'VagaPreenchida', 'status' => 'filled']);
        $this->makeShift(['venue' => 'VagaExpirada', 'date' => now()->subDay()->toDateString()]);

        $full = $this->makeShift(['venue' => 'VagaCheia', 'couriers_needed' => 1]);
        Application::create(['shift_id' => $full->id, 'user_id' => $this->creator()->id, 'status' => 'accepted']);

        Livewire::test(Index::class)
            ->assertSee('VagaVisivel')
            ->assertDontSee('VagaPreenchida')
            ->assertDontSee('VagaExpirada')
            ->assertDontSee('VagaCheia');
    }

    public function test_search_filters_by_term(): void
    {
        $this->actingAs($this->creator());
        $this->makeShift(['venue' => 'Sushi House', 'region' => 'Zona Sul']);
        $this->makeShift(['venue' => 'Pizza Place', 'region' => 'Centro']);

        Livewire::test(Index::class)
            ->set('q', 'Sushi')
            ->assertSee('Sushi House')
            ->assertDontSee('Pizza Place');
    }

    public function test_region_filter(): void
    {
        $this->actingAs($this->creator());
        $this->makeShift(['venue' => 'NoCentro', 'region' => 'Centro']);
        $this->makeShift(['venue' => 'NaZonaSul', 'region' => 'Zona Sul']);

        Livewire::test(Index::class)
            ->call('setRegion', 'Zona Sul')
            ->assertSee('NaZonaSul')
            ->assertDontSee('NoCentro');
    }

    public function test_vehicle_filter(): void
    {
        $this->actingAs($this->creator());
        $this->makeShift(['venue' => 'SoMoto', 'accepted_vehicles' => ['moto']]);
        $this->makeShift(['venue' => 'AceitaBike', 'accepted_vehicles' => ['bike']]);

        Livewire::test(Index::class)
            ->call('applyFilters', [
                'vehicles' => ['bike'], 'dailyMin' => '', 'feeMin' => '',
                'startTime' => '', 'benefits' => [], 'ownBag' => 'any', 'date' => '',
            ])
            ->assertSee('AceitaBike')
            ->assertDontSee('SoMoto');
    }

    public function test_set_vehicle_updates_profile(): void
    {
        $user = $this->creator();
        $this->actingAs($user);

        Livewire::test(Index::class)->call('setVehicle', 'bike');

        $this->assertSame('bike', $user->fresh()->profile->vehicle);
    }

    public function test_full_page_renders_with_layout_and_card(): void
    {
        $user = $this->creator();
        $this->makeShift(['venue' => 'CardNaPagina']);

        $this->actingAs($user)
            ->get('/shifts')
            ->assertOk()
            ->assertSee('começa aqui')   // hero
            ->assertSee('CardNaPagina')  // shift card
            ->assertSee('Vagas');        // bottom-nav label
    }
}
