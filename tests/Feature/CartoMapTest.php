<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartoMapTest extends TestCase
{
    use RefreshDatabase;

    public function test_carto_config_component_injects_the_key_only_when_configured(): void
    {
        config(['services.carto.key' => 'test-carto-key']);
        $html = view('components.carto-config')->render();
        $this->assertStringContainsString('window.__CARTO_API_KEY__', $html);
        $this->assertStringContainsString('test-carto-key', $html);

        config(['services.carto.key' => null]);
        $html = view('components.carto-config')->render();
        $this->assertStringNotContainsString('__CARTO_API_KEY__', $html);
    }

    public function test_map_page_renders_regardless_of_carto_key(): void
    {
        $user = User::create(['name' => 'Dono', 'email' => 'dono'.uniqid().'@test.dev', 'password' => 'secret123']);
        $this->actingAs($user);

        config(['services.carto.key' => null]);
        $this->get(route('map'))->assertOk()->assertSee('vagas no mapa');
    }
}
