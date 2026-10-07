<?php

namespace Tests\Feature;

use App\Livewire\ChatUnreadBadge;
use App\Livewire\Chats\Index as ChatsIndex;
use App\Livewire\Chats\Show as ChatsShow;
use App\Models\Application;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/** Per-conversation read markers and the WhatsApp-style unread counters built on them. */
class ChatUnreadTest extends TestCase
{
    use RefreshDatabase;

    protected function user(string $name = 'User'): User
    {
        return User::create(['name' => $name, 'email' => strtolower($name).uniqid().'@test.dev', 'password' => 'secret123']);
    }

    protected function shift(User $creator, array $overrides = []): Shift
    {
        return Shift::create(array_merge([
            'creator_id' => $creator->id, 'creator_role' => 'business', 'venue' => 'Pizzaria X', 'region' => 'Centro',
            'address' => 'Rua A, 1', 'date' => now('America/Sao_Paulo')->addDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12, 'accepted_vehicles' => ['moto'],
            'requires_own_bag' => false, 'couriers_needed' => 1, 'status' => 'available', 'lat' => 0, 'lng' => 0,
        ], $overrides));
    }

    /** @return array{0: Chat, 1: User, 2: User} chat, creator, courier (interested) */
    protected function chat(array $shiftOverrides = []): array
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, $shiftOverrides);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        return [Chat::findOrCreateBetween($shift->id, $creator->id, $courier->id), $creator, $courier];
    }

    protected function say(Chat $chat, User $author, string $body = 'oi'): void
    {
        $message = Message::create(['chat_id' => $chat->id, 'author_id' => $author->id, 'body' => $body]);
        // created_at defaults to the DB clock, which Carbon::setTestNow() can't move.
        $message->forceFill(['created_at' => now()])->saveQuietly();
    }

    public function test_counts_only_messages_from_the_other_person_after_the_read_marker(): void
    {
        [$chat, $creator, $courier] = $this->chat();
        $this->say($chat, $creator);
        $this->say($chat, $creator);
        $this->say($chat, $courier, 'minha própria mensagem não conta');

        $this->assertSame(2, Chat::unreadCountsFor($courier->id)[$chat->id]);
        $this->assertSame(1, Chat::unreadCountsFor($creator->id)[$chat->id]);

        $chat->markReadBy($courier->id);
        $this->assertArrayNotHasKey($chat->id, Chat::unreadCountsFor($courier->id)->all());
        // Reading is per person: the creator's own unread is untouched.
        $this->assertSame(1, Chat::unreadCountsFor($creator->id)[$chat->id]);
    }

    public function test_new_messages_after_reading_count_again(): void
    {
        [$chat, $creator, $courier] = $this->chat();
        $this->say($chat, $creator);
        Carbon::setTestNow(now()->addMinute());
        $chat->markReadBy($courier->id);

        Carbon::setTestNow(now()->addMinute());
        $this->say($chat, $creator);
        $this->say($chat, $creator);

        $this->assertSame(2, Chat::unreadCountsFor($courier->id)[$chat->id]);
        Carbon::setTestNow();
    }

    public function test_counts_do_not_depend_on_chat_notifications_being_enabled(): void
    {
        [$chat, $creator, $courier] = $this->chat();
        UserSetting::updateOrCreate(['user_id' => $courier->id], ['notify_chat' => false]);

        $this->say($chat, $creator);

        $this->assertSame(1, Chat::unreadCountsFor($courier->id)[$chat->id]);
    }

    public function test_opening_the_conversation_marks_it_read(): void
    {
        [$chat, $creator, $courier] = $this->chat();
        $this->say($chat, $creator);
        $this->say($chat, $creator);

        $this->actingAs($courier);
        Livewire::test(ChatsShow::class, ['id' => $chat->id]);

        $this->assertArrayNotHasKey($chat->id, Chat::unreadCountsFor($courier->id)->all());
    }

    public function test_messages_arriving_while_the_chat_is_open_are_read_as_they_land(): void
    {
        [$chat, $creator, $courier] = $this->chat();
        $this->actingAs($courier);
        $component = Livewire::test(ChatsShow::class, ['id' => $chat->id]);

        Carbon::setTestNow(now()->addMinute());
        $this->say($chat, $creator, 'chegou com a conversa aberta');
        $component->call('onMessage');
        Carbon::setTestNow();

        $this->assertArrayNotHasKey($chat->id, Chat::unreadCountsFor($courier->id)->all());
    }

    public function test_interessadas_tab_shows_the_count_and_highlights_the_row(): void
    {
        [$chat, $creator, $courier] = $this->chat(['venue' => 'Com Mensagens']);
        $other = $this->shift($creator, ['venue' => 'Sem Mensagens']);
        Application::create(['shift_id' => $other->id, 'user_id' => $courier->id, 'status' => 'interested']);
        foreach (range(1, 3) as $i) {
            $this->say($chat, $creator);
        }

        $this->actingAs($courier);
        $html = Livewire::test(ChatsIndex::class)->call('setTab', 'interessadas')->html();

        $this->assertSame(1, substr_count($html, 'aria-label="Abrir conversa"'));
        $this->assertMatchesRegularExpression('/>\s*3\s*<\/span>/', $html);
        $this->assertStringContainsString('border-primary/50 bg-primary/5', $html);
        $this->assertSame(1, substr_count($html, 'font-bold">Com Mensagens') + substr_count($html, 'font-bold">Sem Mensagens'), 'only the unread row is bold');
    }

    public function test_publicadas_tab_shows_unread_per_shift_and_per_candidate(): void
    {
        [$chat, $creator, $courier] = $this->chat(['venue' => 'Minha Vaga']);
        $this->say($chat, $courier);
        $this->say($chat, $courier);

        $this->actingAs($creator);
        $html = Livewire::test(ChatsIndex::class)->call('setTab', 'publicadas')->assertSee('Minha Vaga')->html();
        $this->assertStringContainsString('title="Mensagens não lidas"', $html);
        $this->assertMatchesRegularExpression('/title="Mensagens não lidas"[^>]*>\s*2\s*<\/span>/', $html);

        // Expanded: the candidate's own "Conversar com…" button carries the count too.
        $expanded = Livewire::test(ChatsIndex::class)->call('setTab', 'publicadas')
            ->call('toggleShift', Shift::first()->id)->html();
        // (Livewire puts <!--[if BLOCK]--> comments around @if blocks, hence the (?:…) tolerance.)
        $this->assertMatchesRegularExpression('/Conversar com Moto(?:\s|<!--.*?-->)*<span[^>]*>\s*2\s*<\/span>/s', $expanded);
    }

    public function test_nav_badge_counts_candidates_waiting_for_the_author(): void
    {
        $author = $this->user('Dono');
        $c1 = $this->user('Moto1');
        $c2 = $this->user('Moto2');
        $c3 = $this->user('Moto3');
        $future = now('America/Sao_Paulo')->addDays(3)->toDateString();
        $open = $this->shift($author, ['venue' => 'Aberta', 'date' => $future, 'couriers_needed' => 3]);
        $full = $this->shift($author, ['venue' => 'Cheia', 'date' => $future, 'status' => 'reserved']);
        $over = $this->shift($author, ['venue' => 'Passada', 'date' => now('America/Sao_Paulo')->subDays(2)->toDateString()]);
        Application::create(['shift_id' => $open->id, 'user_id' => $c1->id, 'status' => 'interested']);
        Application::create(['shift_id' => $open->id, 'user_id' => $c2->id, 'status' => 'interested']);
        Application::create(['shift_id' => $full->id, 'user_id' => $c1->id, 'status' => 'accepted', 'confirmed' => true]);
        Application::create(['shift_id' => $full->id, 'user_id' => $c3->id, 'status' => 'interested']); // no slot left
        Application::create(['shift_id' => $over->id, 'user_id' => $c1->id, 'status' => 'interested']);  // already over

        $this->actingAs($author);
        $this->assertSame(2, Livewire::test(ChatUnreadBadge::class)->instance()->total);

        // Accepting one clears it from the count, and the page tells the badge to refresh.
        Livewire::test(ChatsIndex::class)
            ->call('acceptCandidate', $open->id, $c1->id)
            ->assertDispatched('partnerships-changed');
        $this->assertSame(1, Livewire::test(ChatUnreadBadge::class)->instance()->total);

        Livewire::test(ChatsIndex::class)
            ->call('requestDecline', $open->id, $c2->id)->call('confirmDecline')
            ->assertDispatched('partnerships-changed');
        $this->assertSame(0, Livewire::test(ChatUnreadBadge::class)->instance()->total);
    }

    public function test_nav_badge_counts_accepted_shifts_waiting_for_the_courier_to_confirm(): void
    {
        $author = $this->user('Dono');
        $courier = $this->user('Moto');
        $future = now('America/Sao_Paulo')->addDays(3)->toDateString();
        $waiting = $this->shift($author, ['venue' => 'A Confirmar', 'date' => $future, 'status' => 'reserved']);
        $done = $this->shift($author, ['venue' => 'Ja Confirmada', 'date' => now('America/Sao_Paulo')->addDays(5)->toDateString(), 'status' => 'filled']);
        $pending = $this->shift($author, ['venue' => 'So Interesse', 'date' => $future]);
        $over = $this->shift($author, ['venue' => 'Ja Passou', 'date' => now('America/Sao_Paulo')->subDays(2)->toDateString()]);
        Application::create(['shift_id' => $waiting->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => false, 'confirmations' => [$author->id]]);
        Application::create(['shift_id' => $done->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true]);
        Application::create(['shift_id' => $pending->id, 'user_id' => $courier->id, 'status' => 'interested']);
        Application::create(['shift_id' => $over->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => false]);

        $this->actingAs($courier);
        $this->assertSame(1, Livewire::test(ChatUnreadBadge::class)->instance()->total);

        // Messages add on top.
        $chat = Chat::findOrCreateBetween($waiting->id, $author->id, $courier->id);
        $this->say($chat, $author);
        $this->assertSame(2, Livewire::test(ChatUnreadBadge::class)->instance()->total);

        // Confirming from the shift page clears the pending item.
        Livewire::test(\App\Livewire\Shifts\Show::class, ['id' => $waiting->id])
            ->call('confirmPartnership')
            ->assertDispatched('partnerships-changed');
        $this->assertSame(1, Livewire::test(ChatUnreadBadge::class)->instance()->total);
    }

    public function test_parcerias_tabs_show_the_pending_counts(): void
    {
        $author = $this->user('Dono');
        $c1 = $this->user('Moto1');
        $c2 = $this->user('Moto2');
        $future = now('America/Sao_Paulo')->addDays(3)->toDateString();
        $open = $this->shift($author, ['venue' => 'Aberta', 'date' => $future]);
        Application::create(['shift_id' => $open->id, 'user_id' => $c1->id, 'status' => 'interested']);
        Application::create(['shift_id' => $open->id, 'user_id' => $c2->id, 'status' => 'interested']);
        // Multi-courier shift: one confirmed (listed under "Em andamento"), a candidate still waiting.
        $multi = $this->shift($author, ['venue' => 'Dupla', 'date' => $future, 'couriers_needed' => 2, 'status' => 'reserved']);
        Application::create(['shift_id' => $multi->id, 'user_id' => $c1->id, 'status' => 'accepted', 'confirmed' => true]);
        Application::create(['shift_id' => $multi->id, 'user_id' => $c2->id, 'status' => 'interested']);

        $this->actingAs($author);
        $counts = Livewire::test(ChatsIndex::class)->instance()->tabCounts;
        $this->assertEquals(['publicadas' => 2, 'interessadas' => 0, 'andamento' => 1], $counts);

        $waiting = $this->shift($c1, ['venue' => 'Do Moto1', 'date' => $future, 'status' => 'reserved']);
        Application::create(['shift_id' => $waiting->id, 'user_id' => $author->id, 'status' => 'accepted', 'confirmed' => false]);
        $this->assertSame(1, Livewire::test(ChatsIndex::class)->instance()->tabCounts['interessadas']);

        $html = Livewire::test(ChatsIndex::class)->html();
        $this->assertMatchesRegularExpression('/Vagas publicadas<!--.*?-->\s*<span[^>]*>\s*2\s*<\/span>|Vagas publicadas\s*<span[^>]*>\s*2\s*<\/span>/s', $html);
    }

    public function test_nav_badge_totals_every_unread_conversation_and_caps_at_99(): void
    {
        [$chatA, $creator, $courier] = $this->chat();
        $second = $this->shift($creator);
        Application::create(['shift_id' => $second->id, 'user_id' => $courier->id, 'status' => 'interested']);
        $chatB = Chat::findOrCreateBetween($second->id, $creator->id, $courier->id);
        $this->say($chatA, $creator);
        $this->say($chatB, $creator);
        $this->say($chatB, $creator);

        $this->actingAs($courier);
        Livewire::test(ChatUnreadBadge::class)->assertSee('3');

        $this->actingAs($this->user('Ninguem'));
        Livewire::test(ChatUnreadBadge::class)->assertDontSeeHtml('rounded-full bg-primary');

        foreach (range(1, 100) as $i) {
            $this->say($chatA, $creator);
        }
        $this->actingAs($courier);
        Livewire::test(ChatUnreadBadge::class)->assertSee('99+');
    }

    public function test_the_app_layout_renders_the_badge_on_the_parcerias_nav_entry(): void
    {
        [$chat, $creator, $courier] = $this->chat();
        $this->say($chat, $creator);

        $this->actingAs($courier);
        $this->get(route('shifts.index'))->assertOk()->assertSeeLivewire(ChatUnreadBadge::class);
    }

    public function test_existing_chats_start_fully_read_after_the_migration(): void
    {
        [$chat, , $courier] = $this->chat();
        $this->assertNull($chat->fresh()->user_a_read_at, 'new chats begin unread-tracking from scratch');

        // What the migration's backfill does to chats that already exist:
        \Illuminate\Support\Facades\DB::table('chats')->update(['user_a_read_at' => now(), 'user_b_read_at' => now()]);
        $this->assertEmpty(Chat::unreadCountsFor($courier->id)->all());
    }
}
