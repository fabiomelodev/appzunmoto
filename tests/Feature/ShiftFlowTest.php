<?php

namespace Tests\Feature;

use App\Livewire\Addresses\Choose;
use App\Livewire\Shifts\Create;
use App\Livewire\Shifts\Index;
use App\Livewire\Shifts\Show;
use App\Models\Application;
use App\Models\Notification;
use App\Models\Review;
use App\Models\Shift;
use App\Models\UserAddress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShiftFlowTest extends TestCase
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

    public function test_creator_submits_review_and_rating_is_recalculated(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        // Review is only allowed after the shift has ended.
        $shift = $this->shift($creator, [
            'status' => 'reserved', 'reserved_by' => $courier->id,
            'date' => now()->subDay()->toDateString(),
        ]);

        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->set('rating', 5)
            ->set('comment', 'Excelente')
            ->call('submitReview')
            ->assertDispatched('toast');

        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $creator->id, 'target_id' => $courier->id, 'rating' => 5,
        ]);
        $this->assertSame(5.0, (float) $courier->fresh()->profile->avg_rating);
        $this->assertSame(1, (int) $courier->fresh()->profile->total_reviews);
    }

    public function test_non_creator_cannot_review_or_self_review(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, [
            'status' => 'reserved', 'reserved_by' => $courier->id,
            'date' => now()->subDay()->toDateString(),
        ]);

        // The reserved courier tries to review (would be a self-review) → blocked.
        $this->actingAs($courier);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->set('rating', 5)
            ->call('submitReview');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_review_blocked_before_shift_ends(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        // Future shift (not expired yet).
        $shift = $this->shift($creator, ['status' => 'reserved', 'reserved_by' => $courier->id]);

        $this->actingAs($creator);
        Livewire::test(Show::class, ['id' => $shift->id])
            ->set('rating', 5)
            ->call('submitReview');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_profile_modal_loads_public_profile(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $courier->profile->update(['bio' => 'Rápido e pontual', 'vehicle' => 'moto']);
        $shift = $this->shift($creator);
        Application::create(['shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'interested']);

        $this->actingAs($creator);
        Livewire::test(\App\Livewire\ProfileModal::class)
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
        $this->assertDatabaseHas('shift_contacts', ['shift_id' => $shift->id, 'contact_name' => 'Maria']);
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
