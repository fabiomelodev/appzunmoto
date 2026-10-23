<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Instale o app do ZunMoto": the invitation/tutorial modal and the Menu entry (logic is client-side, in pwa-install.js). */
class InstallAppTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        $user = User::create(['name' => 'Maria', 'email' => 'maria'.uniqid().'@test.dev', 'password' => 'secret123']);
        $user->profile()->update(['onboarded_at' => now(), 'role' => 'courier', 'vehicle' => 'moto']);

        return $user;
    }

    public function test_the_app_pages_carry_the_install_tutorial_for_the_three_platforms(): void
    {
        $this->actingAs($this->user());

        $this->get(route('shifts.index'))
            ->assertOk()
            ->assertSee('Instale o app do ZunMoto')
            ->assertSee('Android')->assertSee('iPhone')->assertSee('Computador')
            ->assertSee('Adicionar à Tela de Início')   // iPhone
            ->assertSee('Instalar app')                  // Android
            ->assertSee('Instalar ZunMoto')              // desktop
            ->assertSee('Agora não');
    }

    public function test_the_menu_offers_installing_the_app_and_the_client_hides_it_once_installed(): void
    {
        $this->actingAs($this->user());

        $html = $this->get(route('menu'))->assertOk()->assertSee('Instalar o app')->getContent();

        // Shown only while not installed: the Alpine store flips `installed` (standalone / appinstalled / flag).
        $this->assertStringContainsString('x-show="! $store.install.installed"', $html);
    }

    public function test_the_login_screen_does_not_nag_with_the_install_modal(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('Instale o app do ZunMoto');
    }

    public function test_the_install_script_is_part_of_the_bundle(): void
    {
        $js = file_get_contents(resource_path('js/pwa-install.js'));

        // The pieces that make "install once, never ask again" work.
        foreach (['beforeinstallprompt', 'appinstalled', 'display-mode: standalone', 'navigator.standalone', "'mr-pwa-installed'"] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
        $this->assertStringContainsString("import './pwa-install.js'", file_get_contents(resource_path('js/app.js')));
    }
}
