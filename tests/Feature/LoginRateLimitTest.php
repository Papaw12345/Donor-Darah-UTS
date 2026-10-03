<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_email_and_ip_are_limited_after_five_login_attempts_per_minute(): void
    {
        $payload = [
            'email' => 'target@example.test',
            'password' => 'wrong-password',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.authenticate'), $payload)
                ->assertRedirect()
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.authenticate'), $payload)
            ->assertStatus(429);
    }

    public function test_different_email_on_same_ip_has_an_independent_login_limit(): void
    {
        $firstPayload = [
            'email' => 'first@example.test',
            'password' => 'wrong-password',
        ];

        for ($attempt = 1; $attempt <= 5; $attempt++) {
            $this->post(route('login.authenticate'), $firstPayload)
                ->assertRedirect()
                ->assertSessionHasErrors('email');
        }

        $this->post(route('login.authenticate'), [
            'email' => 'second@example.test',
            'password' => 'wrong-password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');
    }

    public function test_login_get_is_not_rate_limited(): void
    {
        for ($attempt = 1; $attempt <= 6; $attempt++) {
            $this->get(route('login'))
                ->assertOk();
        }
    }
}