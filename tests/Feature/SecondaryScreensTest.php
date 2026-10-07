<?php

namespace Tests\Feature;

use App\Livewire\Chats\Index as ChatsIndex;
use App\Livewire\MapPage;
use App\Models\Application;
use App\Models\Contact;
use App\Models\Faq;
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

    public function test_history_only_lists_ended_shifts_as_concluded_or_expired(): void
    {
        $owner = $this->user('Dono');
        $other = $this->user('Outro');
        $courier = $this->user('Moto');
        $past = now('America/Sao_Paulo')->subDays(2)->toDateString();
        $future = now('America/Sao_Paulo')->addDays(5)->toDateString();

        // Published by the owner.
        $done = $this->shift($owner, ['venue' => 'Publicada Concluida', 'date' => $past, 'status' => 'filled']);
        Application::create(['shift_id' => $done->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true]);
        $this->shift($owner, ['venue' => 'Publicada Expirada', 'date' => $past]);
        $this->shift($owner, ['venue' => 'Publicada Aberta', 'date' => $future]);
        $this->shift($owner, ['venue' => 'Publicada Andamento', 'date' => $future, 'status' => 'filled']);

        // Worked by the courier.
        $worked = $this->shift($other, ['venue' => 'Trabalhada Concluida', 'date' => $past]);
        Application::create(['shift_id' => $worked->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true]);
        $lapsed = $this->shift($other, ['venue' => 'Trabalhada Expirada', 'date' => $past]);
        Application::create(['shift_id' => $lapsed->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => false]);
        $coming = $this->shift($other, ['venue' => 'Trabalhada Futura', 'date' => $future]);
        Application::create(['shift_id' => $coming->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true]);

        $this->actingAs($owner);
        $component = Livewire::test(ChatsIndex::class)->call('setTab', 'historico');
        $component->assertSee('Publicada Concluida')
            ->assertSee('Publicada Expirada')
            ->assertDontSee('Publicada Aberta')
            ->assertDontSee('Publicada Andamento')
            ->assertSee('Concluída')
            ->assertSee('Expirada')
            ->assertDontSee('Ativa');

        $this->actingAs($courier);
        Livewire::test(ChatsIndex::class)
            ->call('setTab', 'historico')
            ->assertDontSee('Publicada Expirada')
            ->call('setHistoryTab', 'worked')
            ->assertSee('Trabalhada Concluida')
            ->assertSee('Trabalhada Expirada')
            ->assertDontSee('Trabalhada Futura')
            ->assertDontSee('Ativa');
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
        Faq::create(['name' => 'Como aceito uma vaga?', 'description' => 'Toque no card da vaga.', 'status' => 'active']);

        $this->actingAs($this->user('Dono'));

        $this->get(route('chats.index'))->assertOk()->assertSee('Histórico');
        $this->get(route('help'))->assertOk()->assertSee('Perguntas frequentes');
        $this->get(route('map'))->assertOk()->assertSee('vagas no mapa');
    }

    public function test_help_loads_livewire_assets_so_the_faq_accordion_works(): void
    {
        // /help é uma view "solta" (sem componente Livewire); sem @livewireScripts no
        // layout, Alpine (empacotado dentro do livewire.js) nunca carrega e os
        // @click do acordeão de FAQ ficam mortos até o usuário navegar por uma
        // página que É um componente Livewire (ex.: o Menu) e voltar via wire:navigate.
        $this->actingAs($this->user('Dono'));

        $this->get(route('help'))
            ->assertOk()
            ->assertSee('livewire.js', false);
    }

    public function test_help_lists_only_active_faqs_from_database(): void
    {
        Faq::create(['name' => 'Pergunta ativa', 'description' => 'Resposta <strong>rica</strong>.', 'status' => 'active']);
        Faq::create(['name' => 'Pergunta inativa', 'description' => 'Não deve aparecer.', 'status' => 'inactive']);

        $this->actingAs($this->user('Dono'));

        $response = $this->get(route('help'))->assertOk();
        $response->assertSee('Pergunta ativa');
        $response->assertSee('Resposta <strong>rica</strong>.', false);
        $response->assertDontSee('Pergunta inativa');
    }

    public function test_help_lists_active_contacts_from_database_in_order(): void
    {
        Contact::create(['name' => 'suporte@giromoto.com.br', 'link' => 'mailto:suporte@giromoto.com.br', 'type' => 'email', 'status' => 'active', 'order' => 2]);
        Contact::create(['name' => 'Contato desativado', 'link' => 'mailto:antigo@giromoto.com.br', 'type' => 'email', 'status' => 'inactive', 'order' => 1]);
        Contact::create(['name' => '(11) 4000-4000', 'link' => 'tel:+551140004000', 'type' => 'phone', 'status' => 'active', 'order' => 1]);

        $this->actingAs($this->user('Dono'));

        $response = $this->get(route('help'))->assertOk();
        $response->assertSeeInOrder(['(11) 4000-4000', 'suporte@giromoto.com.br']);
        $response->assertSee('tel:+551140004000', false);
        $response->assertDontSee('Contato desativado');
    }
}
