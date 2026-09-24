<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ReviewReminderTest extends TestCase
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
            'date' => now('America/Sao_Paulo')->subDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12,
            'venue_type' => 'pizzaria', 'expected_volume' => 'moderado',
            'benefits' => [], 'accepted_vehicles' => ['moto'], 'requires_own_bag' => false,
            'couriers_needed' => 1, 'status' => 'filled', 'lat' => 0, 'lng' => 0,
        ], $overrides));
    }

    protected function confirm(Shift $shift, User $courier, bool $confirmed = true): void
    {
        Application::create([
            'shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => $confirmed,
        ]);
    }

    protected function reminders(User $user)
    {
        return Notification::where('user_id', $user->id)->where('type', 'avaliacao')->get();
    }

    public function test_both_sides_are_reminded_once_the_shift_ends(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);

        $this->artisan('reviews:send-reminders')->assertSuccessful();

        $creatorReminders = $this->reminders($creator);
        $this->assertCount(1, $creatorReminders);
        $this->assertStringContainsString('motoboy', $creatorReminders->first()->description);
        $this->assertSame(route('shifts.show', $shift->id), $creatorReminders->first()->resolveUrl());

        $courierReminders = $this->reminders($courier);
        $this->assertCount(1, $courierReminders);
        $this->assertStringContainsString('estabelecimento', $courierReminders->first()->description);
        $this->assertSame(route('shifts.show', $shift->id), $courierReminders->first()->resolveUrl());

        $this->assertNotNull($shift->fresh()->review_reminder_sent_at);
    }

    public function test_running_again_does_not_send_duplicates(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);

        $this->artisan('reviews:send-reminders');
        $this->artisan('reviews:send-reminders');

        $this->assertCount(1, $this->reminders($creator));
        $this->assertCount(1, $this->reminders($courier));
    }

    public function test_nothing_is_sent_before_the_shift_ends(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['date' => now('America/Sao_Paulo')->addDay()->toDateString()]);
        $this->confirm($shift, $courier);

        $this->artisan('reviews:send-reminders');

        $this->assertCount(0, $this->reminders($creator));
        $this->assertCount(0, $this->reminders($courier));
        $this->assertNull($shift->fresh()->review_reminder_sent_at);
    }

    public function test_end_time_is_evaluated_in_sao_paulo_time(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $now = Carbon::create(2026, 9, 24, 12, 0, 0, 'America/Sao_Paulo'); // 15:00 UTC
        Carbon::setTestNow($now);

        // Ends at 13:00 São Paulo time (16:00 UTC): still in the future.
        $running = $this->shift($creator, [
            'date' => '2026-09-24', 'start_time' => '09:00', 'end_time' => '13:00', 'venue' => 'Ainda rolando',
        ]);
        $this->confirm($running, $courier);
        // Ended at 11:00 São Paulo time (14:00 UTC): already over.
        $over = $this->shift($creator, [
            'date' => '2026-09-24', 'start_time' => '07:00', 'end_time' => '11:00', 'venue' => 'Acabou',
        ]);
        $this->confirm($over, $courier);

        $this->artisan('reviews:send-reminders');
        Carbon::setTestNow();

        $this->assertNull($running->fresh()->review_reminder_sent_at);
        $this->assertNotNull($over->fresh()->review_reminder_sent_at);
    }

    public function test_overnight_shift_is_reminded_only_after_it_ends_the_next_day(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['date' => '2026-09-23', 'start_time' => '20:00', 'end_time' => '03:00']);
        $this->confirm($shift, $courier);

        // 24/09 01:00: dated yesterday, but still running.
        Carbon::setTestNow(Carbon::create(2026, 9, 24, 1, 0, 0, 'America/Sao_Paulo'));
        $this->artisan('reviews:send-reminders');
        $this->assertCount(0, $this->reminders($creator));

        Carbon::setTestNow(Carbon::create(2026, 9, 24, 3, 30, 0, 'America/Sao_Paulo'));
        $this->artisan('reviews:send-reminders');
        Carbon::setTestNow();

        $this->assertCount(1, $this->reminders($creator));
        $this->assertCount(1, $this->reminders($courier));
    }

    public function test_shifts_without_a_confirmed_partnership_are_skipped(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier, confirmed: false);

        $this->artisan('reviews:send-reminders');

        $this->assertCount(0, $this->reminders($creator));
        $this->assertCount(0, $this->reminders($courier));
    }

    public function test_old_history_is_not_flooded(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['date' => now('America/Sao_Paulo')->subDays(30)->toDateString()]);
        $this->confirm($shift, $courier);

        $this->artisan('reviews:send-reminders');

        $this->assertCount(0, $this->reminders($creator));
        $this->assertCount(0, $this->reminders($courier));
    }

    public function test_people_who_already_reviewed_are_not_reminded(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);
        Review::create([
            'shift_id' => $shift->id, 'author_id' => $creator->id, 'target_id' => $courier->id,
            'target_role' => 'courier', 'rating' => 5,
        ]);

        $this->artisan('reviews:send-reminders');

        $this->assertCount(0, $this->reminders($creator));
        $this->assertCount(1, $this->reminders($courier));
    }

    public function test_multi_courier_shift_sends_one_reminder_to_the_creator_and_one_to_each_courier(): void
    {
        $creator = $this->user('Dono');
        $first = $this->user('Primeiro');
        $second = $this->user('Segundo');
        $shift = $this->shift($creator, ['couriers_needed' => 2]);
        $this->confirm($shift, $first);
        $this->confirm($shift, $second);

        $this->artisan('reviews:send-reminders');

        $creatorReminders = $this->reminders($creator);
        $this->assertCount(1, $creatorReminders);
        $this->assertStringContainsString('motoboys', $creatorReminders->first()->description);
        $this->assertCount(1, $this->reminders($first));
        $this->assertCount(1, $this->reminders($second));
    }

    public function test_users_who_turned_shift_notifications_off_are_skipped(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);
        UserSetting::updateOrCreate(['user_id' => $courier->id], ['notify_shifts' => false]);

        $this->artisan('reviews:send-reminders');

        $this->assertCount(1, $this->reminders($creator));
        $this->assertCount(0, $this->reminders($courier));
    }
}
