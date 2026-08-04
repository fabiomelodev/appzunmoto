<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserAdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserAdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_two_admins_with_hashed_passwords(): void
    {
        $this->seed(UserAdminSeeder::class);

        $this->assertDatabaseCount('users', 2);

        $fabio = User::where('email', 'fabio.melo@giromoto.com.br')->first();
        $gabriel = User::where('email', 'gabriel.vinicius@giromoto.com.br')->first();

        $this->assertNotNull($fabio);
        $this->assertNotNull($gabriel);
        $this->assertTrue($fabio->is_admin);
        $this->assertTrue($gabriel->is_admin);
        $this->assertTrue(Hash::check('123@giromoto456', $fabio->password));
        $this->assertNotSame('123@giromoto456', $fabio->password);
    }

    public function test_is_idempotent_when_run_twice(): void
    {
        $this->seed(UserAdminSeeder::class);
        $this->seed(UserAdminSeeder::class);

        $this->assertDatabaseCount('users', 2);
    }

    public function test_provisions_profile_via_observer(): void
    {
        $this->seed(UserAdminSeeder::class);

        $fabio = User::where('email', 'fabio.melo@giromoto.com.br')->first();

        $this->assertNotNull($fabio->profile);
        $this->assertNotNull($fabio->settings);
    }
}
