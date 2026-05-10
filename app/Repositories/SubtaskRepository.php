<?php

namespace App\Repositories;

use App\Models\Subtask;

class SubtaskRepository
{
    public function findWithGoal(int $id): ?Subtask
    {
        return Subtask::with('goal')->find($id);
    }

    public function nextPosition(int $goalId): int
    {
        return (int) Subtask::query()
            ->where('goal_id', $goalId)
            ->max('position') + 1;
    }

    public function create(array $attrs): Subtask
    {
        return Subtask::create($attrs);
    }

    public function save(Subtask $subtask): Subtask
    {
        $subtask->save();

        return $subtask;
    }

    public function delete(Subtask $subtask): void
    {
        $subtask->delete();
    }
}
