<?php

namespace Tests\Feature;

use App\Livewire\Chats\Index as ChatsIndex;
use App\Livewire\Chats\Show as ChatsShow;
use App\Livewire\Notifications\Page as NotificationsPage;
use App\Models\Application;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Shift;
use App\Models\User;
use App\Support\Partnerships;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ChatFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function user(string $name = 'User'): User
    {
        return User::create([
            'name' => $name,
            'email' => strtolower($name).uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
    }

    protected function shift(User $creator, array $overrides = []): Shift
    {
        return Shift::create(array_merge([
            'creator_id' => $creator->id, 'creator_role' => 'business',
            'venue' => 'Pizzaria X', 'region' => 'Centro', 'address' => 'Rua A, 1',
            'date' => now()->addDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12,
            'venue_type' => 'pizzaria', 'expected_volume' => 'moderado',
            'benefits' => [], 'accepted_vehicles' => ['moto'], 'requires_own_bag' => false,
            'couriers_needed' => 1, 'status' => 'available', 'lat' => 0, 'lng' => 0,
        ], $overrides));
    }

    public function test_accept_candidate_creates_chat_reserves_and_notifies(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($creator);
        Livewire::test(ChatsIndex::class)
            ->call('acceptCandidate', $shift->id, $courier->id)
            ->assertRedirect();

        $this->assertDatabaseHas('applications', [
            'shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted',
        ]);
        $fresh = $shift->fresh();
        $this->assertSame('reserved', $fresh->status);
        $this->assertSame($courier->id, $fresh->reserved_by);
        $this->assertDatabaseHas('chats', ['shift_id' => $shift->id]);
        // ShiftObserver notifies the accepted courier.
        $this->assertTrue(Notification::where('user_id', $courier->id)->where('type', 'turno')->exists());
    }

    public function test_decline_removes_application(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($creator);
        Livewire::test(ChatsIndex::class)
            ->call('requestDecline', $shift->id, $courier->id)
            ->call('confirmDecline');

        $this->assertDatabaseMissing('applications', ['shift_id' => $shift->id, 'user_id' => $courier->id]);
    }

    public function test_partnership_confirmation_fills_shift_when_both_confirm(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);
        $chat = Partnerships::accept($shift->load('applications'), $courier->id); // accepted + reserved + chat

        // Creator confirms first → not filled yet.
        $this->actingAs($creator);
        Livewire::test(ChatsShow::class, ['id' => $chat->id])->call('confirmPartnership');
        $this->assertSame('reserved', $shift->fresh()->status);

        // Courier confirms → both confirmed → filled.
        $this->actingAs($courier);
        Livewire::test(ChatsShow::class, ['id' => $chat->id])->call('confirmPartnership');

        $this->assertSame('filled', $shift->fresh()->status);
        $this->assertDatabaseHas('applications', [
            'shift_id' => $shift->id, 'user_id' => $courier->id, 'confirmed' => true,
        ]);
        $this->assertSame(
            2,
            Notification::where('type', 'turno')->where('title', 'Parceria confirmada!')->count()
        );
    }

    public function test_send_message_persists_and_notifies_recipient(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);

        $this->actingAs($courier);
        Livewire::test(ChatsShow::class, ['id' => $chat->id])
            ->set('body', 'Olá, tudo certo?')
            ->call('send')
            ->assertSet('body', '');

        $this->assertDatabaseHas('messages', ['chat_id' => $chat->id, 'author_id' => $courier->id]);
        $this->assertTrue(
            Notification::where('user_id', $creator->id)->where('type', 'mensagem')->exists()
        );
    }

    public function test_non_participant_cannot_open_chat(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $outsider = $this->user('Estranho');
        $shift = $this->shift($creator);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);

        $this->actingAs($outsider)
            ->get(route('chats.show', $chat->id))
            ->assertForbidden();
    }

    public function test_notifications_render_and_mark_read(): void
    {
        $user = $this->user('Dono');
        Notification::create([
            'user_id' => $user->id, 'type' => 'sistema', 'title' => 'Bem-vindo', 'description' => 'Olá!',
        ]);

        $this->actingAs($user);
        Livewire::test(NotificationsPage::class)
            ->assertSee('Bem-vindo')
            ->call('markAllRead');

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'read' => true]);
    }

    public function test_pages_render(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);

        $this->actingAs($creator);
        $this->get(route('chats.index'))->assertOk()->assertSee('Parcerias');
        $this->get(route('chats.show', $chat->id))->assertOk();
        $this->get(route('notifications'))->assertOk()->assertSee('Notificações');
    }
}
