<?php

namespace Tests\Feature;

use App\Livewire\Settings;
use App\Models\Application;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Shift;
use App\Models\User;
use App\Notifications\PushNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Livewire\Livewire;
use Tests\TestCase;

class WebPushTest extends TestCase
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

    public function test_subscribe_persists_a_push_subscription_for_the_user(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(Settings::class)
            ->call('subscribeToPush', [
                'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
                'keys' => ['p256dh' => 'public-key', 'auth' => 'auth-token'],
            ])
            ->assertDispatched('toast');

        $this->assertSame(1, $user->pushSubscriptions()->count());
        $this->assertSame('https://fcm.googleapis.com/fcm/send/abc123', $user->pushSubscriptions()->first()->endpoint);
    }

    public function test_subscribe_ignores_a_payload_without_an_endpoint(): void
    {
        $user = $this->user();
        $this->actingAs($user);

        Livewire::test(Settings::class)->call('subscribeToPush', ['keys' => ['p256dh' => 'x', 'auth' => 'y']]);

        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_unsubscribe_removes_the_matching_subscription(): void
    {
        $user = $this->user();
        $this->actingAs($user);
        $user->updatePushSubscription('https://fcm.googleapis.com/fcm/send/abc123', 'k', 'a');

        Livewire::test(Settings::class)
            ->call('unsubscribeFromPush', 'https://fcm.googleapis.com/fcm/send/abc123')
            ->assertDispatched('toast');

        $this->assertSame(0, $user->pushSubscriptions()->count());
    }

    public function test_new_interest_on_shift_sends_a_push_to_the_creator(): void
    {
        NotificationFacade::fake();

        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);

        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        NotificationFacade::assertSentTo($creator, PushNotification::class);
    }

    public function test_new_chat_message_sends_a_push_to_the_recipient(): void
    {
        NotificationFacade::fake();

        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);

        Message::create(['chat_id' => $chat->id, 'author_id' => $creator->id, 'body' => 'Oi, tudo bem?']);

        NotificationFacade::assertSentTo($courier, PushNotification::class);
    }

    public function test_push_is_not_sent_when_the_user_disabled_that_notification_type(): void
    {
        NotificationFacade::fake();

        $creator = $this->user('Dono');
        $creator->settings()->update(['notify_shifts' => false]);
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);

        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        NotificationFacade::assertNotSentTo($creator, PushNotification::class);
    }

    public function test_push_notification_targets_the_shift_url(): void
    {
        $creator = $this->user('Dono');
        $shift = $this->shift($creator);

        $notification = new PushNotification('Novo candidato', 'Alguém se candidatou', route('shifts.show', $shift->id));
        $message = $notification->toWebPush($creator, $notification);

        $this->assertSame(route('shifts.show', $shift->id), $message->toArray()['data']['url']);
    }
}
