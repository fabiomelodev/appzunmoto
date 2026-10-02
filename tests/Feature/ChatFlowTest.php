<?php

namespace Tests\Feature;

use App\Livewire\Chats\Index as ChatsIndex;
use App\Livewire\Chats\Show as ChatsShow;
use App\Livewire\Notifications\Page as NotificationsPage;
use App\Models\Application;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Review;
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
            ->call('acceptCandidate', $shift->id, $courier->id);

        $this->assertDatabaseHas('applications', [
            'shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted',
        ]);
        $fresh = $shift->fresh();
        $this->assertSame('reserved', $fresh->status);
        $this->assertSame($courier->id, $fresh->reserved_by);
        $this->assertDatabaseHas('chats', ['shift_id' => $shift->id]);
        // Partnerships::accept notifies the accepted courier.
        $this->assertTrue(Notification::where('user_id', $courier->id)->where('type', 'turno')->exists());

        // Accepting also registers the creator's own confirmation — no separate
        // "Confirmar Parceria" click needed on their side. The courier still
        // has to confirm independently before the shift is actually filled.
        $app = Application::where('shift_id', $shift->id)->where('user_id', $courier->id)->first();
        $this->assertContains($creator->id, $app->confirmations);
        $this->assertFalse((bool) $app->confirmed);
        $this->assertNotSame('filled', $fresh->status);
    }

    public function test_accept_candidate_fills_the_shift_when_courier_already_confirmed(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        Partnerships::accept($shift->load('applications'), $courier->id);
        // The courier confirms their side first (e.g. from their own chat screen).
        Partnerships::confirm($shift->refresh(), $courier->id);

        $this->actingAs($creator);
        Livewire::test(ChatsIndex::class)->call('acceptCandidate', $shift->id, $courier->id);

        $app = Application::where('shift_id', $shift->id)->where('user_id', $courier->id)->first();
        $this->assertTrue((bool) $app->confirmed);
        $this->assertSame('filled', $shift->fresh()->status);
    }

    public function test_each_accepted_courier_is_notified_in_multi_courier_shift(): void
    {
        $creator = $this->user('Dono');
        $c1 = $this->user('Moto1');
        $c2 = $this->user('Moto2');
        $shift = $this->shift($creator, ['couriers_needed' => 2]);
        Application::create(['shift_id' => $shift->id, 'user_id' => $c1->id, 'status' => 'interested']);
        Application::create(['shift_id' => $shift->id, 'user_id' => $c2->id, 'status' => 'interested']);

        Partnerships::accept($shift->load('applications'), $c1->id);
        Partnerships::accept($shift->refresh()->load('applications'), $c2->id);

        // BOTH accepted couriers — not just the first — get the "accepted" notification.
        foreach ([$c1, $c2] as $courier) {
            $this->assertTrue(
                Notification::where('user_id', $courier->id)
                    ->where('type', 'turno')
                    ->where('title', 'Você foi aceito em uma vaga!')
                    ->exists(),
                "courier {$courier->name} deveria ser notificado",
            );
        }
    }

    public function test_courier_defaults_to_the_interessadas_tab_and_creator_to_publicadas(): void
    {
        $courier = $this->user('Moto');
        $this->actingAs($courier);
        Livewire::test(ChatsIndex::class)->assertSet('tab', 'interessadas');

        $business = $this->user('Dono');
        $business->profile->update(['role' => 'business']);
        $this->actingAs($business);
        Livewire::test(ChatsIndex::class)->assertSet('tab', 'publicadas');
    }

    public function test_publicadas_tab_lists_shifts_as_open_or_closed(): void
    {
        $creator = $this->user('Dono');
        $this->shift($creator, ['venue' => 'Vaga Aberta']);
        $this->shift($creator, ['venue' => 'Vaga Encerrada', 'status' => 'filled']);

        $this->actingAs($creator);
        Livewire::test(ChatsIndex::class)
            ->call('setTab', 'publicadas')
            ->assertSee('Vagas abertas')
            ->assertSee('Vaga Aberta')
            ->assertSee('Vagas encerradas')
            ->assertSee('Vaga Encerrada');
    }

    public function test_interessadas_tab_lists_only_still_pending_applications(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $pending = $this->shift($creator, ['venue' => 'Aguardando Analise']);
        $accepted = $this->shift($creator, ['venue' => 'Ja Aceita']);
        Application::create(['shift_id' => $pending->id, 'user_id' => $courier->id, 'status' => 'interested']);
        Application::create(['shift_id' => $accepted->id, 'user_id' => $courier->id, 'status' => 'accepted']);

        $this->actingAs($courier);
        Livewire::test(ChatsIndex::class)
            ->call('setTab', 'interessadas')
            ->assertSee('Aguardando Analise')
            ->assertDontSee('Ja Aceita');
    }

    public function test_interested_courier_can_reply_in_an_existing_chat_but_not_start_one(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);
        $this->actingAs($courier);

        // No chat yet: no button, and openChat() must not create one.
        Livewire::test(\App\Livewire\Shifts\Show::class, ['id' => $shift->id])
            ->assertDontSee('Abrir conversa')
            ->call('openChat')
            ->assertNoRedirect();
        $this->assertDatabaseCount('chats', 0);

        // The creator writes first → the courier gets the button and can open it.
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);
        Livewire::test(\App\Livewire\Shifts\Show::class, ['id' => $shift->id])
            ->assertSee('Abrir conversa')
            ->assertSeeHtml(route('chats.show', $chat->id))
            ->call('openChat')
            ->assertRedirect(route('chats.show', $chat->id));
    }


    public function test_a_vaga_id_query_param_opens_the_publicadas_tab_for_that_shift(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['venue' => 'Vaga Alvo']);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($creator);
        $this->get(route('chats.index', ['vagaId' => $shift->id]))
            ->assertOk()
            ->assertSee('Vagas publicadas')
            ->assertSee('Moto');
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

    public function test_creator_reviews_courier_from_chat_and_second_submit_is_locked(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['date' => now()->subDay()->toDateString()]);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true]);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);

        $this->actingAs($creator);
        Livewire::test(ChatsShow::class, ['id' => $chat->id])
            ->assertSee('Vaga concluída')
            ->set('rating', 5)
            ->set('comment', 'Primeira avaliação')
            ->call('submitReview');

        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $creator->id, 'target_id' => $courier->id,
            'rating' => 5, 'comment' => 'Primeira avaliação',
        ]);

        // Panel now shows the locked state instead of the review form.
        Livewire::test(ChatsShow::class, ['id' => $chat->id])
            ->assertDontSee('Vaga concluída')
            ->assertSee('Avaliação enviada');

        // Even if triggered directly, a second submission must not change the review.
        Livewire::test(ChatsShow::class, ['id' => $chat->id])
            ->set('rating', 1)
            ->set('comment', 'Tentativa de sobrescrever')
            ->call('submitReview');

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $creator->id, 'target_id' => $courier->id,
            'rating' => 5, 'comment' => 'Primeira avaliação',
        ]);
    }

    public function test_courier_reviews_the_creator_from_chat(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['date' => now()->subDay()->toDateString()]);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true]);
        $chat = Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id);

        $this->actingAs($courier);
        Livewire::test(ChatsShow::class, ['id' => $chat->id])
            ->assertSee('Vaga concluída')
            ->set('rating', 4)
            ->set('comment', 'Local organizado')
            ->call('submitReview');

        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $courier->id, 'target_id' => $creator->id,
            'target_role' => 'business', 'rating' => 4,
        ]);
        $this->publishDueReviews();
        $this->assertSame(1, (int) $creator->fresh()->profile->business_total_reviews);

        Livewire::test(ChatsShow::class, ['id' => $chat->id])
            ->assertDontSee('Vaga concluída')
            ->assertSee('Avaliação enviada');
    }

    public function test_chat_review_requires_a_confirmed_partnership_and_a_finished_shift(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');

        // Finished, but the courier never confirmed the partnership.
        $unconfirmed = $this->shift($creator, ['date' => now()->subDay()->toDateString()]);
        Application::create(['shift_id' => $unconfirmed->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => false]);
        $chatA = Chat::findOrCreateBetween($unconfirmed->id, $creator->id, $courier->id);

        // Confirmed, but the shift hasn't happened yet.
        $upcoming = $this->shift($creator);
        Application::create(['shift_id' => $upcoming->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true]);
        $chatB = Chat::findOrCreateBetween($upcoming->id, $creator->id, $courier->id);

        foreach ([$creator, $courier] as $user) {
            $this->actingAs($user);
            foreach ([$chatA, $chatB] as $chat) {
                Livewire::test(ChatsShow::class, ['id' => $chat->id])
                    ->assertDontSee('Vaga concluída')
                    ->set('rating', 5)
                    ->call('submitReview');
            }
        }

        $this->assertDatabaseCount('reviews', 0);
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
