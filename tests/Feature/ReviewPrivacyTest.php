<?php

namespace Tests\Feature;

use App\Livewire\ProfileModal;
use App\Livewire\ProfilePage;
use App\Models\Application;
use App\Models\Review;
use App\Models\Shift;
use App\Models\User;
use App\Support\Reviews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ReviewPrivacyTest extends TestCase
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

    /** @return array{0: Shift, 1: User, 2: User} finished shift, creator, courier (partnership confirmed) */
    protected function finishedShift(): array
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = Shift::create([
            'creator_id' => $creator->id, 'creator_role' => 'business', 'venue' => 'Pizzaria X', 'region' => 'Centro',
            'address' => 'Rua A, 1', 'date' => now()->subDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12, 'accepted_vehicles' => ['moto'],
            'requires_own_bag' => false, 'couriers_needed' => 1, 'status' => 'filled', 'lat' => 0, 'lng' => 0,
        ]);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true]);

        return [$shift, $creator, $courier];
    }

    public function test_a_new_review_starts_private_and_leaves_the_rating_untouched(): void
    {
        [$shift, $creator, $courier] = $this->finishedShift();

        $this->assertTrue(Reviews::submit($shift, $creator->id, $courier->id, 1, 'Atrasou muito'));

        $review = Review::firstOrFail();
        $this->assertNull($review->published_at);
        $profile = $courier->fresh()->profile;
        $this->assertSame(0, (int) $profile->total_reviews);
        $this->assertSame(0.0, (float) $profile->avg_rating);

        // Not visible on the reviewed person's own profile nor in the public profile dialog.
        $this->actingAs($courier);
        Livewire::test(ProfilePage::class)->set('tab', 'reviews')->assertDontSee('Atrasou muito');
        Livewire::test(ProfileModal::class)->call('open', $courier->id)->assertDontSee('Atrasou muito');

        // The author still knows it was sent.
        $this->actingAs($creator);
        $this->assertTrue(Reviews::hasReviewed($shift, $creator->id, $courier->id));
    }

    public function test_it_stays_private_until_the_waiting_period_is_over(): void
    {
        [$shift, $creator, $courier] = $this->finishedShift();
        Reviews::submit($shift, $creator->id, $courier->id, 2, 'Comentário reservado');
        $sentAt = Review::firstOrFail()->created_at;

        // Just before the 5 days: nothing published.
        Carbon::setTestNow($sentAt->copy()->addDays(Review::PUBLISH_DELAY_DAYS)->subMinute());
        $this->artisan('reviews:publish')->expectsOutput('Reviews published: 0');
        $this->assertNull(Review::firstOrFail()->published_at);
        $this->assertSame(0, (int) $courier->fresh()->profile->total_reviews);

        // Once the period is over: published, dated at the moment it was due.
        Carbon::setTestNow($sentAt->copy()->addDays(Review::PUBLISH_DELAY_DAYS)->addMinute());
        $this->artisan('reviews:publish')->expectsOutput('Reviews published: 1');
        Carbon::setTestNow();

        $review = Review::firstOrFail();
        $this->assertEquals($sentAt->copy()->addDays(Review::PUBLISH_DELAY_DAYS), $review->published_at);

        $profile = $courier->fresh()->profile;
        $this->assertSame(1, (int) $profile->total_reviews);
        $this->assertSame(2.0, (float) $profile->avg_rating);

        $this->actingAs($courier);
        Livewire::test(ProfilePage::class)->set('tab', 'reviews')
            ->assertSee('Comentário reservado')
            // No date at all: neither when it was sent nor when it went public.
            ->assertDontSee($sentAt->format('d/m/Y'))
            ->assertDontSee($review->published_at->format('d/m/Y'));
        Livewire::test(ProfileModal::class)->call('open', $courier->id)->assertSee('Comentário reservado');
    }

    public function test_published_reviews_never_reveal_their_author(): void
    {
        [$shift, $creator, $courier] = $this->finishedShift();
        $creator->profile->update(['name' => 'Pizzaria Identificavel']);
        Reviews::submit($shift, $creator->id, $courier->id, 2, 'Não gostei');
        $this->publishDueReviews();

        $this->actingAs($courier);
        Livewire::test(ProfilePage::class)->set('tab', 'reviews')
            ->assertSee('Não gostei')
            ->assertSee('Anônimo')
            ->assertDontSee('Pizzaria Identificavel')
            ->assertDontSee('Dono');

        Livewire::test(ProfileModal::class)->call('open', $courier->id)
            ->assertSee('Não gostei')
            ->assertDontSee('Pizzaria Identificavel');
    }

    public function test_publishing_twice_does_nothing_more(): void
    {
        [$shift, $creator, $courier] = $this->finishedShift();
        Reviews::submit($shift, $creator->id, $courier->id, 4, '');

        $this->publishDueReviews();
        $publishedAt = Review::firstOrFail()->published_at;

        $this->publishDueReviews();

        $this->assertEquals($publishedAt, Review::firstOrFail()->published_at);
        $this->assertSame(1, (int) $courier->fresh()->profile->total_reviews);
    }

    public function test_a_private_review_does_not_move_an_already_public_average(): void
    {
        [$shift, $creator, $courier] = $this->finishedShift();
        Review::create([
            'shift_id' => $shift->id, 'author_id' => $this->user('Antigo')->id, 'target_id' => $courier->id,
            'target_role' => 'courier', 'rating' => 5, 'published_at' => now(),
        ]);
        $this->assertSame(5.0, (float) $courier->fresh()->profile->avg_rating);

        Reviews::submit($shift, $creator->id, $courier->id, 1, 'Ruim');

        $profile = $courier->fresh()->profile;
        $this->assertSame(1, (int) $profile->total_reviews);
        $this->assertSame(5.0, (float) $profile->avg_rating);

        $this->publishDueReviews();

        $profile = $courier->fresh()->profile;
        $this->assertSame(2, (int) $profile->total_reviews);
        $this->assertSame(3.0, (float) $profile->avg_rating);
    }

    public function test_the_establishment_rating_also_waits_before_going_public(): void
    {
        [$shift, $creator, $courier] = $this->finishedShift();

        Reviews::submit($shift, $courier->id, $creator->id, 3, 'Espera longa');
        $this->assertSame(0, (int) $creator->fresh()->profile->business_total_reviews);

        $this->publishDueReviews();

        $this->assertSame(1, (int) $creator->fresh()->profile->business_total_reviews);
        $this->assertSame(3.0, (float) $creator->fresh()->profile->business_avg_rating);
    }

    public function test_the_review_dialogs_tell_the_author_about_the_delay(): void
    {
        [$shift, $creator, $courier] = $this->finishedShift();
        $this->actingAs($creator);

        Livewire::test(\App\Livewire\Shifts\Show::class, ['id' => $shift->id])
            ->call('openReview', $courier->id)
            ->assertSee('só fica pública '.Review::PUBLISH_DELAY_DAYS.' dias depois de enviada');
    }
}
