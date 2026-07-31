<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    /** Build a fake Socialite provider whose user() returns the given identity. */
    private function fakeGoogleUser(string $id, string $email, string $name, ?string $avatar): void
    {
        $socialiteUser = Mockery::mock(SocialiteUser::class);
        $socialiteUser->shouldReceive('getId')->andReturn($id);
        $socialiteUser->shouldReceive('getEmail')->andReturn($email);
        $socialiteUser->shouldReceive('getName')->andReturn($name);
        $socialiteUser->shouldReceive('getAvatar')->andReturn($avatar);

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    public function test_callback_creates_user_logs_in_and_provisions_profile(): void
    {
        $this->fakeGoogleUser('g-123', 'novo@gmail.com', 'Novo Motoboy', 'https://x/avatar.png');

        $this->get(route('google.callback'))
            ->assertRedirect(route('shifts.index'));

        $this->assertAuthenticated();

        $user = User::where('email', 'novo@gmail.com')->first();
        $this->assertNotNull($user);
        $this->assertSame('g-123', $user->google_id);
        $this->assertNull($user->password, 'usuário Google não tem senha local');
        $this->assertSame('courier', $user->profile->role, 'profile provisionado pelo observer');
        $this->assertSame('https://x/avatar.png', $user->profile->photo_url);
    }

    public function test_callback_links_existing_email_account_without_duplicating(): void
    {
        $existing = User::create([
            'name' => 'Ana',
            'email' => 'ana@gmail.com',
            'password' => 'secret123',
        ]);

        $this->fakeGoogleUser('g-ana', 'ana@gmail.com', 'Ana', null);

        $this->get(route('google.callback'))
            ->assertRedirect(route('shifts.index'));

        $this->assertAuthenticatedAs($existing->fresh());
        $this->assertDatabaseCount('users', 1);
        $this->assertSame('g-ana', $existing->fresh()->google_id);
    }

    public function test_callback_failure_redirects_to_login_with_notice(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andThrow(new \RuntimeException('invalid state'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('notice');

        $this->assertGuest();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_redirect_endpoint_sends_user_to_google(): void
    {
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('redirect')->andReturn(redirect('https://accounts.google.com/o/oauth2/auth'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->get(route('google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/auth');
    }
}
