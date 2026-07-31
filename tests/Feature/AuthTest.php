<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Models\Profile;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_mode_logs_in_and_provisions_profile_and_settings(): void
    {
        Livewire::test(Login::class)
            ->set('testName', 'Maria')
            ->call('testLogin')
            ->assertRedirect(route('shifts.index'));

        $this->assertAuthenticated();
        $user = User::first();
        $this->assertNotNull($user->profile, 'profile auto-criado pelo observer');
        $this->assertSame('courier', $user->profile->role);
        $this->assertSame('Maria', $user->name);
        $this->assertInstanceOf(UserSetting::class, $user->settings);
    }

    public function test_signup_creates_user_and_profile_without_auto_login(): void
    {
        Livewire::test(Login::class)
            ->set('mode', 'signup')
            ->set('name', 'João Silva')
            ->set('birthDate', '10/05/1990')
            ->set('phone', '(11) 99999-0000')
            ->set('street', 'Av Paulista')
            ->set('number', '100')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->set('email', 'joao@test.dev')
            ->set('password', 'secret123')
            ->set('passwordConfirmation', 'secret123')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('mode', 'signin');

        $this->assertGuest();
        $user = User::where('email', 'joao@test.dev')->first();
        $this->assertNotNull($user);
        $this->assertSame('11999990000', $user->profile->phone);
        $this->assertSame('1990-05-10', $user->profile->birth_date->toDateString());
        $this->assertSame('Centro', $user->profile->district);
    }

    public function test_signup_rejects_mismatched_passwords(): void
    {
        Livewire::test(Login::class)
            ->set('mode', 'signup')
            ->set('name', 'João Silva')
            ->set('birthDate', '10/05/1990')
            ->set('phone', '(11) 99999-0000')
            ->set('street', 'Av Paulista')
            ->set('number', '100')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->set('email', 'joao2@test.dev')
            ->set('password', 'secret123')
            ->set('passwordConfirmation', 'different')
            ->call('submit')
            ->assertHasErrors('password');

        $this->assertNull(User::where('email', 'joao2@test.dev')->first());
    }

    public function test_signup_rejects_users_under_18(): void
    {
        Livewire::test(Login::class)
            ->set('mode', 'signup')
            ->set('name', 'Jovem Demais')
            ->set('birthDate', now()->subYears(17)->format('d/m/Y'))
            ->set('phone', '(11) 99999-0000')
            ->set('street', 'Av Paulista')
            ->set('number', '100')
            ->set('district', 'Centro')
            ->set('city', 'São Paulo')
            ->set('email', 'jovem@test.dev')
            ->set('password', 'secret123')
            ->set('passwordConfirmation', 'secret123')
            ->call('submit')
            ->assertHasErrors('birthDate');

        $this->assertNull(User::where('email', 'jovem@test.dev')->first(), 'menor de 18 não deve criar conta');
    }

    public function test_signin_authenticates_existing_user(): void
    {
        User::create(['name' => 'Ana', 'email' => 'ana@test.dev', 'password' => 'secret123']);

        Livewire::test(Login::class)
            ->set('email', 'ana@test.dev')
            ->set('password', 'secret123')
            ->call('submit')
            ->assertRedirect(route('shifts.index'));

        $this->assertAuthenticated();
    }

    public function test_signin_fails_with_wrong_password(): void
    {
        User::create(['name' => 'Ana', 'email' => 'ana2@test.dev', 'password' => 'secret123']);

        Livewire::test(Login::class)
            ->set('email', 'ana2@test.dev')
            ->set('password', 'wrongpass')
            ->call('submit')
            ->assertHasErrors('email');

        $this->assertGuest();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get('/shifts')->assertRedirect(route('login'));
    }
}
