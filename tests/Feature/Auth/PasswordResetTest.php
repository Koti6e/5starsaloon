<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_screen_is_unavailable(): void
    {
        $this->get('/forgot-password')->assertNotFound();
    }

    public function test_reset_password_submission_is_unavailable(): void
    {
        $this->post('/reset-password')->assertNotFound();
    }
}
