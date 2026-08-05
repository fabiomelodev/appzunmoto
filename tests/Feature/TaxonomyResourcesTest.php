<?php

namespace Tests\Feature;

use App\Models\Benefit;
use App\Models\ExpectedVolume;
use App\Models\User;
use App\Models\VenueType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomyResourcesTest extends TestCase
{
    use RefreshDatabase;

    protected function admin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@test.dev',
            'password' => 'secret123',
            'is_admin' => true,
        ]);
    }

    protected function regularUser(): User
    {
        return User::create([
            'name' => 'Maria',
            'email' => 'maria'.uniqid().'@test.dev',
            'password' => 'secret123',
        ]);
    }

    public function test_admin_can_access_taxonomy_resource_pages(): void
    {
        $this->actingAs($this->admin());

        $this->get('/admin/venue-types')->assertOk();
        $this->get('/admin/expected-volumes')->assertOk();
        $this->get('/admin/benefits')->assertOk();
    }

    public function test_regular_user_cannot_access_taxonomy_resource_pages(): void
    {
        $this->actingAs($this->regularUser());

        $this->get('/admin/venue-types')->assertForbidden();
        $this->get('/admin/expected-volumes')->assertForbidden();
        $this->get('/admin/benefits')->assertForbidden();
    }

    public function test_venue_type_scope_active_only_returns_active_rows(): void
    {
        VenueType::create(['name' => 'Pizzaria', 'slug' => 'pizzaria', 'status' => 'active']);
        VenueType::create(['name' => 'Desativado', 'slug' => 'desativado', 'status' => 'inactive']);

        $this->assertSame(['Pizzaria'], VenueType::active()->pluck('name')->all());
    }

    public function test_expected_volume_scope_active_only_returns_active_rows(): void
    {
        ExpectedVolume::create(['name' => 'Tranquilo', 'slug' => 'tranquilo', 'status' => 'active']);
        ExpectedVolume::create(['name' => 'Desativado', 'slug' => 'desativado', 'status' => 'inactive']);

        $this->assertSame(['Tranquilo'], ExpectedVolume::active()->pluck('name')->all());
    }

    public function test_benefit_scope_active_only_returns_active_rows(): void
    {
        Benefit::create(['name' => 'Lanche', 'slug' => 'lanche', 'icon' => 'sandwich', 'status' => 'active']);
        Benefit::create(['name' => 'Desativado', 'slug' => 'desativado', 'status' => 'inactive']);

        $this->assertSame(['Lanche'], Benefit::active()->pluck('name')->all());
    }
}
