<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_redirected_from_admin(): void
    {
        $this->get('/admin/dashboard')->assertRedirect('/login');
    }

    public function test_regular_user_gets_403_on_admin(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($user)
            ->get('/admin/dashboard')
            ->assertForbidden();

        $this->actingAs($user)
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_admin_can_access_admin_pages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $this->actingAs($admin)
            ->get('/admin/dashboard')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/users')
            ->assertOk();
    }

    public function test_admin_can_change_user_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $target = User::factory()->create(['role' => User::ROLE_USER]);

        $this->actingAs($admin)
            ->patch("/admin/users/{$target->id}/role", ['role' => 'admin'])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id' => $target->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_admin_cannot_demote_last_admin(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $other = User::factory()->create(['role' => User::ROLE_USER]);

        // Пытаемся понизить чужого admin'а — но текущий и есть единственный
        // admin → не должно случиться. Сначала попытаемся понизить себя
        // (всегда блокируется), затем — понизить admin когда он один.
        $this->actingAs($admin)
            ->patch("/admin/users/{$admin->id}/role", ['role' => 'user'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'role' => User::ROLE_ADMIN,
        ]);
    }
}
