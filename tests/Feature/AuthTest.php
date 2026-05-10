<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_from_dashboard_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_register_creates_user_and_logs_in(): void
    {
        $response = $this->post('/register', [
            'name' => 'Иван Тестовый',
            'email' => 'ivan@example.com',
            'password' => 'pass-strong',
            'password_confirmation' => 'pass-strong',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'ivan@example.com',
            'role' => User::ROLE_USER,
        ]);
    }

    public function test_register_rejects_short_password(): void
    {
        $this->post('/register', [
            'name' => 'X',
            'email' => 'x@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrors('password');

        $this->assertGuest();
    }

    public function test_login_with_correct_credentials(): void
    {
        User::factory()->create([
            'email' => 'login@example.com',
            'password' => 'mypassword',
        ]);

        $this->post('/login', [
            'email' => 'login@example.com',
            'password' => 'mypassword',
        ])->assertRedirect('/dashboard');

        $this->assertAuthenticated();
    }

    public function test_login_with_wrong_password_fails(): void
    {
        User::factory()->create([
            'email' => 'fail@example.com',
            'password' => 'realpass',
        ]);

        $this->post('/login', [
            'email' => 'fail@example.com',
            'password' => 'WRONG',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_logout_invalidates_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
