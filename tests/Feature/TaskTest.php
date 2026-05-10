<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Goal;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private function setupGoalWithSubtask(User $user): Subtask
    {
        $catId = Category::where('is_system', true)->first()->id;
        $goal = Goal::create([
            'user_id' => $user->id,
            'category_id' => $catId,
            'title' => 'Goal',
            'status' => 'active',
        ]);

        return Subtask::create([
            'goal_id' => $goal->id,
            'title' => 'Sub',
            'position' => 1,
        ]);
    }

    public function test_user_can_create_task(): void
    {
        $user = User::factory()->create();
        $subtask = $this->setupGoalWithSubtask($user);

        $this->actingAs($user)
            ->postJson("/subtasks/{$subtask->id}/tasks", [
                'title' => 'Новая задача',
            ])
            ->assertCreated()
            ->assertJsonFragment(['title' => 'Новая задача', 'is_done' => false]);

        $this->assertDatabaseHas('tasks', [
            'subtask_id' => $subtask->id,
            'title' => 'Новая задача',
            'is_done' => false,
        ]);
    }

    public function test_toggle_marks_task_done_and_returns_progress(): void
    {
        $user = User::factory()->create();
        $subtask = $this->setupGoalWithSubtask($user);
        $task = Task::create([
            'subtask_id' => $subtask->id,
            'title' => 'Задача',
            'is_done' => false,
            'position' => 1,
        ]);

        $response = $this->actingAs($user)
            ->postJson("/tasks/{$task->id}/toggle");

        $response->assertOk()
            ->assertJsonFragment(['is_done' => true, 'goal_progress' => 100]);

        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'is_done' => true,
        ]);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_toggle_again_clears_done_state(): void
    {
        $user = User::factory()->create();
        $subtask = $this->setupGoalWithSubtask($user);
        $task = Task::create([
            'subtask_id' => $subtask->id,
            'title' => 'Задача',
            'is_done' => true,
            'completed_at' => now(),
            'position' => 1,
        ]);

        $this->actingAs($user)
            ->postJson("/tasks/{$task->id}/toggle")
            ->assertOk()
            ->assertJsonFragment(['is_done' => false]);

        $this->assertNull($task->fresh()->completed_at);
    }

    public function test_user_cannot_toggle_other_users_task(): void
    {
        $owner = User::factory()->create();
        $subtask = $this->setupGoalWithSubtask($owner);
        $task = Task::create([
            'subtask_id' => $subtask->id,
            'title' => 'Чужая задача',
            'is_done' => false,
            'position' => 1,
        ]);

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->postJson("/tasks/{$task->id}/toggle")
            ->assertForbidden();
    }
}
