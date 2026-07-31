<?php

namespace Tests\Feature;

use App\Events\MessageSent;
use App\Events\NotificationReceived;
use App\Livewire\Chats\Show as ChatsShow;
use App\Models\Application;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;
use Tests\TestCase;

class RealtimeBroadcastTest extends TestCase
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

    protected function shift(User $creator): Shift
    {
        return Shift::create([
            'creator_id' => $creator->id, 'creator_role' => 'business',
            'venue' => 'Pizzaria X', 'region' => 'Centro', 'address' => 'Rua A, 1',
            'date' => now()->addDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12,
            'venue_type' => 'pizzaria', 'expected_volume' => 'moderado',
            'benefits' => [], 'accepted_vehicles' => ['moto'], 'requires_own_bag' => false,
            'couriers_needed' => 1, 'status' => 'available', 'lat' => 0, 'lng' => 0,
        ]);
    }

    public function test_sending_message_broadcasts_to_chat_and_notifies_recipient(): void
    {
        Event::fake([MessageSent::class, NotificationReceived::class]);

        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);

        $this->actingAs($courier);
        Livewire::test(ChatsShow::class, ['id' => $chat->id])
            ->set('body', 'Chegando em 10 min')
            ->call('send');

        Event::assertDispatched(MessageSent::class, fn (MessageSent $e) => $e->message->chat_id === $chat->id
            && $e->message->author_id === $courier->id);

        // The recipient (creator) gets a realtime notification.
        Event::assertDispatched(NotificationReceived::class, fn (NotificationReceived $e) => $e->notification->user_id === $creator->id
            && $e->notification->type === 'mensagem');
    }

    public function test_message_event_targets_private_chat_channel_with_payload(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);
        $message = Message::create(['chat_id' => $chat->id, 'author_id' => $creator->id, 'body' => 'Oi']);

        $event = new MessageSent($message);

        $this->assertSame('private-chat.'.$chat->id, $event->broadcastOn()->name);
        $this->assertSame('message.sent', $event->broadcastAs());

        $payload = $event->broadcastWith();
        $this->assertSame($message->id, $payload['id']);
        $this->assertSame($chat->id, $payload['chat_id']);
        $this->assertSame($creator->id, $payload['author_id']);
        $this->assertNotNull($payload['created_at']);
    }

    public function test_notification_event_targets_private_user_channel(): void
    {
        $user = $this->user('Dono');
        $notification = \App\Models\Notification::create([
            'user_id' => $user->id, 'type' => 'sistema', 'title' => 'Oi', 'description' => 'teste',
        ]);

        $event = new NotificationReceived($notification);

        $this->assertSame('private-user.'.$user->id, $event->broadcastOn()->name);
        $this->assertSame('notification.received', $event->broadcastAs());
        $this->assertSame('sistema', $event->broadcastWith()['type']);
    }

    public function test_new_application_broadcasts_notification_to_shift_creator(): void
    {
        Event::fake([NotificationReceived::class]);

        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);

        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        Event::assertDispatched(NotificationReceived::class, fn (NotificationReceived $e) => $e->notification->user_id === $creator->id
            && $e->notification->type === 'vaga');
    }

    public function test_chat_channel_is_restricted_to_participants(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $outsider = $this->user('Estranho');
        $shift = $this->shift($creator);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);

        // This is exactly the gate the `chat.{chatId}` channel uses.
        $this->assertTrue($chat->hasParticipant($creator->id));
        $this->assertTrue($chat->hasParticipant($courier->id));
        $this->assertFalse($chat->hasParticipant($outsider->id));
    }
}
