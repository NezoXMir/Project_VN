<?php

namespace App\Services;

use App\Models\Subtask;
use App\Models\Task;

class TaskService
{
    public function create(int $subtaskId, int $userId, array $data): Task
    {
        $subtask = $this->findOwnedSubtask($subtaskId, $userId);

        $position = (int) Task::query()
            ->where('subtask_id', $subtask->id)
            ->max('position') + 1;

        return Task::create([
            'subtask_id' => $subtask->id,
            'title' => $data['title'],
            'is_done' => false,
            'completed_at' => null,
            'position' => $position,
        ]);
    }

    public function update(int $taskId, int $userId, array $data): Task
    {
        $task = $this->findOwned($taskId, $userId);
        $task->fill(['title' => $data['title']])->save();

        return $task;
    }

    public function toggle(int $taskId, int $userId): Task
    {
        $task = $this->findOwned($taskId, $userId);
        $task->is_done = ! $task->is_done;
        $task->completed_at = $task->is_done ? now() : null;
        $task->save();

        return $task;
    }

    public function delete(int $taskId, int $userId): void
    {
        $task = $this->findOwned($taskId, $userId);
        $task->delete();
    }

    public function findOwned(int $taskId, int $userId): Task
    {
        $task = Task::with('subtask.goal')->find($taskId);

        if (! $task) {
            abort(404);
        }

        if ($task->subtask->goal->user_id !== $userId) {
            abort(403);
        }

        return $task;
    }

    private function findOwnedSubtask(int $subtaskId, int $userId): Subtask
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
}
