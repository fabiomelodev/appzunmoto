<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Shift;
use App\Models\User;
use Database\Seeders\DemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_geolocated_shifts_and_applications(): void
    {
        $this->seed(DemoSeeder::class);

        // Demo accounts exist with auto-provisioned profiles.
        $bella = User::where('email', 'bella@demo.test')->first();
        $this->assertNotNull($bella);
        $this->assertSame('business', $bella->profile->role);

        // At least one shift is geolocated (so the map has pins).
        $this->assertTrue(
            Shift::where('lat', '!=', 0)->where('lng', '!=', 0)->exists(),
            'Esperava ao menos uma vaga geolocalizada no seeder.'
        );

        // Some interest was seeded.
        $this->assertGreaterThan(0, Application::count());
    }
}
