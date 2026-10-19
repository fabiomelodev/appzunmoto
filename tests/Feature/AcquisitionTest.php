<?php

namespace Tests\Feature;

use App\Filament\Pages\AcquisitionReport;
use App\Livewire\Auth\Login;
use App\Livewire\Onboarding;
use App\Models\AcquisitionChannel;
use App\Models\Application;
use App\Models\Shift;
use App\Models\User;
use App\Support\Acquisition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

/** "De onde vêm os usuários": ?utm_source in the QR codes → cookie → account, plus the admin report. */
class AcquisitionTest extends TestCase
{
    use RefreshDatabase;

    private function signUp(string $email = 'novo@test.dev', array $cookies = []): User
    {
        Livewire::withCookies($cookies)->test(Login::class)
            ->set('mode', 'signup')
            ->set('name', 'Novo Usuário')->set('email', $email)
            ->set('password', 'secret123')->set('passwordConfirmation', 'secret123')
            ->call('submit')
            ->assertHasNoErrors();

        return User::where('email', $email)->firstOrFail();
    }

    public function test_seeded_channels_cover_the_printed_qr_codes(): void
    {
        $this->assertEqualsCanonicalizing(
            ['tv', 'panfleto', 'instagram', 'google-ads', 'youtube-ads'],
            AcquisitionChannel::pluck('slug')->all(),
        );
        $this->assertSame(rtrim(config('app.url'), '/').'?utm_source=tv', AcquisitionChannel::where('slug', 'tv')->first()->url());
    }

    public function test_utm_source_is_normalised_and_untrusted_characters_are_dropped(): void
    {
        $this->assertSame('tv', Acquisition::normalize(' TV '));
        $this->assertSame('google-ads', Acquisition::normalize('Google-Ads'));
        $this->assertSame('scriptalert1script', Acquisition::normalize('<script>alert(1)</script>'));
        $this->assertSame('or11--', Acquisition::normalize("' OR 1=1; --  "), 'only [a-z0-9_-] survive');
        $this->assertSame(64, strlen(Acquisition::normalize(str_repeat('a', 200))));
        $this->assertSame('', Acquisition::normalize(null));
    }

    public function test_the_source_survives_the_redirect_of_a_guest_to_the_login(): void
    {
        // The printed QR code opens the site root; a guest is redirected to /login and the query string is lost.
        $this->get('/?utm_source=TV')
            ->assertRedirect()
            ->assertCookie(Acquisition::COOKIE, 'tv');
    }

    public function test_the_first_source_wins_and_junk_is_ignored(): void
    {
        // Already attributed to the TV: a later visit from an ad doesn't overwrite it.
        $this->withCookie(Acquisition::COOKIE, 'tv')->get('/login?utm_source=instagram')->assertCookieMissing(Acquisition::COOKIE);

        $this->get('/login?utm_source=')->assertCookieMissing(Acquisition::COOKIE);
        $this->get('/login?utm_source=%3C%3E')->assertCookieMissing(Acquisition::COOKIE);
        $this->get('/login')->assertCookieMissing(Acquisition::COOKIE);
    }

    public function test_a_messy_source_is_sanitised_rather_than_rejected(): void
    {
        $this->get('/login?utm_source=Panfleto%20')->assertCookie(Acquisition::COOKIE, 'panfleto');
    }

    public function test_email_signup_is_attributed_to_the_registered_channel(): void
    {
        $user = $this->signUp('tv@test.dev', [Acquisition::COOKIE => 'tv']);

        $this->assertSame('tv', $user->utm_source);
        $this->assertSame('TV', $user->acquisitionChannel->name);
    }

    public function test_unknown_or_inactive_sources_keep_the_raw_value_without_a_channel(): void
    {
        $unknown = $this->signUp('outro@test.dev', [Acquisition::COOKIE => 'radio-local']);
        $this->assertSame('radio-local', $unknown->utm_source);
        $this->assertNull($unknown->acquisition_channel_id);

        AcquisitionChannel::where('slug', 'panfleto')->update(['active' => false]);
        $inactive = $this->signUp('inativo@test.dev', [Acquisition::COOKIE => 'panfleto']);
        $this->assertSame('panfleto', $inactive->utm_source);
        $this->assertNull($inactive->acquisition_channel_id);
        $this->assertSame('Outro: panfleto', Acquisition::label($inactive->utm_source, null));
    }

    public function test_signup_without_a_source_is_direct(): void
    {
        $user = $this->signUp('direto@test.dev');

        $this->assertNull($user->utm_source);
        $this->assertNull($user->acquisition_channel_id);
        $this->assertSame('Direto / não informado', Acquisition::label(null, null));
    }

    public function test_google_signup_is_attributed_but_an_existing_account_is_not_touched(): void
    {
        $socialite = fn (string $id, string $email) => tap(Mockery::mock(SocialiteUser::class), function ($u) use ($id, $email) {
            $u->shouldReceive('getId')->andReturn($id);
            $u->shouldReceive('getEmail')->andReturn($email);
            $u->shouldReceive('getName')->andReturn('Alguém');
            $u->shouldReceive('getAvatar')->andReturn(null);
        });
        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialite('g-1', 'g1@gmail.com'), $socialite('g-2', 'old@gmail.com'));
        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

        $this->withCookie(Acquisition::COOKIE, 'tv')->get(route('google.callback'))->assertRedirect();
        $this->assertSame('tv', User::where('email', 'g1@gmail.com')->first()->utm_source);

