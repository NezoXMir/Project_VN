<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\Subtask;

class SubtaskService
{
    public function create(int $goalId, int $userId, array $data): Subtask
    {
        $goal = $this->findOwnedGoal($goalId, $userId);

        $position = (int) Subtask::query()
            ->where('goal_id', $goal->id)
            ->max('position') + 1;

        return Subtask::create([
            'goal_id' => $goal->id,
            'title' => $data['title'],
            'position' => $position,
        ]);
    }

    public function update(int $subtaskId, int $userId, array $data): Subtask
    {
        $subtask = $this->findOwned($subtaskId, $userId);
        $subtask->fill(['title' => $data['title']])->save();

        return $subtask;
    }

    public function delete(int $subtaskId, int $userId): void
    {
        $subtask = $this->findOwned($subtaskId, $userId);
        $subtask->delete();
    }

    public function findOwned(int $subtaskId, int $userId): Subtask
    {
        $subtask = Subtask::with('goal')->find($subtaskId);

        if (! $subtask) {
            abort(404);
        }

        if ($subtask->goal->user_id !== $userId) {
            abort(403);
        }

        return $subtask;
    }

    private function findOwnedGoal(int $goalId, int $userId): Goal
    {
        $goal = Goal::find($goalId);

        if (! $goal) {
            abort(404);
        }

        if ($goal->user_id !== $userId) {
            abort(403);
        }

        return $goal;
    }
}
