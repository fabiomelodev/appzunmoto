<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Livewire\Chats\Show as ChatsShow;
use App\Livewire\Notifications\Page as NotificationsPage;
use App\Livewire\Settings;
use App\Models\Chat;
use App\Models\ImpersonationLog;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Shift;
use App\Models\User;
use App\Support\Impersonation;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Admin "acessar como usuário": session switch, audit trail, way back and the safeguards. */
class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin'.uniqid().'@test.dev', 'password' => 'secret123', 'is_admin' => true]);
    }

    private function user(string $name = 'Maria', array $attrs = [], bool $onboarded = true): User
    {
        $user = User::create(['name' => $name, 'email' => strtolower($name).uniqid().'@test.dev', 'password' => 'secret123'] + $attrs);
        $user->profile()->update(['onboarded_at' => $onboarded ? now() : null, 'vehicle' => 'moto', 'district' => 'Centro', 'city' => 'São Paulo']);

        return $user;
    }

    public function test_the_users_list_action_logs_in_as_the_user_and_goes_to_the_app(): void
    {
        $admin = $this->admin();
        $target = $this->user('Maria');
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        Livewire::test(ListUsers::class)
            ->callTableAction('impersonate', $target)
            ->assertRedirect(route('shifts.index'));

        $this->assertSame($target->id, auth()->id());
        $this->assertSame($admin->id, session(Impersonation::SESSION_ADMIN));
        $log = ImpersonationLog::firstOrFail();
        $this->assertSame([$admin->id, $target->id], [$log->admin_id, $log->user_id]);
        $this->assertNull($log->ended_at);
    }

    public function test_an_account_created_with_google_can_be_accessed_too(): void
    {
        $admin = $this->admin();
        $google = User::create(['name' => 'Ana Google', 'email' => 'ana@gmail.com', 'google_id' => 'g-123']);
        $google->profile()->update(['onboarded_at' => now(), 'vehicle' => 'moto']);
        $this->assertNull($google->password, 'a Google account has no local password');

        $this->actingAs($admin);
        $this->assertTrue(Impersonation::start($admin, $google));
        $this->assertSame($google->id, auth()->id());

        $this->get(route('shifts.index'))->assertOk()->assertSee('Acessando como')->assertSee('Ana Google');
    }

    public function test_a_user_who_has_not_finished_the_signup_lands_on_the_onboarding(): void
    {
        $admin = $this->admin();
        $target = $this->user('Novo', onboarded: false);
        $this->actingAs($admin);
        Filament::setCurrentPanel('admin');

        Livewire::test(ListUsers::class)->callTableAction('impersonate', $target)->assertRedirect(route('onboarding'));
    }

    public function test_only_admins_can_start_and_never_on_admins_themselves_or_nested(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $regular = $this->user('Comum');
        $target = $this->user('Alvo');

        $this->assertFalse(Impersonation::canImpersonate($regular, $target), 'a regular user is not an admin');
        $this->assertFalse(Impersonation::canImpersonate($admin, $admin), 'not on yourself');
        $this->assertFalse(Impersonation::canImpersonate($admin, $otherAdmin), 'not on another admin');
        $this->assertFalse(Impersonation::start($regular, $target));
        $this->assertGuest();

        $this->actingAs($admin);
        $this->assertTrue(Impersonation::start($admin, $target));
        // Already inside an account: no nesting (the original admin would be lost).
        $this->assertFalse(Impersonation::start($admin, $this->user('Outro')));
        $this->assertSame($target->id, auth()->id());
        $this->assertSame(1, ImpersonationLog::count());
    }

    public function test_the_panel_action_is_hidden_for_regular_users(): void
    {
        $this->actingAs($this->user('Comum'));
        Filament::setCurrentPanel('admin');

        $this->assertFalse(Impersonation::canImpersonate(auth()->user(), $this->user('Alvo')));
        $this->get('/admin/users')->assertForbidden();
    }

    public function test_the_banner_shows_only_while_accessing_and_sair_goes_back_to_the_admin(): void
    {
        $admin = $this->admin();
        $target = $this->user('Maria');
        $this->actingAs($target);
        $this->get(route('shifts.index'))->assertOk()->assertDontSee('Voltar ao painel');

        $this->actingAs($admin);
        Impersonation::start($admin, $target);
        $this->get(route('shifts.index'))->assertOk()->assertSee('Acessando como')->assertSee('Voltar ao painel');

        // POST /logout leaves the impersonation instead of ending the session.
        $this->post(route('logout'))->assertRedirect('/admin/users');
        $this->assertSame($admin->id, auth()->id());
        $this->assertFalse(Impersonation::isActive());
        $this->assertNotNull(ImpersonationLog::first()->ended_at);

        $this->get(route('shifts.index'))->assertOk()->assertDontSee('Voltar ao painel');
        // Back as a normal session: logging out really logs out.
        $this->post(route('logout'))->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_looking_at_the_account_does_not_mark_anything_as_read_for_the_user(): void
    {
        $admin = $this->admin();
        $owner = $this->user('Dono');
        $target = $this->user('Maria');
        $shift = Shift::create([
            'creator_id' => $owner->id, 'creator_role' => 'business', 'venue' => 'Loja', 'region' => 'Centro', 'address' => 'Rua A',
            'date' => now()->addDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00', 'daily_rate' => 100,
            'delivery_fee_min' => 5, 'delivery_fee_max' => 8, 'venue_type' => 'pizzaria', 'expected_volume' => 'moderado',
            'accepted_vehicles' => ['moto'], 'status' => 'available', 'lat' => 0, 'lng' => 0,
        ]);
        $chat = Chat::findOrCreateBetween($shift->id, $owner->id, $target->id);
        Message::create(['chat_id' => $chat->id, 'author_id' => $owner->id, 'body' => 'Oi Maria']);
        $note = Notification::create(['user_id' => $target->id, 'type' => 'mensagem', 'title' => 'Nova mensagem', 'payload' => ['chat_id' => $chat->id]]);

        $this->actingAs($admin);
        Impersonation::start($admin, $target);

        Livewire::test(ChatsShow::class, ['id' => $chat->id])->assertSee('Oi Maria');
        Livewire::test(NotificationsPage::class)->call('markAllRead')->call('open', $note->id);

        $this->assertFalse($note->fresh()->read, 'notification still unread for the real user');
        $this->assertSame(1, Chat::unreadCountsFor($target->id)->sum(), 'message still unread for the real user');

        // Without impersonation the very same visit does mark them read.
        Impersonation::leave();
        $this->actingAs($target);
        Livewire::test(ChatsShow::class, ['id' => $chat->id]);
        $this->assertTrue($note->fresh()->read);
        $this->assertSame(0, Chat::unreadCountsFor($target->id)->sum());
    }

    public function test_credentials_and_push_devices_are_off_limits_while_accessing(): void
    {
        $admin = $this->admin();
        $target = $this->user('Maria');
        $this->actingAs($admin);
        Impersonation::start($admin, $target);

        Livewire::test(Settings::class)
            ->set('newEmail', 'novo@test.dev')->call('updateEmail')
            ->assertDispatched('toast', message: 'Indisponível ao acessar como outro usuário.', type: 'error')
            ->set('newPassword', 'outra-senha')->set('passwordConfirmation', 'outra-senha')->set('currentPassword', 'secret123')
            ->call('updatePassword')
            ->assertDispatched('toast', message: 'Indisponível ao acessar como outro usuário.', type: 'error')
            // The admin's browser must not become a push device of the user.
            ->call('subscribeToPush', ['endpoint' => 'https://push.example/abc', 'keys' => ['p256dh' => 'k', 'auth' => 'a']]);

        $fresh = $target->fresh();
        $this->assertNotSame('novo@test.dev', $fresh->email);
        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('secret123', $fresh->password));
        $this->assertSame(0, $fresh->pushSubscriptions()->count());
    }
}
