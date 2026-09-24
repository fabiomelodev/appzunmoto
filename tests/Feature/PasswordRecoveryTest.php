<?php

namespace Tests\Feature;

use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class PasswordRecoveryTest extends TestCase
{
    use RefreshDatabase;

    protected function user(string $email = 'maria@test.dev'): User
    {
        return User::create(['name' => 'Maria', 'email' => $email, 'password' => 'senha-antiga']);
    }

    public function test_login_links_to_password_recovery_only_when_signing_in(): void
    {
        Livewire::test(Login::class)
            ->assertSee('Esqueci minha senha')
            ->assertSeeHtml(route('password.request'))
            ->call('setMode', 'signup')
            ->assertDontSee('Esqueci minha senha');
    }

    public function test_recovery_pages_render_for_guests(): void
    {
        $this->get(route('password.request'))->assertOk()->assertSee('Recuperar senha');
        $this->get(route('password.reset', 'qualquer-token').'?email=maria@test.dev')->assertOk()->assertSee('Nova senha');
    }

    public function test_it_sends_the_reset_link_to_an_existing_account(): void
    {
        Notification::fake();
        $user = $this->user();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'maria@test.dev')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('Confira seu e-mail');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_the_answer_is_the_same_for_an_unknown_email(): void
    {
        Notification::fake();
        $this->user();

        Livewire::test(ForgotPassword::class)
            ->set('email', 'ninguem@test.dev')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertSet('sent', true)
            ->assertSee('Se existir uma conta');

        Notification::assertNothingSent();
    }

    public function test_the_email_is_in_portuguese_and_links_to_the_reset_screen(): void
    {
        Notification::fake();
        $user = $this->user();

        Livewire::test(ForgotPassword::class)->set('email', 'maria@test.dev')->call('submit');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);
            $this->assertSame('Redefinição de senha — ZunMoto', $mail->subject);
            $this->assertSame('Redefinir senha', $mail->actionText);
            $this->assertStringContainsString('/reset-password/'.$notification->token, $mail->actionUrl);
            $this->assertStringContainsString('email='.urlencode('maria@test.dev'), $mail->actionUrl);

            return true;
        });
    }

    public function test_an_account_created_with_google_can_also_recover_by_email(): void
    {
        Notification::fake();
        $user = User::create(['name' => 'Goog', 'email' => 'goog@test.dev', 'google_id' => 'g-1']);
        $this->assertNull($user->password);

        Livewire::test(ForgotPassword::class)->set('email', 'goog@test.dev')->call('submit');
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $token = Password::createToken($user);
        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'goog@test.dev')
            ->set('password', 'nova-senha')
            ->set('passwordConfirmation', 'nova-senha')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertTrue(Hash::check('nova-senha', $user->fresh()->password));
    }

    public function test_requests_are_rate_limited_per_email_and_ip(): void
    {
        Notification::fake();
        $this->user();

        $component = Livewire::test(ForgotPassword::class)->set('email', 'maria@test.dev');
        for ($i = 0; $i < 5; $i++) {
            $component->call('submit')->assertHasNoErrors();
        }

        $component->call('submit')->assertHasErrors('email');
    }

    public function test_the_forgot_form_validates_the_email(): void
    {
        Livewire::test(ForgotPassword::class)->set('email', '')->call('submit')->assertHasErrors('email');
        Livewire::test(ForgotPassword::class)->set('email', 'nao-e-email')->call('submit')->assertHasErrors('email');
    }

    public function test_a_valid_token_sets_the_new_password_and_sends_the_user_to_login(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'maria@test.dev')
            ->set('password', 'nova-senha')
            ->set('passwordConfirmation', 'nova-senha')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $user->refresh();
        $this->assertTrue(Hash::check('nova-senha', $user->password));
        $this->assertFalse(Hash::check('senha-antiga', $user->password));

        // The login screen tells the user it worked.
        $this->assertSame('Senha redefinida com sucesso! Entre com a nova senha.', session('notice'));
        $this->assertTrue(auth()->attempt(['email' => 'maria@test.dev', 'password' => 'nova-senha']));
    }

    public function test_a_token_can_only_be_used_once(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'maria@test.dev')->set('password', 'primeira-nova')->set('passwordConfirmation', 'primeira-nova')
            ->call('submit')->assertHasNoErrors();

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'maria@test.dev')->set('password', 'segunda-nova')->set('passwordConfirmation', 'segunda-nova')
            ->call('submit')->assertHasErrors('email');

        $this->assertTrue(Hash::check('primeira-nova', $user->fresh()->password));
    }

    public function test_an_invalid_token_or_wrong_email_changes_nothing(): void
    {
        $user = $this->user();
        $this->user('outra@test.dev');
        $token = Password::createToken($user);

        // Bad token.
        Livewire::test(ResetPassword::class, ['token' => 'token-falso'])
            ->set('email', 'maria@test.dev')->set('password', 'nova-senha')->set('passwordConfirmation', 'nova-senha')
            ->call('submit')
            ->assertHasErrors('email')
            ->assertSee('inválido ou expirou');

        // Right token, someone else's e-mail.
        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'outra@test.dev')->set('password', 'nova-senha')->set('passwordConfirmation', 'nova-senha')
            ->call('submit')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('senha-antiga', $user->fresh()->password));
    }

    public function test_an_expired_token_is_rejected(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);
        $this->travel(61)->minutes();

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'maria@test.dev')->set('password', 'nova-senha')->set('passwordConfirmation', 'nova-senha')
            ->call('submit')
            ->assertHasErrors('email');

        $this->assertTrue(Hash::check('senha-antiga', $user->fresh()->password));
    }

    public function test_the_new_password_is_validated(): void
    {
        $user = $this->user();
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'maria@test.dev')->set('password', '123')->set('passwordConfirmation', '123')
            ->call('submit')->assertHasErrors('password');

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'maria@test.dev')->set('password', 'nova-senha')->set('passwordConfirmation', 'diferente')
            ->call('submit')->assertHasErrors('password');

        $this->assertTrue(Hash::check('senha-antiga', $user->fresh()->password));
    }

    public function test_recovering_the_account_signs_it_out_of_every_device(): void
    {
        $user = $this->user();
        $other = $this->user('outra@test.dev');
        foreach ([[$user, 's-1'], [$user, 's-2'], [$other, 's-3']] as [$owner, $id]) {
            DB::table('sessions')->insert([
                'id' => $id, 'user_id' => $owner->id, 'ip_address' => '127.0.0.1',
                'user_agent' => 'test', 'payload' => '', 'last_activity' => time(),
            ]);
        }
        $token = Password::createToken($user);

        Livewire::test(ResetPassword::class, ['token' => $token])
            ->set('email', 'maria@test.dev')->set('password', 'nova-senha')->set('passwordConfirmation', 'nova-senha')
            ->call('submit')->assertHasNoErrors();

        $this->assertSame(0, DB::table('sessions')->where('user_id', $user->id)->count());
        $this->assertSame(1, DB::table('sessions')->where('user_id', $other->id)->count(), 'other accounts are untouched');
    }

    public function test_logged_in_users_are_sent_away_from_the_recovery_screens(): void
    {
        $this->actingAs($this->user());

        $this->get(route('password.request'))->assertRedirect();
    }
}
