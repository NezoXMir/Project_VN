<?php

namespace App\Repositories;

use App\Models\Task;

class TaskRepository
{
    public function findWithChain(int $id): ?Task
    {
        return Task::with('subtask.goal')->find($id);
    }

    public function nextPosition(int $subtaskId): int
    {
        return (int) Task::query()
            ->where('subtask_id', $subtaskId)
            ->max('position') + 1;
    }

    public function create(array $attrs): Task
    {
        return Task::create($attrs);
    }

    public function save(Task $task): Task
    {
        $task->save();

        return $task;
    }

    public function delete(Task $task): void
    {
        $task->delete();
    }
}
