<?php

namespace Tests\Feature;

use App\Livewire\History;
use App\Livewire\MapPage;
use App\Models\Application;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SecondaryScreensTest extends TestCase
{
    use RefreshDatabase;

    protected function user(string $name = 'User'): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
    }

    protected function shift(User $creator, array $overrides = []): Shift
    {
        return Shift::create(array_merge([
            'creator_id' => $creator->id,
            'creator_role' => 'business',
            'venue' => 'Pizzaria X',
            'region' => 'Centro',
            'date' => now()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '23:00',
            'daily_rate' => 150,
            'delivery_fee_min' => 8,
            'delivery_fee_max' => 12,
            'venue_type' => 'pizzaria',
            'expected_volume' => 'moderado',
            'accepted_vehicles' => ['moto'],
            'status' => 'available',
            'lat' => 0, 'lng' => 0,
        ], $overrides));
    }

    public function test_history_published_and_worked_tabs(): void
    {
        $owner = $this->user('Dono');
        $other = $this->user('Outro');
        $courier = $this->user('Moto');

        $this->shift($owner, ['venue' => 'Publicada']);
        $worked = $this->shift($other, ['venue' => 'Trabalhada']);
        Application::create(['shift_id' => $worked->id, 'user_id' => $courier->id, 'status' => 'accepted']);

        $this->actingAs($owner);
        Livewire::test(History::class)->assertSee('Publicada');

        $this->actingAs($courier);
        Livewire::test(History::class)
            ->assertDontSee('Publicada')
            ->call('setTab', 'worked')
            ->assertSee('Trabalhada');
    }

    public function test_map_only_lists_geocoded_available_shifts(): void
    {
        $owner = $this->user('Dono');
        $this->shift($owner, ['venue' => 'Geo', 'lat' => -23.5, 'lng' => -46.6]);
        $this->shift($owner, ['venue' => 'SemCoord']); // lat/lng 0 → excluída

        $this->actingAs($owner);
        Livewire::test(MapPage::class)->assertSee('1 vagas no mapa');
    }

    public function test_pages_render(): void
    {
        $this->actingAs($this->user('Dono'));

        $this->get(route('history'))->assertOk()->assertSee('Histórico de Turnos');
        $this->get(route('help'))->assertOk()->assertSee('Perguntas frequentes');
        $this->get(route('map'))->assertOk()->assertSee('vagas no mapa');
    }
}
