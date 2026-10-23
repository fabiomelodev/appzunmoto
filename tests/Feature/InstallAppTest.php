<?php

namespace Tests\Feature;

use App\Models\AppInstall;
use App\Models\User;
use App\Support\Impersonation;
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
    public function test_the_manifest_points_at_itself_so_chrome_can_tell_when_the_app_is_installed(): void
    {
        $response = $this->get('/manifest.webmanifest')->assertOk();
        $this->assertStringContainsString('application/manifest+json', $response->headers->get('Content-Type'));

        $manifest = $response->json();
        $this->assertSame('ZunMoto', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertSame([['platform' => 'webapp', 'url' => url('/manifest.webmanifest')]], $manifest['related_applications']);
        $this->assertStringStartsWith('http', $manifest['related_applications'][0]['url']);
        $this->assertArrayNotHasKey('prefer_related_applications', $manifest);
        $this->assertFileDoesNotExist(public_path('manifest.webmanifest'), 'the static copy would shadow the route');
    }

    public function test_the_server_records_where_the_app_is_installed_once_per_device(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        $this->postJson(route('pwa.installed'), ['device' => 'device-abc-123', 'platform' => 'android', 'standalone' => false])
            ->assertOk()->assertJson(['recorded' => true]);
        $first = AppInstall::firstOrFail();
        $this->assertSame('android', $first->platform);
        $this->assertNull($first->last_standalone_at);

        // Same device again, now running as the installed app: same row, standalone moment recorded.
        $this->travel(2)->days();
        $this->postJson(route('pwa.installed'), ['device' => 'device-abc-123', 'platform' => 'android', 'standalone' => true])->assertOk();
        $this->assertSame(1, AppInstall::count());
        $again = AppInstall::first();
        $this->assertEquals($first->installed_at->timestamp, $again->installed_at->timestamp, 'first install moment is kept');
        $this->assertGreaterThan($first->last_seen_at->timestamp, $again->last_seen_at->timestamp);
        $this->assertNotNull($again->last_standalone_at);

        // Another device of the same user counts separately.
        $this->postJson(route('pwa.installed'), ['device' => 'notebook-xyz-789', 'platform' => 'desktop'])->assertOk();
        $this->assertSame(2, AppInstall::where('user_id', $user->id)->count());
    }

    public function test_the_install_report_is_validated_and_needs_a_session(): void
    {
        // A logged-out request is sent to the login (the script treats that redirect as "not reported") and nothing is stored.
        $guest = $this->postJson(route('pwa.installed'), ['device' => 'device-abc-123', 'platform' => 'android']);
        $this->assertContains($guest->getStatusCode(), [302, 401]);
        $this->assertSame(0, AppInstall::count());

        $this->actingAs($this->user());
        // Invalid payloads (this app renders validation errors as redirects for non-/api routes, hence 302 or 422).
        foreach ([
            ['device' => 'x', 'platform' => 'android'],
            ['device' => 'device-abc-123', 'platform' => 'windows-phone'],
            ['device' => 'bad id <script>', 'platform' => 'ios'],
        ] as $payload) {
            $this->assertContains($this->postJson(route('pwa.installed'), $payload)->getStatusCode(), [302, 422]);
        }
        $this->assertSame(0, AppInstall::count());
    }

    public function test_an_admin_inside_a_user_account_does_not_register_their_own_device_for_that_user(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'adm'.uniqid().'@test.dev', 'password' => 'secret123', 'is_admin' => true]);
        $target = $this->user();
        $this->actingAs($admin);
        Impersonation::start($admin, $target);

        $this->postJson(route('pwa.installed'), ['device' => 'admin-device-1', 'platform' => 'desktop', 'standalone' => true])
            ->assertOk()->assertJson(['recorded' => false]);

        $this->assertSame(0, AppInstall::count());
    }

    public function test_the_script_asks_chrome_whether_the_app_is_installed_and_reports_installs(): void
    {
        $js = file_get_contents(resource_path('js/pwa-install.js'));

        foreach (['getInstalledRelatedApps', "'/pwa/installed'", 'reportInstall()', 'mr-device-id', "redirect: 'manual'"] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
    }
}