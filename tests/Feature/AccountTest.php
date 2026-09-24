<?php

namespace Tests\Feature;

use App\Livewire\Menu;
use App\Livewire\ProfilePage;
use App\Livewire\Settings;
use App\Models\Review;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class AccountTest extends TestCase
{
    use RefreshDatabase;

    protected function user(): User
    {
        return User::create([
            'name' => 'Maria',
            'email' => 'maria'.uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
    }

    public function test_menu_renders_and_logs_out(): void
    {
        $this->actingAs($this->user());

        Livewire::test(Menu::class)
            ->assertSee('Meu Perfil')
            ->assertSee('Configurações')
            ->call('logout')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_menu_shows_admin_panel_link_only_to_admins(): void
    {
        $this->actingAs($this->user());

        Livewire::test(Menu::class)
            ->assertDontSee('Painel Administrativo');

        $admin = $this->user();
        $admin->forceFill(['is_admin' => true])->save();
        $this->actingAs($admin);

        Livewire::test(Menu::class)
            ->assertSee('Painel Administrativo')
            ->assertSee(route('filament.admin.pages.dashboard'));
    }

    public function test_menu_shows_vehicle_link_only_to_courier(): void
    {
        $courier = $this->user();
        $this->actingAs($courier);
        Livewire::test(Menu::class)->assertSee('Veículo e Documentação');

        $owner = $this->user();
        $owner->profile()->update(['role' => 'business']);
        $this->actingAs($owner);
        Livewire::test(Menu::class)->assertDontSee('Veículo e Documentação');
    }

    public function test_uploading_a_photo_stores_it_publicly_and_updates_profile_url(): void
    {
        Storage::fake('public');
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('photo', UploadedFile::fake()->image('avatar.jpg'))
            ->assertDispatched('toast');

        $profile = $user->fresh()->profile;
        $this->assertNotNull($profile->photo_url, 'photo_url deve ser preenchido após o upload');
        $this->assertStringContainsString('/storage/avatars/', $profile->photo_url);

        $path = 'avatars/'.$user->id.'.jpg';
        Storage::disk('public')->assertExists($path);
    }

    public function test_profile_save_updates_profile_and_user_name(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('name', 'Maria Souza')
            ->set('cpf', '529.982.247-25')
            ->set('phone', '(11) 99999-0000')
            ->set('city', 'São Paulo - SP')
            ->set('bio', 'Entregadora veloz')
            ->set('hasBag', true)
            ->call('save')
            ->assertDispatched('toast');

        $profile = $user->fresh()->profile;
        $this->assertSame('Maria Souza', $profile->name);
        $this->assertSame('52998224725', $profile->cpf);
        $this->assertSame('11999990000', $profile->phone);
        $this->assertSame('Entregadora veloz', $profile->bio);
        $this->assertTrue((bool) $profile->has_bag);
        $this->assertSame('Maria Souza', $user->fresh()->name);
    }

    public function test_profile_save_blocks_a_calendar_invalid_birth_date(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('name', 'Maria Souza')
            ->set('birthDate', '31/02/1990') // fevereiro não tem dia 31
            ->call('save')
            ->assertHasErrors('birthDate')
            ->assertNotDispatched('toast');

        $this->assertNull($user->fresh()->profile->birth_date);
    }

    public function test_profile_save_allows_an_empty_birth_date(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('name', 'Maria Souza')
            ->set('birthDate', '')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');
    }

    public function test_profile_save_blocks_an_invalid_cpf(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('name', 'Maria Souza')
            ->set('cpf', '111.111.111-11')
            ->call('save')
            ->assertHasErrors('cpf')
            ->assertNotDispatched('toast');

        $this->assertNull($user->fresh()->profile->cpf);
    }

    public function test_profile_save_accepts_a_valid_cpf(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('name', 'Maria Souza')
            ->set('cpf', '529.982.247-25')
            ->call('save')
            ->assertHasNoErrors()
            ->assertDispatched('toast');

        $this->assertSame('52998224725', $user->fresh()->profile->cpf);
    }

    public function test_profile_save_blocks_a_cpf_already_used_by_another_account(): void
    {
        $other = $this->user();
        $other->profile()->update(['cpf' => '52998224725']);

        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(ProfilePage::class)
            ->set('name', 'Maria Souza')
            ->set('cpf', '529.982.247-25')
            ->call('save')
            ->assertHasErrors('cpf')
            ->assertNotDispatched('toast');

        $this->assertNull($user->fresh()->profile->cpf);
    }

    public function test_profile_page_hides_courier_only_sections_for_business(): void
    {
        $courier = $this->user();
        $this->actingAs($courier);
        Livewire::test(ProfilePage::class)
            ->assertSee('Avaliações')
            ->assertSee('Endereço')
            ->assertSee('Equipamentos disponíveis');

        $owner = $this->user();
        $owner->profile()->update(['role' => 'business']);
        $this->actingAs($owner);
        Livewire::test(ProfilePage::class)
            ->assertSee('Avaliações') // couriers review establishments too
            ->assertDontSee('Equipamentos disponíveis')
            ->assertSee('Meus Endereços');
    }

    public function test_profile_page_shows_the_rating_of_the_active_role_only(): void
    {
        $user = $this->user();
        $shift = Shift::create([
            'creator_id' => $user->id, 'creator_role' => 'business', 'venue' => 'Pizzaria X', 'region' => 'Centro',
            'address' => 'Rua A, 1', 'date' => now()->subDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12, 'accepted_vehicles' => ['moto'],
            'requires_own_bag' => false, 'couriers_needed' => 1, 'status' => 'filled', 'lat' => 0, 'lng' => 0,
        ]);
        $author = $this->user();
        Review::create(['shift_id' => $shift->id, 'author_id' => $author->id, 'target_id' => $user->id, 'target_role' => 'courier', 'rating' => 3, 'comment' => 'Comentário de motoboy']);
        Review::create(['shift_id' => $shift->id, 'author_id' => $this->user()->id, 'target_id' => $user->id, 'target_role' => 'business', 'rating' => 5, 'comment' => 'Comentário de estabelecimento']);

        $this->actingAs($user);
        Livewire::test(ProfilePage::class)
            ->assertSeeHtml('text-foreground">3,0</span>')
            ->assertDontSeeHtml('text-foreground">5,0</span>')
            ->set('tab', 'reviews')
            ->assertSee('Comentário de motoboy')
            ->assertDontSee('Comentário de estabelecimento');

        $user->profile()->update(['role' => 'business']);
        $this->actingAs($user->fresh()); // drop the profile relation cached with the old role
        Livewire::test(ProfilePage::class)
            ->assertSeeHtml('text-foreground">5,0</span>')
            ->assertDontSeeHtml('text-foreground">3,0</span>')
            ->set('tab', 'reviews')
            ->assertSee('Comentário de estabelecimento')
            ->assertDontSee('Comentário de motoboy');
    }

    public function test_profile_page_has_no_verification_or_status_badges(): void
    {
        $this->actingAs($this->user());

        Livewire::test(ProfilePage::class)
            ->assertDontSee('Verificado')
            ->assertDontSee('Conta nova')
            ->assertDontSee('Entregador ativo')
            ->assertDontSee('Entregador experiente');
    }

    public function test_settings_notifications_persist(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->set('notifyShifts', false)
            ->set('notifyEmail', true);

        $this->assertDatabaseHas('user_settings', [
            'user_id' => $user->id, 'notify_shifts' => false, 'notify_email' => true,
        ]);
    }

    public function test_settings_persists_theme(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(Settings::class)->call('persistTheme', 'urbano');

        $this->assertDatabaseHas('user_settings', ['user_id' => $user->id, 'theme' => 'urbano']);
    }

    public function test_settings_updates_email_and_password(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->set('newEmail', 'novo'.uniqid().'@test.dev')
            ->set('currentPassword', 'secret123')
            ->call('updateEmail')
            ->assertHasNoErrors();

        Livewire::test(Settings::class)
            ->set('newPassword', 'brandnew123')
            ->set('passwordConfirmation', 'brandnew123')
            ->set('currentPassword', 'secret123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('brandnew123', $user->fresh()->password));
    }

    public function test_email_change_requires_correct_current_password(): void
    {
        $user = $this->user();
        $original = $user->email;
        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->set('newEmail', 'hacker@test.dev')
            ->set('currentPassword', 'wrong-password')
            ->call('updateEmail')
            ->assertHasErrors('currentPassword');

        $this->assertSame($original, $user->fresh()->email);
    }

    public function test_unlink_google_disconnects_when_password_is_set(): void
    {
        $user = $this->user();
        $user->update(['google_id' => 'g-linked']);
        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->call('unlinkGoogle')
            ->assertDispatched('toast');

        $this->assertNull($user->fresh()->google_id);
    }

    public function test_unlink_google_is_blocked_without_a_password(): void
    {
        // Google-only account: no local password to fall back on.
        $user = User::create([
            'name' => 'Goog',
            'email' => 'goog'.uniqid().'@test.dev',
            'google_id' => 'g-only',
        ]);
        $this->assertNull($user->password);
        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->call('unlinkGoogle')
            ->assertSet('passwordOpen', true)
            ->assertDispatched('toast');

        $this->assertSame('g-only', $user->fresh()->google_id, 'não desvincula sem senha');
    }

    public function test_google_only_user_can_define_a_password_without_the_current_one(): void
    {
        $user = User::create([
            'name' => 'Goog',
            'email' => 'goog'.uniqid().'@test.dev',
            'google_id' => 'g-only',
        ]);
        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->set('newPassword', 'firstpass1')
            ->set('passwordConfirmation', 'firstpass1')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('firstpass1', $user->fresh()->password));
    }

    public function test_account_pages_render(): void
    {
        $this->actingAs($this->user());

        $this->get(route('menu'))->assertOk()->assertSee('Meu Perfil');
        $this->get(route('profile'))->assertOk()->assertSee('Dados pessoais');
        $this->get(route('settings'))->assertOk()->assertSee('Aparência');
    }

    public function test_pages_link_the_pwa_manifest_and_icons(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('manifest.webmanifest', false);

        $this->actingAs($this->user());

        $this->get(route('shifts.index'))
            ->assertOk()
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('apple-touch-icon', false);
    }

    public function test_pwa_manifest_file_has_the_required_fields(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true);

        $this->assertSame('ZunMoto', $manifest['name']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['icons']);
        $this->assertTrue(collect($manifest['icons'])->contains(fn ($icon) => $icon['sizes'] === '512x512'));

        foreach ($manifest['icons'] as $icon) {
            $this->assertFileExists(public_path(ltrim($icon['src'], '/')));
        }
    }
}
