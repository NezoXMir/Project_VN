<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubtaskRequest;
use App\Services\SubtaskService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class SubtaskController extends Controller
{
    public function __construct(private readonly SubtaskService $subtasks)
    {
    }

    public function store(SubtaskRequest $request, int $goal): JsonResponse
    {
        $subtask = $this->subtasks->create($goal, (int) Auth::id(), $request->validated());

        return response()->json([
            'id' => $subtask->id,
            'goal_id' => $subtask->goal_id,
            'title' => $subtask->title,
            'position' => $subtask->position,
            'tasks' => [],
        ], 201);
    }

    public function update(SubtaskRequest $request, int $subtask): JsonResponse
    {
        $model = $this->subtasks->update($subtask, (int) Auth::id(), $request->validated());

        return response()->json([
            'id' => $model->id,
            'title' => $model->title,
        ]);
    }

    public function destroy(int $subtask): JsonResponse
    {
        $this->subtasks->delete($subtask, (int) Auth::id());

        return response()->json(['ok' => true]);
    }
}
