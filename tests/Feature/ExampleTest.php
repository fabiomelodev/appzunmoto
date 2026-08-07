<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Guests hitting the app root are redirected to the login screen.
     */
    public function test_guest_root_redirects_to_login(): void
    {
        $this->get('/')->assertRedirect(route('login'));
    }

    /**
     * The login screen renders for guests.
     */
    public function test_login_screen_is_reachable(): void
    {
        $this->get('/login')->assertOk()->assertSee('ZunMoto');
    }
}
