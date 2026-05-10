<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Goal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoalTest extends TestCase
{
    use RefreshDatabase;

    private function systemCategoryId(): int
    {
        return Category::where('is_system', true)->first()->id;
    }

    public function test_user_can_create_goal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/goals', [
            'title' => 'Тестовая цель',
            'category_id' => $this->systemCategoryId(),
            'description' => 'описание',
        ])->assertRedirect();

        $this->assertDatabaseHas('goals', [
            'user_id' => $user->id,
            'title' => 'Тестовая цель',
            'status' => 'active',
        ]);
    }

    public function test_create_rejects_empty_title(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/goals', [
            'title' => '',
            'category_id' => $this->systemCategoryId(),
        ])->assertSessionHasErrors('title');
    }

    public function test_user_can_edit_own_goal(): void
    {
        $user = User::factory()->create();
        $goal = Goal::create([
            'user_id' => $user->id,
            'category_id' => $this->systemCategoryId(),
            'title' => 'Старое',
            'status' => 'active',
        ]);

        $this->actingAs($user)->patch("/goals/{$goal->id}", [
            'title' => 'Новое',
            'category_id' => $this->systemCategoryId(),
        ])->assertRedirect();

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'title' => 'Новое',
        ]);
    }

    public function test_user_cannot_view_other_users_goal(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $goal = Goal::create([
            'user_id' => $owner->id,
            'category_id' => $this->systemCategoryId(),
            'title' => 'Чужая',
            'status' => 'active',
        ]);

        $this->actingAs($intruder)
            ->get("/goals/{$goal->id}")
            ->assertForbidden();
    }

    public function test_archive_changes_status(): void
    {
        $user = User::factory()->create();
        $goal = Goal::create([
            'user_id' => $user->id,
            'category_id' => $this->systemCategoryId(),
            'title' => 'В архив',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post("/goals/{$goal->id}/archive")
            ->assertRedirect();

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'status' => 'archived',
        ]);
    }

    public function test_complete_changes_status_to_completed(): void
    {
        $user = User::factory()->create();
        $goal = Goal::create([
            'user_id' => $user->id,
            'category_id' => $this->systemCategoryId(),
            'title' => 'Закрыть',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->post("/goals/{$goal->id}/complete")
            ->assertRedirect();

        $this->assertDatabaseHas('goals', [
            'id' => $goal->id,
            'status' => 'completed',
        ]);
    }
}
