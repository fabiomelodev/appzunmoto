<?php

namespace Tests\Feature;

use App\Livewire\Menu;
use App\Livewire\ProfilePage;
use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
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
            ->call('updateEmail')
            ->assertHasNoErrors();

        Livewire::test(Settings::class)
            ->set('newPassword', 'brandnew123')
            ->set('passwordConfirmation', 'brandnew123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('brandnew123', $user->fresh()->password));
    }

    public function test_account_pages_render(): void
    {
        $this->actingAs($this->user());

        $this->get(route('menu'))->assertOk()->assertSee('Meu Perfil');
        $this->get(route('profile'))->assertOk()->assertSee('Dados pessoais');
        $this->get(route('settings'))->assertOk()->assertSee('Aparência');
    }
}
