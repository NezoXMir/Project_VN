<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GoalRequest;
use App\Services\GoalService;
use App\Services\StatsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Demo REST API. Использует те же сессии, что и веб-интерфейс
 * (middleware web + auth) — отдельных JWT/токенов нет, это
 * демонстрация интеграционного контракта, не публичное API.
 */
class GoalApiController extends Controller
{
    public function __construct(
        private readonly GoalService $goals,
        private readonly StatsService $stats,
    ) {
    }

    public function index(): JsonResponse
    {
        $goals = $this->goals->listForUser((int) Auth::id())->map(
            fn ($g) => $this->serializeGoal($g)
        );

        return response()->json(['data' => $goals]);
    }

    public function store(GoalRequest $request): JsonResponse
    {
        $goal = $this->goals->create((int) Auth::id(), $request->validated());

        return response()->json([
            'data' => $this->serializeGoal($goal->load('category')),
        ], 201);
    }

    public function show(int $goal): JsonResponse
    {
        $model = $this->goals->get($goal, Auth::user());

        return response()->json([
            'data' => $this->serializeGoalWithSubtasks($model),
        ]);
    }

    public function userStats(): JsonResponse
    {
        return response()->json([
            'data' => $this->stats->dashboard((int) Auth::id()),
        ]);
    }

    private function serializeGoal($goal): array
    {
        return [
            'id' => $goal->id,
            'title' => $goal->title,
            'description' => $goal->description,
            'status' => $goal->status,
            'progress' => $goal->progress,
            'deadline' => $goal->deadline?->toIso8601String(),
            'category' => $goal->category ? [
                'id' => $goal->category->id,
                'label' => $goal->category->label,
                'color' => $goal->category->color,
            ] : null,
            'created_at' => $goal->created_at->toIso8601String(),
        ];
    }

    private function serializeGoalWithSubtasks($goal): array
    {
        return array_merge($this->serializeGoal($goal), [
            'subtasks' => $goal->subtasks->map(fn ($s) => [
                'id' => $s->id,
                'title' => $s->title,
                'position' => $s->position,
                'tasks' => $s->tasks->map(fn ($t) => [
                    'id' => $t->id,
                    'title' => $t->title,
                    'is_done' => (bool) $t->is_done,
                    'completed_at' => $t->completed_at?->toIso8601String(),
                    'position' => $t->position,
                ])->values(),
            ])->values(),
        ]);
    }
}
