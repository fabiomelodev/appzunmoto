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

    public function test_min_delivery_fee_filter(): void
    {
        $this->actingAs($this->creator());
        $this->makeShift(['venue' => 'TaxaBaixa', 'delivery_fee_min' => 5, 'delivery_fee_max' => 5]);
        $this->makeShift(['venue' => 'TaxaAlta', 'delivery_fee_min' => 10, 'delivery_fee_max' => 10]);

        Livewire::test(Index::class)
            ->call('applyFilters', [
                'vehicles' => [], 'dailyMin' => '', 'feeMin' => '8',
                'startTime' => '', 'benefits' => [], 'ownBag' => 'any', 'date' => '',
            ])
            ->assertSee('TaxaAlta')
            ->assertDontSee('TaxaBaixa');
    }

    public function test_set_vehicle_updates_profile(): void
    {
        $user = $this->creator();
        $this->actingAs($user);

        Livewire::test(Index::class)->call('setVehicle', 'bike');

        $this->assertSame('bike', $user->fresh()->profile->vehicle);
    }

    public function test_accepted_courier_sees_success_message_others_dont(): void
    {
        $courier = User::create(['name' => 'Moto', 'email' => 'moto'.uniqid().'@test.dev', 'password' => 'secret123']);
        $other = User::create(['name' => 'Outro', 'email' => 'outro'.uniqid().'@test.dev', 'password' => 'secret123']);

        // 1-courier shift where this courier was accepted (reserved + therefore full).
        $shift = $this->makeShift(['venue' => 'VagaAceita', 'status' => 'reserved', 'reserved_by' => $courier->id]);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted']);

        // The accepted courier still sees the (full) shift + the confirmation banner.
        $this->actingAs($courier);
        Livewire::test(Index::class)
            ->assertSee('VagaAceita')
            ->assertSee('Interesse aceito com sucesso');

        // Anyone else: the full shift is hidden, and no banner anywhere.
        $this->actingAs($other);
        Livewire::test(Index::class)
            ->assertDontSee('VagaAceita')
            ->assertDontSee('Interesse aceito com sucesso');
    }

    public function test_accepted_courier_still_sees_filled_shift_they_belong_to(): void
    {
        $courier = User::create(['name' => 'Moto', 'email' => 'moto'.uniqid().'@test.dev', 'password' => 'secret123']);
        $other = User::create(['name' => 'Outro', 'email' => 'outro'.uniqid().'@test.dev', 'password' => 'secret123']);

        // A shift that became filled (partnership confirmed) with this courier accepted.
        $shift = $this->makeShift(['venue' => 'VagaPreenchidaMinha', 'status' => 'filled', 'reserved_by' => $courier->id]);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted']);

        // The committed courier keeps seeing it (with the confirmation banner).
        $this->actingAs($courier);
        Livewire::test(Index::class)
            ->assertSee('VagaPreenchidaMinha')
            ->assertSee('Interesse aceito com sucesso');

        // Filled shifts stay off-market for everyone else.
        $this->actingAs($other);
        Livewire::test(Index::class)
            ->assertDontSee('VagaPreenchidaMinha');
    }

    public function test_owner_sees_own_paused_shift_with_badge_others_dont(): void
    {
        $owner = $this->creator();
        $other = $this->creator();
        $this->makeShift(['venue' => 'VagaPausada', 'creator_id' => $owner->id, 'active' => false]);

        // Owner sees the paused shift with the "Pausada" badge.
        $this->actingAs($owner);
        Livewire::test(Index::class)
            ->assertSee('VagaPausada')
            ->assertSee('Pausada');

        // Other users don't see paused shifts.
        $this->actingAs($other);
        Livewire::test(Index::class)
            ->assertDontSee('VagaPausada');
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
