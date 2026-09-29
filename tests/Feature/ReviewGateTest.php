<?php

namespace Tests\Feature;

use App\Livewire\ReviewGate;
use App\Models\Application;
use App\Models\Review;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * [TEST BRANCH feature/avaliacao-obrigatoria] Covers the mandatory review
 * gate (App\Livewire\ReviewGate): a non-dismissible modal shown on every
 * app-layout page until every review the signed-in account owes is done.
 */
class ReviewGateTest extends TestCase
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
            'creator_id' => $creator->id, 'creator_role' => 'business', 'venue' => 'Pizzaria X', 'region' => 'Centro',
            'address' => 'Rua A, 1', 'date' => now('America/Sao_Paulo')->subDay()->toDateString(), 'start_time' => '18:00', 'end_time' => '23:00',
            'daily_rate' => 150, 'delivery_fee_min' => 8, 'delivery_fee_max' => 12, 'accepted_vehicles' => ['moto'],
            'requires_own_bag' => false, 'couriers_needed' => 1, 'status' => 'filled', 'lat' => 0, 'lng' => 0,
        ], $overrides));
    }

    protected function confirm(Shift $shift, User $courier, bool $confirmed = true): void
    {
        Application::create([
            'shift_id' => $shift->id, 'user_id' => $courier->id, 'status' => 'accepted', 'confirmed' => $confirmed,
        ]);
    }

    public function test_no_gate_when_nothing_is_pending(): void
    {
        $this->actingAs($this->user());

        Livewire::test(ReviewGate::class)->assertDontSee('Avalie para continuar');
    }

    public function test_creator_is_gated_until_reviewing_the_confirmed_courier(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);

        $this->actingAs($creator);
        Livewire::test(ReviewGate::class)
            ->assertSee('Avalie para continuar')
            ->assertSee('Moto')
            ->set('rating', 5)
            ->call('submit')
            ->assertDontSee('Avalie para continuar');

        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $creator->id, 'target_id' => $courier->id, 'rating' => 5,
        ]);
    }

    public function test_courier_is_gated_until_reviewing_the_creator(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);

        $this->actingAs($courier);
        Livewire::test(ReviewGate::class)
            ->assertSee('Avalie para continuar')
            ->assertSee('Dono')
            ->set('rating', 4)
            ->call('submit')
            ->assertDontSee('Avalie para continuar');

        $this->assertDatabaseHas('reviews', [
            'shift_id' => $shift->id, 'author_id' => $courier->id, 'target_id' => $creator->id, 'rating' => 4,
        ]);
    }

    public function test_submitting_without_a_rating_does_nothing(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);

        $this->actingAs($creator);
        Livewire::test(ReviewGate::class)
            ->call('submit')
            ->assertSee('Avalie para continuar');

        $this->assertDatabaseCount('reviews', 0);
    }

    public function test_gate_moves_to_the_next_pending_review_after_submitting_one(): void
    {
        $creator = $this->user('Dono');
        $first = $this->user('Primeiro');
        $second = $this->user('Segundo');
        $shiftA = $this->shift($creator, ['venue' => 'Vaga A']);
        $this->confirm($shiftA, $first);
        $shiftB = $this->shift($creator, ['venue' => 'Vaga B']);
        $this->confirm($shiftB, $second);

        $this->actingAs($creator);
        Livewire::test(ReviewGate::class)
            ->assertSee('Primeiro')
            ->assertSee('Mais 1 avaliação pendente')
            ->set('rating', 5)
            ->call('submit')
            ->assertSee('Segundo')
            ->assertDontSee('Mais 1 avaliação pendente')
            ->set('rating', 5)
            ->call('submit')
            ->assertDontSee('Avalie para continuar');

        $this->assertDatabaseCount('reviews', 2);
    }

    public function test_gate_ignores_unconfirmed_partnerships(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier, confirmed: false);

        $this->actingAs($creator);
        Livewire::test(ReviewGate::class)->assertDontSee('Avalie para continuar');

        $this->actingAs($courier);
        Livewire::test(ReviewGate::class)->assertDontSee('Avalie para continuar');
    }

    public function test_gate_does_not_show_before_the_shift_ends(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator, ['date' => now('America/Sao_Paulo')->addDay()->toDateString()]);
        $this->confirm($shift, $courier);

        $this->actingAs($creator);
        Livewire::test(ReviewGate::class)->assertDontSee('Avalie para continuar');
    }

    public function test_gate_does_not_show_once_already_reviewed(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);
        Review::create([
            'shift_id' => $shift->id, 'author_id' => $creator->id, 'target_id' => $courier->id,
            'target_role' => 'courier', 'rating' => 5,
        ]);

        $this->actingAs($creator);
        Livewire::test(ReviewGate::class)->assertDontSee('Avalie para continuar');

        // The courier still owes their own review of the creator.
        $this->actingAs($courier);
        Livewire::test(ReviewGate::class)->assertSee('Avalie para continuar');
    }

    public function test_gate_is_not_rendered_on_guest_pages(): void
    {
        $this->get(route('login'))->assertOk()->assertDontSee('Avalie para continuar');
    }

    public function test_gate_blocks_the_shifts_page_until_reviewed(): void
    {
        $creator = $this->user('Dono');
        $courier = $this->user('Moto');
        $shift = $this->shift($creator);
        $this->confirm($shift, $courier);

        $this->actingAs($creator);
        $this->get(route('shifts.index'))->assertOk()->assertSee('Avalie para continuar');
    }
}
