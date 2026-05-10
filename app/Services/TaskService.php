<?php

namespace App\Services;

use App\Models\Subtask;
use App\Models\Task;
use App\Models\User;
use App\Repositories\SubtaskRepository;
use App\Repositories\TaskRepository;
use Illuminate\Support\Facades\Gate;

class TaskService
{
    public function __construct(
        private readonly TaskRepository $repo,
        private readonly SubtaskRepository $subtaskRepo,
    ) {
    }

    public function create(int $subtaskId, User $user, array $data): Task
    {
        $subtask = $this->findOwnedSubtask($subtaskId, $user);

        return $this->repo->create([
            'subtask_id' => $subtask->id,
            'title' => $data['title'],
            'is_done' => false,
            'completed_at' => null,
            'position' => $this->repo->nextPosition($subtask->id),
        ]);
    }

    public function update(int $taskId, User $user, array $data): Task
    {
        $task = $this->findAuthorized($taskId, $user, 'update');
        $task->fill(['title' => $data['title']]);

        return $this->repo->save($task);
    }

    public function toggle(int $taskId, User $user): Task
    {
        $task = $this->findAuthorized($taskId, $user, 'toggle');
        $task->is_done = ! $task->is_done;
        $task->completed_at = $task->is_done ? now() : null;

        return $this->repo->save($task);
    }

    public function delete(int $taskId, User $user): void
    {
        $task = $this->findAuthorized($taskId, $user, 'delete');
        $this->repo->delete($task);
    }

    public function findOwned(int $taskId, User $user): Task
    {
        return $this->findAuthorized($taskId, $user, 'view');
    }

    private function findAuthorized(int $taskId, User $user, string $ability): Task
    {
        $task = $this->repo->findWithChain($taskId);

        if (! $task) {
            abort(404);
        }

        Gate::forUser($user)->authorize($ability, $task);

        return $task;
    }

    private function findOwnedSubtask(int $subtaskId, User $user): Subtask
    {
        $subtask = $this->subtaskRepo->findWithGoal($subtaskId);

        if (! $subtask) {
            abort(404);
        }

        Gate::forUser($user)->authorize('update', $subtask);

        return $subtask;
    }
}
