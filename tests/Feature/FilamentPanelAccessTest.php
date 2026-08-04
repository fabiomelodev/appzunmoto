<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FilamentPanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_authenticated_user_cannot_access_admin_panel(): void
    {
        $user = User::create([
            'name' => 'Maria',
            'email' => 'maria'.uniqid().'@test.dev',
            'password' => 'secret123',
        ]);

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_admin_user_can_access_admin_panel(): void
    {
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin'.uniqid().'@test.dev',
            'password' => 'secret123',
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get('/admin')
            ->assertOk();
    }
}
