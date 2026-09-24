<?php

namespace Tests\Feature;

use App\Livewire\Addresses\Choose;
use App\Livewire\ProfileModal;
use App\Livewire\Shifts\Create;
use App\Livewire\Shifts\Index;
use App\Livewire\Shifts\Show;
use App\Models\Application;
use App\Models\Banner;
use App\Models\Benefit;
use App\Models\ExpectedVolume;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Shift;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\VenueType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Shift::save() now validates venue_type/expected_volume/benefits against
        // these DB-backed taxonomies (see App\Support\Catalog) instead of fixed lists.
        VenueType::create(['name' => 'Pizzaria', 'slug' => 'pizzaria', 'status' => 'active']);
        ExpectedVolume::create(['name' => 'Moderado', 'slug' => 'moderado', 'status' => 'active']);
        Benefit::create(['name' => 'Lanche', 'slug' => 'lanche', 'icon' => 'sandwich', 'status' => 'active']);
    }

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
            'creator_id' => $creator->id,
            'creator_role' => 'business',
            'venue' => 'Pizzaria X',
            'region' => 'Centro',
            'address' => 'Rua A, 1 — Centro',
            'date' => now()->addDay()->toDateString(),
            'start_time' => '18:00',
            'end_time' => '23:00',
            'daily_rate' => 150,
            'delivery_fee_min' => 8,
            'delivery_fee_max' => 12,
            'venue_type' => 'pizzaria',
            'expected_volume' => 'moderado',
            'benefits' => ['lanche'],
            'accepted_vehicles' => ['moto'],
            'requires_own_bag' => false,
            'couriers_needed' => 1,
            'status' => 'available',
            'lat' => 0, 'lng' => 0,
        ], $overrides));
    }

    public function test_courier_registers_interest_and_creator_is_notified(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto', 'has_bag' => true]);
        $shift = $this->shift($creator);

        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('registerInterest')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('applications', [
            'shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested',
        ]);
        $this->assertTrue(
            Notification::where('user_id', $creator->id)->where('type', 'vaga')->exists()
        );
    }

    public function test_account_on_the_business_profile_cannot_register_interest(): void
    {
        $creator = $this->user('Dono');
        $other = $this->user('Outro');
        // Was a courier (vehicle/bag on file), then switched to estabelecimento.
        $other->profile->update(['vehicle' => 'moto', 'has_bag' => true, 'role' => 'business']);
        $shift = $this->shift($creator);

        $this->actingAs($other);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('Disponível apenas para motoboys')
            ->assertDontSee('Aceitar Vaga')
            ->call('registerInterest');

        $this->assertDatabaseMissing('applications', ['shift_id' => $shift->id, 'user_id' => $other->id]);

        // Switching back to courier makes the same shift available again.
        $other->profile->update(['role' => 'courier']);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('Aceitar Vaga')
            ->call('registerInterest');

        $this->assertDatabaseHas('applications', ['shift_id' => $shift->id, 'user_id' => $other->id]);
    }

    public function test_courier_withdraws_interest(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('withdrawInterest')
            ->assertDispatched('toast');

        $this->assertDatabaseMissing('applications', [
            'shift_id' => $shift->id, 'user_id' => $courier->id,
        ]);
    }

    public function test_withdraw_interest_does_not_touch_another_couriers_application(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $other = $this->user('Outro');
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $other->id, 'status' => 'interested']);

        // Courier has no application of their own on this shift — nothing to withdraw.
        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])->call('withdrawInterest');

        $this->assertDatabaseHas('applications', [
            'shift_id' => $shift->id, 'user_id' => $other->id, 'status' => 'interested',
        ]);
    }

    public function test_accepted_courier_cannot_withdraw_via_this_action(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted']);

        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])->call('withdrawInterest');

        $this->assertDatabaseHas('applications', [
            'shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted',
        ]);
    }

    public function test_register_blocked_for_incompatible_vehicle(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto']);
        $shift = $this->shift($creator, ['accepted_vehicles' => ['bike']]);

        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])->call('registerInterest');

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_register_blocked_when_bag_required_and_missing(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto', 'has_bag' => false]);
        $shift = $this->shift($creator, ['requires_own_bag' => true]);

        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])->call('registerInterest');

        $this->assertDatabaseCount('applications', 0);
    }

    public function test_accepted_courier_still_appears_in_interested_list_with_badge(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);

        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => Application::STATUS_ACCEPTED]);

        $this->actingAs($creator);

        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('Motoboys interessados')
            ->assertSeeHtml('(1)')
            ->assertSee('Moto')
            ->assertSee('Aceito')
            ->assertDontSee('Ainda ninguém demonstrou interesse.');
    }

    public function test_confirm_has_bag_unblocks_registration_without_leaving_the_page(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto', 'has_bag' => false]);
        $shift = $this->shift($creator, ['requires_own_bag' => true]);

        $this->actingAs($courier);

        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('Sim, tenho mochila térmica (bag)')
            ->call('confirmHasBag')
            ->assertDispatched('toast')
            ->assertDontSee('Sim, tenho mochila térmica (bag)')
            ->assertSee('Aceitar Vaga');

        $this->assertTrue((bool) $courier->profile->fresh()->has_bag);
    }

    /** A finished shift (yesterday) with a confirmed partnership between creator and courier. */
    protected function finishedShiftWith(User $creator, User $courier, array $overrides = []): Shift
    {
        $shift = $this->shift($creator, array_merge([
            'status' => 'filled', 'reserved_by' => $courier->id,
            'date' => now()->subDay()->toDateString(),
        ], $overrides));
        Application::create([
            'shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => true,
        ]);

        return $shift;
    }

    public function test_creator_submits_review_and_rating_is_recalculated(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->finishedShiftWith($creator, $courier);

        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('Avaliar Moto')
            ->call('openReview', $courier->id)
            ->set('rating', 5)
            ->set('comment', 'Excelente')
            ->call('submitReview')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $creator->id, 'target_id' => $courier->id,
            'target_role' => 'courier', 'rating' => 5,
        ]);
        $this->assertSame(5.0, (float) $courier->fresh()->profile->avg_rating);
        $this->assertSame(1, (int) $courier->fresh()->profile->total_reviews);
    }

    public function test_review_button_locks_after_submission_and_second_submit_does_not_overwrite(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->finishedShiftWith($creator, $courier);

        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('openReview', $courier->id)
            ->set('rating', 5)
            ->set('comment', 'Primeira avaliação')
            ->call('submitReview');

        // Button now shows the locked state instead of the reviewable one.
        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('avaliação enviada')
            ->assertDontSee('Avaliar Moto');

        // Even if triggered directly, a second submission must not change the review.
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('openReview', $courier->id)
            ->set('rating', 1)
            ->set('comment', 'Tentativa de sobrescrever')
            ->call('submitReview');

        $this->assertDatabaseCount('reviews', 1);
        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $creator->id, 'target_id' => $courier->id,
            'rating' => 5, 'comment' => 'Primeira avaliação',
        ]);
    }

    public function test_courier_reviews_the_establishment_and_the_rating_is_kept_per_role(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->finishedShiftWith($creator, $courier);

        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('Avaliar Dono')
            ->call('openReview', $creator->id)
            ->set('rating', 4)
            ->set('comment', 'Bom local')
            ->call('submitReview')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $courier->id, 'target_id' => $creator->id,
            'target_role' => 'business', 'rating' => 4,
        ]);

        $creatorProfile = $creator->fresh()->profile;
        $this->assertSame(4.0, (float) $creatorProfile->business_avg_rating);
        $this->assertSame(1, (int) $creatorProfile->business_total_reviews);
        // Nothing leaks into the account's courier-side rating.
        $this->assertSame(0.0, (float) $creatorProfile->avg_rating);
        $this->assertSame(0, (int) $creatorProfile->total_reviews);

        // The creator's review of the courier is independent of this one.
        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])->assertSee('Avaliar Moto');
    }

    public function test_courier_review_of_a_courier_creator_counts_as_a_courier_rating(): void
    {
        $creator = $this->user('Colega');
        $courier = $this->user('Moto');
        $shift = $this->finishedShiftWith($creator, $courier, ['creator_role' => 'courier']);

        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('openReview', $creator->id)
            ->set('rating', 5)
            ->call('submitReview');

        $this->assertDatabaseHas('reviews', ['target_id' => $creator->id, 'target_role' => 'courier']);
        $this->assertSame(1, (int) $creator->fresh()->profile->total_reviews);
        $this->assertSame(0, (int) $creator->fresh()->profile->business_total_reviews);
    }

    public function test_only_confirmed_partners_can_review_and_never_themselves(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $stranger = $this->user('Estranho');
        $unconfirmed = $this->user('Pendente');
        $shift = $this->finishedShiftWith($creator, $courier);
        Application::create([
            'shift_id' => $shift->id, 'user_id' => $unconfirmed->id, 'status' => 'accepted', 'confirmed' => false,
        ]);

        // Accepted but never confirmed the partnership → can't review, and can't be reviewed.
        $this->actingAs($unconfirmed);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('openReview', $creator->id)
            ->set('rating', 5)
            ->call('submitReview');

        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('openReview', $unconfirmed->id)
            ->set('rating', 5)
            ->call('submitReview')
            // ...nor themselves.
            ->call('openReview', $creator->id)
            ->set('rating', 5)
            ->call('submitReview');

        // A user with no part in the shift can't review either side.
        $this->actingAs($stranger);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('openReview', $creator->id)
            ->set('rating', 5)
            ->call('submitReview')
            ->call('openReview', $courier->id)
            ->set('rating', 5)
            ->call('submitReview');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_blocked_before_shift_ends(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->finishedShiftWith($creator, $courier, ['date' => now()->addDay()->toDateString()]);

        foreach ([[$creator, $courier], [$courier, $creator]] as [$author, $target]) {
            $this->actingAs($author);
            Livewire::test(Show::class, ['id' => $shift->id])
                ->assertDontSee('Avaliar '.$target->profile->name)
                ->call('openReview', $target->id)
                ->set('rating', 5)
                ->call('submitReview');
        }

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_multi_courier_shift_lists_each_confirmed_courier_for_review(): void
    {
        $creator = $this->user('Dono');
        $first = $this->user('Primeiro');
        $second = $this->user('Segundo');
        $shift = $this->finishedShiftWith($creator, $first, ['couriers_needed' => 2]);
        Application::create([
            'shift_id' => $shift->id, 'user_id' => $second->id, 'status' => 'accepted', 'confirmed' => true,
        ]);

        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('Avaliar Primeiro')
            ->assertSee('Avaliar Segundo')
            ->call('openReview', $first->id)
            ->set('rating', 5)
            ->call('submitReview');

        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertDontSee('Avaliar Primeiro')
            ->assertSee('Avaliar Segundo');

        $this->assertDatabaseCount('reviews', 1);
    }

    public function test_shift_page_shows_the_creators_rating_for_its_role(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);

        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])->assertSee('Sem avaliações');

        $creator->profile->update(['role' => 'business']);
        \App\Models\Profile::where('id', $creator->id)->update(['business_avg_rating' => 4.5, 'business_total_reviews' => 2]);

        Livewire::test(Show::class, ['id' => $shift->id])
            ->assertSee('4,5')
            ->assertSee('(2)')
            ->assertDontSee('Sem avaliações');
    }

    public function test_profile_modal_loads_public_profile(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['bio' => 'Rápido e pontual', 'vehicle' => 'moto']);
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($creator);
        Livewire::test(ProfileModal::class)
            ->call('open', $courier->id)
            ->assertSee('Rápido e pontual');
    }

    public function test_choose_address_saves_and_redirects_to_create(): void
    {
        $user = $this->user('Dono');
        $this->actingAs($user);

        Livewire::withQueryParams(['as' => 'business'])
            ->test(Choose::class)
            ->set('label', 'Pizzaria')
            ->set('cep', '01310-100')
            ->set('street', 'Av Paulista')
            ->set('number', '1000')
            ->set('district', 'Bela Vista')
            ->set('city', 'São Paulo')
            ->call('saveNew')
            ->assertRedirect();

        $this->assertDatabaseHas('user_addresses', [
            'user_id' => $user->id, 'label' => 'Pizzaria', 'postal_code' => '01310100',
        ]);
    }

    public function test_create_shift_saves_with_contact(): void
    {
        $user = $this->user('Dono');
        $address = UserAddress::create([
            'user_id' => $user->id, 'label' => 'Pizzaria', 'postal_code' => '01310100',
            'street' => 'Av Paulista', 'number' => '1000', 'district' => 'Bela Vista', 'city' => 'São Paulo',
        ]);
        $this->actingAs($user);

        $form = [
            'date' => now()->addDay()->toDateString(),
            'startTime' => '18:00', 'endTime' => '23:00',
            'dailyRate' => '150', 'feeMin' => '8', 'feeMax' => '12',
            'contactName' => 'Maria', 'contactPhone' => '11999990000',
            'notes' => 'Chegar 10min antes', 'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
            'couriersNeeded' => 2, 'benefits' => ['lanche'], 'vehicles' => ['moto'], 'requiresOwnBag' => true,
        ];

        Livewire::withQueryParams(['as' => 'business', 'address' => $address->id])
            ->test(Create::class)
            ->call('save', $form)
            ->assertRedirect();

        $shift = Shift::first();
        $this->assertNotNull($shift);
        $this->assertSame('Pizzaria', $shift->venue);
        $this->assertSame(2, $shift->couriers_needed);
        $this->assertSame(['moto'], $shift->accepted_vehicles);
        $this->assertEquals(8, $shift->delivery_fee_min);
        $this->assertEquals(12, $shift->delivery_fee_max);
        $this->assertDatabaseHas('shift_contacts', ['shift_id' => $shift->id, 'contact_name' => 'Maria']);
    }

    public function test_create_shift_rejects_max_fee_below_min(): void
    {
        $user = $this->user('Dono');
        $address = UserAddress::create([
            'user_id' => $user->id, 'label' => 'Pizzaria', 'postal_code' => '01310100',
            'street' => 'Av Paulista', 'number' => '1000', 'district' => 'Bela Vista', 'city' => 'São Paulo',
        ]);
        $this->actingAs($user);

        Livewire::withQueryParams(['as' => 'business', 'address' => $address->id])
            ->test(Create::class)
            ->call('save', [
                'date' => now()->addDay()->toDateString(),
                'startTime' => '18:00', 'endTime' => '23:00',
                'dailyRate' => '150', 'feeMin' => '12', 'feeMax' => '8',
                'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
                'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
            ])
            ->assertDispatched('toast');

        $this->assertDatabaseCount('shifts', 0);
    }

    public function test_shift_inherits_address_photo(): void
    {
        $user = $this->user('Dono');
        $address = UserAddress::create([
            'user_id' => $user->id, 'label' => 'Pizzaria', 'postal_code' => '01310100',
            'street' => 'Av Paulista', 'number' => '1000', 'district' => 'Bela Vista', 'city' => 'São Paulo',
            'photo_url' => 'http://localhost/storage/address-photos/abc.jpg',
        ]);
        $this->actingAs($user);

        Livewire::withQueryParams(['as' => 'business', 'address' => $address->id])
            ->test(Create::class)
            ->call('save', [
                'date' => now()->addDay()->toDateString(),
                'startTime' => '18:00', 'endTime' => '23:00',
                'dailyRate' => '150', 'feeMin' => '8', 'feeMax' => '12',
                'contactName' => '', 'contactPhone' => '', 'notes' => '',
                'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
                'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
            ])
            ->assertRedirect();

        $shift = Shift::where('creator_id', $user->id)->firstOrFail();
        $this->assertSame($address->photo_url, $shift->address_photo_url);
    }

    public function test_cannot_reduce_couriers_when_shift_has_interest(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['couriers_needed' => 2]);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);
        $this->actingAs($creator);

        $form = [
            'date' => $shift->date->toDateString(),
            'startTime' => '18:00', 'endTime' => '23:00',
            'dailyRate' => '150', 'feeMin' => '8', 'feeMax' => '12',
            'contactName' => '', 'contactPhone' => '', 'notes' => '',
            'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
            'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
        ];

        // Reduzir abaixo de 2 é rejeitado — segue 2.
        Livewire::test(Create::class, ['id' => $shift->id])
            ->call('save', $form)
            ->assertDispatched('toast');
        $this->assertSame(2, $shift->fresh()->couriers_needed);

        // Aumentar é permitido.
        Livewire::test(Create::class, ['id' => $shift->id])
            ->call('save', ['couriersNeeded' => 3] + $form)
            ->assertRedirect();
        $this->assertSame(3, $shift->fresh()->couriers_needed);
    }

    protected function createShiftForm(array $overrides = []): array
    {
        return array_merge([
            'date' => now()->addDay()->toDateString(),
            'startTime' => '18:00', 'endTime' => '23:00',
            'dailyRate' => '150', 'feeMin' => '8', 'feeMax' => '12',
            'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
            'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
        ], $overrides);
    }

    protected function createComponentFor(User $user)
    {
        $address = UserAddress::create([
            'user_id' => $user->id, 'label' => 'Pizzaria', 'street' => 'Av A', 'number' => '1',
            'district' => 'Centro', 'city' => 'SP',
        ]);
        $this->actingAs($user);

        return Livewire::withQueryParams(['as' => 'business', 'address' => $address->id])->test(Create::class);
    }

    public function test_create_shift_rejects_identical_start_and_end_times(): void
    {
        $this->createComponentFor($this->user('Dono'))
            ->call('save', $this->createShiftForm(['startTime' => '18:00', 'endTime' => '18:00']))
            ->assertDispatched('toast', message: 'O horário final não pode ser igual ao horário de início.');

        $this->assertDatabaseCount('shifts', 0);
    }

    public function test_create_shift_accepts_an_overnight_window_and_it_ends_the_next_day(): void
    {
        $this->createComponentFor($this->user('Dono'))
            ->call('save', $this->createShiftForm(['date' => '2099-09-24', 'startTime' => '20:00', 'endTime' => '03:00']));

        $shift = Shift::firstOrFail();
        $this->assertSame('2099-09-24', $shift->date->toDateString());
        $this->assertTrue($shift->crossesMidnight());
        $this->assertSame('2099-09-25 03:00', $shift->endsAt()->format('Y-m-d H:i'));
        $this->assertSame('20:00–03:00 (+1 dia)', $shift->timeRange());
    }

    public function test_same_day_shift_does_not_cross_midnight(): void
    {
        $shift = $this->shift($this->user('Dono'), ['date' => '2099-09-24', 'start_time' => '18:00', 'end_time' => '23:00']);

        $this->assertFalse($shift->crossesMidnight());
        $this->assertSame('2099-09-24 23:00', $shift->endsAt()->format('Y-m-d H:i'));
        $this->assertSame('18:00–23:00', $shift->timeRange());
    }

    public function test_overnight_shifts_conflict_across_the_date_boundary(): void
    {
        $creator = $this->user('Dono');
        $this->shift($creator, ['venue' => 'Pizzaria X', 'date' => '2099-09-24', 'start_time' => '20:00', 'end_time' => '03:00']);
        $component = $this->createComponentFor($creator);
        $component->set('venue', 'Pizzaria X');

        // Next-day 01:00–05:00 overlaps the tail of the overnight shift.
        $component->call('save', $this->createShiftForm(['date' => '2099-09-25', 'startTime' => '01:00', 'endTime' => '05:00']))
            ->assertDispatched('toast', message: 'Você já tem uma vaga neste local nesse mesmo horário.');
        $this->assertDatabaseCount('shifts', 1);

        // Touching edges (starts when the other ends) and other hours don't conflict.
        $component->call('save', $this->createShiftForm(['date' => '2099-09-25', 'startTime' => '03:00', 'endTime' => '07:00']));
        $component->call('save', $this->createShiftForm(['date' => '2099-09-24', 'startTime' => '10:00', 'endTime' => '15:00']));
        $this->assertDatabaseCount('shifts', 3);
    }

    public function test_listing_keeps_an_overnight_shift_until_it_ends_the_next_day(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto', 'has_bag' => true]);
        $this->shift($creator, ['venue' => 'Vaga Madrugada', 'date' => '2026-09-24', 'start_time' => '20:00', 'end_time' => '03:00']);
        $this->actingAs($courier);

        // Already 25/09 01:00 — dated yesterday but still running.
        Carbon::setTestNow(Carbon::create(2026, 9, 25, 1, 0, 0, 'America/Sao_Paulo'));
        Livewire::test(Index::class)->assertSee('Vaga Madrugada');

        Carbon::setTestNow(Carbon::create(2026, 9, 25, 3, 30, 0, 'America/Sao_Paulo'));
        Livewire::test(Index::class)->assertDontSee('Vaga Madrugada');

        Carbon::setTestNow();
    }

    public function test_an_overnight_shift_is_not_over_before_its_next_day_end(): void
    {
        $shift = $this->shift($this->user('Dono'), ['date' => '2026-09-24', 'start_time' => '20:00', 'end_time' => '03:00']);

        Carbon::setTestNow(Carbon::create(2026, 9, 24, 23, 0, 0, 'America/Sao_Paulo'));
        $this->assertFalse($shift->hasEnded());

        Carbon::setTestNow(Carbon::create(2026, 9, 25, 3, 1, 0, 'America/Sao_Paulo'));
        $this->assertTrue($shift->hasEnded());

        Carbon::setTestNow();
    }

    public function test_create_shift_starting_soon_is_accepted_in_sao_paulo_time(): void
    {
        // 12:00 in São Paulo is 15:00 UTC: a 13:00 start is still an hour away.
        Carbon::setTestNow(Carbon::create(2026, 9, 24, 12, 0, 0, 'America/Sao_Paulo'));

        $this->createComponentFor($this->user('Dono'))
            ->call('save', $this->createShiftForm([
                'date' => '2026-09-24', 'startTime' => '13:00', 'endTime' => '17:00',
            ]));

        Carbon::setTestNow();

        $this->assertDatabaseCount('shifts', 1);
    }

    public function test_listing_keeps_a_shift_visible_until_it_really_ends_in_sao_paulo_time(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto', 'has_bag' => true]);
        $this->shift($creator, ['venue' => 'Vaga Noite', 'date' => '2026-09-24', 'start_time' => '18:00', 'end_time' => '23:30']);
        $this->actingAs($courier);

        // 20:00 São Paulo = 23:00 UTC: ends 23:30 São Paulo, so still running.
        Carbon::setTestNow(Carbon::create(2026, 9, 24, 20, 0, 0, 'America/Sao_Paulo'));
        Livewire::test(Index::class)->assertSee('Vaga Noite');

        // 22:30 São Paulo is already 25/09 in UTC, yet today's shift must still show.
        Carbon::setTestNow(Carbon::create(2026, 9, 24, 22, 30, 0, 'America/Sao_Paulo'));
        Livewire::test(Index::class)->assertSee('Vaga Noite');

        // 23:45 São Paulo: over.
        Carbon::setTestNow(Carbon::create(2026, 9, 24, 23, 45, 0, 'America/Sao_Paulo'));
        Livewire::test(Index::class)->assertDontSee('Vaga Noite');

        Carbon::setTestNow();
    }

    public function test_create_shift_rejects_retroactive(): void
    {
        $user = $this->user('Dono');
        $address = UserAddress::create([
            'user_id' => $user->id, 'label' => 'Pizzaria', 'street' => 'Av A', 'number' => '1',
            'district' => 'Centro', 'city' => 'SP',
        ]);
        $this->actingAs($user);

        Livewire::withQueryParams(['as' => 'business', 'address' => $address->id])
            ->test(Create::class)
            ->call('save', [
                'date' => now()->subDay()->toDateString(),
                'startTime' => '18:00', 'endTime' => '23:00',
                'dailyRate' => '150', 'feeMin' => '8', 'feeMax' => '12',
                'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
                'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
            ]);

        $this->assertDatabaseCount('shifts', 0);
    }

    public function test_listing_marks_interest_and_filters_by_it(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto', 'has_bag' => true]);
        $a = $this->shift($creator, ['venue' => 'Vaga A']);
        $this->shift($creator, ['venue' => 'Vaga B']);
        Application::create(['shift_id' => $a->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($courier);

        // Both shifts listed; the one I applied to shows the badge.
        Livewire::test(Index::class)
            ->assertSee('Vaga A')
            ->assertSee('Vaga B')
            ->assertSee('Tenho interesse')
            // Filtering by "only interested" keeps A and drops B.
            ->call('applyFilters', ['onlyInterested' => true])
            ->assertSee('Vaga A')
            ->assertDontSee('Vaga B');
    }

    public function test_creator_can_edit_shift_and_marks_edited_at(): void
    {
        $creator = $this->user('Dono');
        $shift = $this->shift($creator, ['daily_rate' => 150, 'notes' => 'antigo']);

        $this->actingAs($creator);
        Livewire::test(Create::class, ['id' => $shift->id])
            ->call('save', [
                'date' => $shift->date->toDateString(),
                'startTime' => '18:00', 'endTime' => '23:00',
                'dailyRate' => '200', 'feeMin' => '8', 'feeMax' => '12',
                'contactName' => '', 'contactPhone' => '',
                'notes' => 'editado', 'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
                'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
            ])
            ->assertRedirect();

        $fresh = $shift->fresh();
        $this->assertSame(200.0, (float) $fresh->daily_rate);
        $this->assertSame('editado', $fresh->notes);
        $this->assertNotNull($fresh->edited_at);
    }

    public function test_editing_shift_without_interest_allows_changing_address(): void
    {
        $creator = $this->user('Dono');
        $shift = $this->shift($creator, ['venue' => 'Endereço Antigo']);
        $newAddress = UserAddress::create([
            'user_id' => $creator->id, 'label' => 'Endereço Novo', 'postal_code' => '01310100',
            'street' => 'Av Paulista', 'number' => '1000', 'district' => 'Bela Vista', 'city' => 'São Paulo',
            'lat' => -23.5, 'lng' => -46.6,
        ]);

        $this->actingAs($creator);
        Livewire::withQueryParams(['address' => $newAddress->id])
            ->test(Create::class, ['id' => $shift->id])
            ->assertSet('canChangeAddress', true)
            ->assertSet('venue', 'Endereço Novo')
            ->call('save', [
                'date' => $shift->date->toDateString(),
                'startTime' => '18:00', 'endTime' => '23:00',
                'dailyRate' => '150', 'feeMin' => '8', 'feeMax' => '12',
                'contactName' => '', 'contactPhone' => '',
                'notes' => '', 'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
                'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
            ])
            ->assertRedirect();

        $fresh = $shift->fresh();
        $this->assertSame('Endereço Novo', $fresh->venue);
        $this->assertStringContainsString('Bela Vista', $fresh->address);
    }

    public function test_editing_shift_with_interest_locks_the_address(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['venue' => 'Endereço Antigo']);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);
        $newAddress = UserAddress::create([
            'user_id' => $creator->id, 'label' => 'Endereço Novo', 'postal_code' => '01310100',
            'street' => 'Av Paulista', 'number' => '1000', 'district' => 'Bela Vista', 'city' => 'São Paulo',
        ]);

        $this->actingAs($creator);
        Livewire::withQueryParams(['address' => $newAddress->id])
            ->test(Create::class, ['id' => $shift->id])
            ->assertSet('canChangeAddress', false)
            ->assertSet('venue', 'Endereço Antigo')
            ->call('save', [
                'date' => $shift->date->toDateString(),
                'startTime' => '18:00', 'endTime' => '23:00',
                'dailyRate' => '150', 'feeMin' => '8', 'feeMax' => '12',
                'contactName' => '', 'contactPhone' => '',
                'notes' => '', 'venueType' => 'pizzaria', 'expectedVolume' => 'moderado',
                'couriersNeeded' => 1, 'benefits' => [], 'vehicles' => ['moto'], 'requiresOwnBag' => false,
            ])
            ->assertRedirect();

        $this->assertSame('Endereço Antigo', $shift->fresh()->venue);
    }

    public function test_deactivate_hides_shift_from_listing(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto']);
        $shift = $this->shift($creator, ['venue' => 'Vaga Pausável']);

        $this->actingAs($courier);
        Livewire::test(Index::class)->assertSee('Vaga Pausável');

        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])->call('toggleActive');
        $this->assertFalse((bool) $shift->fresh()->active);

        $this->actingAs($courier);
        Livewire::test(Index::class)->assertDontSee('Vaga Pausável');
    }

    public function test_creator_can_delete_shift(): void
    {
        $creator = $this->user('Dono');
        $shift = $this->shift($creator);

        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->call('deleteShift')
            ->assertRedirect(route('shifts.index'));

        $this->assertDatabaseMissing('shifts', ['id' => $shift->id]);
    }

    public function test_own_shifts_are_highlighted_in_listing(): void
    {
        $creator = $this->user('Dono');
        $other = $this->user('Outro');
        $other->profile->update(['vehicle' => 'moto']);
        $this->shift($creator, ['venue' => 'Minha Vaga']);

        $this->actingAs($creator);
        Livewire::test(Index::class)->assertSee('Minha Vaga')->assertSee('Sua vaga');

        $this->actingAs($other);
        Livewire::test(Index::class)->assertSee('Minha Vaga')->assertDontSee('Sua vaga');
    }

    public function test_hero_text_shows_when_no_active_banners(): void
    {
        $user = $this->user('Dono');
        Banner::create(['image' => 'banners/inactive.jpg', 'status' => 'inactive', 'order' => 0]);

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->assertSee('começa aqui')
            ->assertDontSee('swiper-wrapper');
    }

    public function test_banner_carousel_shows_instead_of_hero_text_when_active(): void
    {
        $user = $this->user('Dono');
        Banner::create(['image' => 'banners/promo.jpg', 'title' => 'Promoção', 'status' => 'active', 'order' => 0]);

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->assertSee('swiper-wrapper')
            ->assertSee('banners/promo.jpg')
            ->assertDontSee('começa aqui');
    }

    public function test_banner_with_link_wraps_image_in_anchor(): void
    {
        $user = $this->user('Dono');
        Banner::create([
            'image' => 'banners/promo.jpg', 'status' => 'active', 'order' => 0,
            'link_url' => 'https://exemplo.com/promo', 'open_in_new_tab' => true,
        ]);

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->assertSee('href="https://exemplo.com/promo"', false)
            ->assertSee('target="_blank"', false);
    }

    public function test_banner_without_new_tab_does_not_add_target_blank(): void
    {
        $user = $this->user('Dono');
        Banner::create([
            'image' => 'banners/promo.jpg', 'status' => 'active', 'order' => 0,
            'link_url' => 'https://exemplo.com/promo', 'open_in_new_tab' => false,
        ]);

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->assertSee('href="https://exemplo.com/promo"', false)
            ->assertDontSee('target="_blank"');
    }

    public function test_banner_without_link_does_not_render_an_empty_anchor(): void
    {
        $user = $this->user('Dono');
        Banner::create(['image' => 'banners/promo.jpg', 'status' => 'active', 'order' => 0]);

        $this->actingAs($user);
        Livewire::test(Index::class)
            ->assertSee('banners/promo.jpg')
            ->assertDontSee('href=""', false);
    }

    public function test_open_chat_requires_accepted_application(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['vehicle' => 'moto']);
        $shift = $this->shift($creator);
        $app = Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($courier);

        // Only interested → cannot open a chat with the creator.
        Livewire::test(Show::class, ['id' => $shift->id])->call('openChat');
        $this->assertDatabaseCount('chats', 0);

        // Accepted → chat is created and the user is redirected.
        $app->update(['status' => 'accepted']);
        Livewire::test(Show::class, ['id' => $shift->id])->call('openChat')->assertRedirect();
        $this->assertDatabaseCount('chats', 1);
    }

    public function test_pages_render(): void
    {
        $user = $this->user('Dono');
        $address = UserAddress::create([
            'user_id' => $user->id, 'label' => 'Pizzaria', 'street' => 'Av A', 'number' => '1',
            'district' => 'Centro', 'city' => 'SP',
        ]);
        $shift = $this->shift($user);
        $this->actingAs($user);

        $this->get(route('shifts.show', $shift->id))->assertOk()->assertSee('Pizzaria X');
        $this->get(route('shifts.create', ['as' => 'business', 'address' => $address->id]))->assertOk()->assertSee('Etapa 3 de 3');
        $this->get(route('addresses.choose'))->assertOk()->assertSee('Onde será o turno');
    }
}
