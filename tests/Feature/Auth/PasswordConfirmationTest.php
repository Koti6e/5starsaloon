<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_confirmation_routes_are_unavailable(): void
    {
        $this->get('/confirm-password')->assertNotFound();
        $this->post('/confirm-password')->assertNotFound();
    }
}
