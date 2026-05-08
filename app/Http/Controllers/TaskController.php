<?php

namespace App\Http\Controllers;

use App\Http\Requests\TaskRequest;
use App\Services\AchievementService;
use App\Services\TaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function __construct(
        private readonly TaskService $tasks,
        private readonly AchievementService $achievements,
    ) {
    }

    public function store(TaskRequest $request, int $subtask): JsonResponse
    {
        $task = $this->tasks->create($subtask, (int) Auth::id(), $request->validated());

        return response()->json([
            'id' => $task->id,
            'subtask_id' => $task->subtask_id,
            'title' => $task->title,
            'is_done' => $task->is_done,
            'completed_at' => $task->completed_at?->toIso8601String(),
            'position' => $task->position,
        ], 201);
    }

    public function update(TaskRequest $request, int $task): JsonResponse
    {
        $model = $this->tasks->update($task, (int) Auth::id(), $request->validated());

        return response()->json([
            'id' => $model->id,
            'title' => $model->title,
        ]);
    }

    public function toggle(int $task): JsonResponse
    {
        $userId = (int) Auth::id();
        $model = $this->tasks->toggle($task, $userId);
        $goal = $model->subtask->goal;
        $goal->load('subtasks.tasks');

        // Проверяем достижения только при переходе в done — снятие
        // галочки не должно перепроверять (правила всё равно
        // не «откатываются»).
        $unlocked = $model->is_done
            ? $this->achievements->check($userId)
            : [];

        return response()->json([
            'id' => $model->id,
            'is_done' => $model->is_done,
            'completed_at' => $model->completed_at?->toIso8601String(),
            'goal_progress' => $goal->progress,
            'unlocked_achievements' => array_map(fn ($a) => [
                'code' => $a->code,
                'name' => $a->name,
                'icon' => $a->icon,
            ], $unlocked),
        ]);
    }

    public function destroy(int $task): JsonResponse
    {
        $this->tasks->delete($task, (int) Auth::id());

        return response()->json(['ok' => true]);
    }
}