        auth()->logout();
        User::create(['name' => 'Old', 'email' => 'old@gmail.com', 'password' => 'secret123']);
        $this->withCookie(Acquisition::COOKIE, 'panfleto')->get(route('google.callback'))->assertRedirect();
        $this->assertNull(User::where('email', 'old@gmail.com')->first()->utm_source, 'only brand-new accounts are attributed');
    }

    public function test_the_signup_asks_how_the_user_heard_about_it_and_saves_the_answer(): void
    {
        $user = User::create(['name' => 'Ana', 'email' => 'ana@test.dev', 'password' => 'secret123']);
        $user->profile()->update(['onboarded_at' => null]);
        $this->actingAs($user);

        $component = Livewire::test(Onboarding::class)
            ->assertSee('Como você conheceu o ZunMoto?')
            ->assertSee('Prefiro não informar')
            ->call('setHeardFrom', 'qualquer-coisa')
            ->assertSet('heardFrom', '')
            ->call('setHeardFrom', 'tv')
            ->assertSet('heardFrom', 'tv');

        $component->set('name', 'Ana Souza')->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')
            ->set('district', 'Centro')->set('city', 'São Paulo')
            ->call('submitAddress');

        $this->assertSame('tv', $user->fresh()->heard_from);
    }

    public function test_the_answer_is_optional(): void
    {
        $user = User::create(['name' => 'Bia', 'email' => 'bia@test.dev', 'password' => 'secret123']);
        $user->profile()->update(['onboarded_at' => null]);
        $this->actingAs($user);

        Livewire::test(Onboarding::class)
            ->set('name', 'Bia Souza')->set('phone', '(11) 99999-0000')
            ->set('birthDate', '10/05/1990')->set('cpf', '529.982.247-25')
            ->call('submitPersonalData')->assertHasNoErrors()
            ->set('district', 'Centro')->set('city', 'São Paulo')
            ->call('submitAddress')->assertHasNoErrors();

        $this->assertNull($user->fresh()->heard_from);
    }

    public function test_report_counts_signups_completed_and_active_users_per_channel(): void
    {
        $tv = AcquisitionChannel::where('slug', 'tv')->first();
        $mk = function (string $email, array $attrs, bool $onboarded = true) {
            $u = User::create(['name' => $email, 'email' => $email, 'password' => 'secret123'] + $attrs);
            $u->profile()->update(['onboarded_at' => $onboarded ? now() : null]);

            return $u;
        };

        $tvDone = $mk('tv1@test.dev', ['utm_source' => 'tv', 'acquisition_channel_id' => $tv->id, 'heard_from' => 'tv']);
        $mk('tv2@test.dev', ['utm_source' => 'tv', 'acquisition_channel_id' => $tv->id, 'heard_from' => 'indicacao'], onboarded: false);
        $mk('radio@test.dev', ['utm_source' => 'radio-local']);
        $mk('direto@test.dev', []);
        $mk('admin@test.dev', ['is_admin' => true, 'utm_source' => 'tv', 'acquisition_channel_id' => $tv->id]);
        // Someone who signed up a year ago is outside the default period.
        $old = $mk('antigo@test.dev', ['utm_source' => 'tv', 'acquisition_channel_id' => $tv->id]);
        $old->forceFill(['created_at' => now()->subYear()])->save();

        // The TV user used the app: applied to a shift.
        $owner = $mk('dono@test.dev', []);
        $shift = Shift::create([
            'creator_id' => $owner->id, 'creator_role' => 'business', 'venue' => 'Loja', 'region' => 'Centro',
            'address' => 'Rua A', 'date' => now()->addDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 100, 'delivery_fee_min' => 5, 'delivery_fee_max' => 8, 'venue_type' => 'pizzaria',
            'expected_volume' => 'moderado', 'accepted_vehicles' => ['moto'], 'status' => 'available', 'lat' => 0, 'lng' => 0,
        ]);
        Application::create(['shift_id' => $shift->id, 'user_id' => $tvDone->id, 'status' => 'interested']);

        $rows = AcquisitionReport::rows('30', 'channel')->keyBy('name');
        $this->assertSame(2, $rows['TV']['signups'], 'admins and users outside the period are not counted');
        $this->assertSame(1, $rows['TV']['completed']);
        $this->assertSame(1, $rows['TV']['active']);
        $this->assertSame('50% dos cadastros', $rows['TV']['completed_pct']);
        $this->assertSame(1, $rows['Outro: radio-local']['signups']);
        $this->assertSame(2, $rows['Direto / não informado']['signups']); // direto + dono
        $this->assertSame(5, $rows['Total']['signups']);
        $this->assertSame('TV', AcquisitionReport::rows('30', 'channel')->first()['name'], 'most sign-ups first');

        $asked = AcquisitionReport::rows('30', 'asked')->keyBy('name');
        $this->assertSame(1, $asked['TV']['signups']);
        $this->assertSame(1, $asked['Indicação de amigo ou conhecido']['signups']);
        $this->assertSame(3, $asked['Não respondeu']['signups']);

        $this->assertSame(6, AcquisitionReport::rows('all', 'channel')->keyBy('name')['Total']['signups'] - 0 + 0, 'the whole history');
    }

    public function test_admin_panel_has_the_channels_resource_and_the_report(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'adm@test.dev', 'password' => 'secret123', 'is_admin' => true]);
        $this->actingAs($admin);

        $this->get('/admin/acquisition-channels')->assertOk()->assertSee('TV')->assertSee('Panfleto')->assertSee('utm_source=tv', false);
        $this->get('/admin/acquisition-channels/create')->assertOk();
        $this->get('/admin/aquisicao')->assertOk()->assertSee('Aquisição');
        $this->get('/admin/users')->assertOk()->assertSee('Canal');

        // A regular user can't see any of it.
        $this->actingAs(User::create(['name' => 'Zé', 'email' => 'ze@test.dev', 'password' => 'secret123']));
        $this->get('/admin/aquisicao')->assertForbidden();
    }
}
