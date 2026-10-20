<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Benefit;
use App\Models\Chat;
use App\Models\ExpectedVolume;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\VenueType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** users:purge — cleans test accounts, keeps the listed e-mails and admins, repairs what cascades don't. */
class PurgeUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        VenueType::create(['name' => 'Pizzaria', 'slug' => 'pizzaria', 'status' => 'active']);
        ExpectedVolume::create(['name' => 'Moderado', 'slug' => 'moderado', 'status' => 'active']);
        Benefit::create(['name' => 'Lanche', 'slug' => 'lanche', 'icon' => 'sandwich', 'status' => 'active']);
    }

    private function user(string $email, array $attrs = []): User
    {
        return User::create(['name' => ucfirst(strtok($email, '@')), 'email' => $email, 'password' => 'secret123'] + $attrs);
    }

    private function shift(User $creator, array $overrides = []): Shift
    {
        return Shift::create(array_merge([
            'creator_id' => $creator->id, 'creator_role' => 'business', 'venue' => 'Loja '.uniqid(), 'region' => 'Centro', 'address' => 'Rua A',
            'date' => now()->addDays(3)->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00', 'daily_rate' => 100,
            'delivery_fee_min' => 5, 'delivery_fee_max' => 8, 'venue_type' => 'pizzaria', 'expected_volume' => 'moderado',
            'accepted_vehicles' => ['moto'], 'status' => 'available', 'couriers_needed' => 1, 'lat' => 0, 'lng' => 0,
        ], $overrides));
    }

    public function test_it_refuses_to_run_without_a_keep_list_or_with_an_unknown_email(): void
    {
        $this->user('real@test.dev');
        $this->user('teste@test.dev');

        $this->artisan('users:purge', ['--force' => true])->assertFailed();
        $this->artisan('users:purge', ['--keep' => ['real@test.dev', 'digitado-errado@test.dev'], '--force' => true])
            ->expectsOutputToContain('digitado-errado@test.dev')
            ->assertFailed();

        $this->assertSame(2, User::count(), 'a typo in the list must never delete anyone');
    }

    public function test_dry_run_reports_and_deletes_nothing(): void
    {
        $keep = $this->user('real@test.dev');
        $this->user('teste1@test.dev');
        $this->user('teste2@test.dev');
        $this->shift($keep);

        $this->artisan('users:purge', ['--keep' => ['real@test.dev']])
            ->expectsOutputToContain('2 serão apagados')
            ->expectsOutputToContain('SIMULAÇÃO')
            ->assertSuccessful();

        $this->assertSame(3, User::count());
    }

    public function test_the_keep_file_and_comma_separated_lists_work_and_admins_are_always_kept(): void
    {
        $a = $this->user('a@test.dev');
        $b = $this->user('b@test.dev');
        $admin = $this->user('admin@test.dev', ['is_admin' => true]);
        $this->user('teste@test.dev');

        $file = tempnam(sys_get_temp_dir(), 'keep');
        file_put_contents($file, "A@TEST.DEV\n\n  b@test.dev  \n");

        $this->artisan('users:purge', ['--keep-file' => $file, '--force' => true])
            ->expectsConfirmation('Apagar DEFINITIVAMENTE 1 usuário(s) e tudo o que é deles? Isso não pode ser desfeito.', 'yes')
            ->assertSuccessful();
        unlink($file);

        $this->assertEqualsCanonicalizing([$a->id, $b->id, $admin->id], User::pluck('id')->all());
    }

    public function test_declining_the_confirmation_deletes_nothing(): void
    {
        $this->user('real@test.dev');
        $this->user('teste@test.dev');

        $this->artisan('users:purge', ['--keep' => 'real@test.dev', '--force' => true])
            ->expectsConfirmation('Apagar DEFINITIVAMENTE 1 usuário(s) e tudo o que é deles? Isso não pode ser desfeito.', 'no')
            ->assertFailed();

        $this->assertSame(2, User::count());
    }

    public function test_the_purge_cascades_and_repairs_what_the_cascade_leaves_behind(): void
    {
        Storage::fake('public');
        Storage::fake('local');

        $real = $this->user('real@test.dev');
        $real2 = $this->user('real2@test.dev');
        $test = $this->user('teste@test.dev');
        $test2 = $this->user('teste2@test.dev');

        // A real shift where a test courier was accepted and confirmed (shift filled).
        $realShift = $this->shift($real, ['venue' => 'Loja Real', 'status' => Shift::STATUS_FILLED, 'reserved_by' => $test->id]);
        Application::create(['shift_id' => $realShift->id, 'user_id' => $test->id, 'status' => 'accepted', 'confirmed' => true]);
        // A real shift with a real accepted courier AND a test candidate: stays reserved for the real one.
        $mixed = $this->shift($real, ['venue' => 'Loja Mista', 'status' => Shift::STATUS_RESERVED, 'couriers_needed' => 1]);
        Application::create(['shift_id' => $mixed->id, 'user_id' => $real2->id, 'status' => 'accepted', 'confirmed' => false]);
        Application::create(['shift_id' => $mixed->id, 'user_id' => $test->id, 'status' => 'interested']);
        // A test shift (goes away) with a real courier interested.
        $testShift = $this->shift($test, ['venue' => 'Loja de Teste']);
        Application::create(['shift_id' => $testShift->id, 'user_id' => $real2->id, 'status' => 'interested']);
        $chat = Chat::findOrCreateBetween($testShift->id, $test->id, $real2->id);
        Message::create(['chat_id' => $chat->id, 'author_id' => $test->id, 'body' => 'oi']);

        // Ratings: the test user rated the real one 1 star; another real review stays.
        $r1 = Review::create(['shift_id' => $realShift->id, 'author_id' => $test->id, 'target_id' => $real->id, 'rating' => 1, 'target_role' => 'business']);
        $r2 = Review::create(['shift_id' => $mixed->id, 'author_id' => $real2->id, 'target_id' => $real->id, 'rating' => 5, 'target_role' => 'business']);
        DB::table('reviews')->update(['published_at' => now()->subDay()]);
        (new \App\Observers\ReviewObserver)->recalculate($real->id, 'business');
        $this->assertSame(2, $real->profile->fresh()->business_total_reviews);
        $this->assertEquals(3.0, (float) $real->profile->fresh()->business_avg_rating);

        // Leftovers a cascade doesn't handle.
        $deadChatNote = Notification::create(['user_id' => $real2->id, 'type' => 'mensagem', 'title' => 'Nova mensagem', 'payload' => ['chat_id' => $chat->id]]);
        $liveNote = Notification::create(['user_id' => $real2->id, 'type' => 'turno', 'title' => 'Vaga', 'payload' => ['shift_id' => $mixed->id]]);
        DB::table('push_subscriptions')->insert([
            'subscribable_type' => User::class, 'subscribable_id' => $test->id, 'endpoint' => 'https://push.example/x',
            'public_key' => 'k', 'auth_token' => 'a', 'content_encoding' => 'aesgcm',
        ]);
        DB::table('sessions')->insert(['id' => 'sess1', 'user_id' => $test->id, 'payload' => '', 'last_activity' => time()]);
        Storage::disk('public')->put('avatars/'.$test->id.'.jpg', 'x');
        Storage::disk('public')->put('address-photos/shared.jpg', 'x');
        $test->profile()->update(['photo_url' => 'https://app.test/storage/avatars/'.$test->id.'.jpg']);
        UserAddress::create(['user_id' => $test->id, 'label' => 'Loja', 'street' => 'A', 'number' => '1', 'district' => 'Centro', 'city' => 'SP', 'photo_url' => 'https://app.test/storage/address-photos/shared.jpg']);
        // A file a REMAINING user's address also points at must survive.
        UserAddress::create(['user_id' => $real->id, 'label' => 'Minha', 'street' => 'B', 'number' => '2', 'district' => 'Centro', 'city' => 'SP', 'photo_url' => 'https://app.test/storage/address-photos/shared.jpg']);
        Storage::disk('local')->put('documents/'.$test->id.'/cnh.pdf', 'x');

        $this->artisan('users:purge', ['--keep' => ['real@test.dev', 'real2@test.dev'], '--force' => true])
            ->expectsConfirmation('Apagar DEFINITIVAMENTE 2 usuário(s) e tudo o que é deles? Isso não pode ser desfeito.', 'yes')
            ->assertSuccessful();

        // Gone, with their things.
        $this->assertEqualsCanonicalizing([$real->id, $real2->id], User::pluck('id')->all());
        $this->assertNull(Shift::find($testShift->id));
        $this->assertSame(0, Chat::count());
        $this->assertSame(0, Message::count());
        $this->assertNull(Review::find($r1->id));
        $this->assertSame(0, DB::table('push_subscriptions')->count());
        $this->assertSame(0, DB::table('sessions')->where('user_id', $test->id)->count());
        $this->assertSame(0, \App\Models\Profile::where('id', $test->id)->count());

        // Repaired: rating no longer counts the deleted review...
        $this->assertSame(1, $real->profile->fresh()->business_total_reviews);
        $this->assertEquals(5.0, (float) $real->profile->fresh()->business_avg_rating);
        // ...the real shift that lost its accepted courier is open again...
        $this->assertSame(Shift::STATUS_AVAILABLE, $realShift->fresh()->status);
        $this->assertNull($realShift->fresh()->reserved_by);
        // ...and the one with a real accepted courier stays reserved for them.
        $this->assertSame(Shift::STATUS_RESERVED, $mixed->fresh()->status);
        $this->assertSame($real2->id, $mixed->fresh()->reserved_by);
        // The real courier's application on the test shift is gone with it; their other data is intact.
        $this->assertSame(1, Application::where('user_id', $real2->id)->count());

        // Dead links removed, live ones kept.
        $this->assertNull(Notification::find($deadChatNote->id));
        $this->assertNotNull(Notification::find($liveNote->id));

        // Files: the deleted user's avatar and documents are gone; the file still used by a kept user stays.
        Storage::disk('public')->assertMissing('avatars/'.$test->id.'.jpg');
        Storage::disk('public')->assertExists('address-photos/shared.jpg');
        Storage::disk('local')->assertMissing('documents/'.$test->id.'/cnh.pdf');
    }
}
