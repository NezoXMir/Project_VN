<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\Subtask;
use App\Models\User;
use App\Repositories\GoalRepository;
use App\Repositories\SubtaskRepository;
use Illuminate\Support\Facades\Gate;

class SubtaskService
{
    public function __construct(
        private readonly SubtaskRepository $repo,
        private readonly GoalRepository $goalRepo,
    ) {
    }

    public function create(int $goalId, User $user, array $data): Subtask
    {
        $goal = $this->findOwnedGoal($goalId, $user);

        return $this->repo->create([
            'goal_id' => $goal->id,
            'title' => $data['title'],
            'position' => $this->repo->nextPosition($goal->id),
        ]);
    }

    public function update(int $subtaskId, User $user, array $data): Subtask
    {
        $subtask = $this->findAuthorized($subtaskId, $user, 'update');
        $subtask->fill(['title' => $data['title']]);

        return $this->repo->save($subtask);
    }

    public function delete(int $subtaskId, User $user): void
    {
        $subtask = $this->findAuthorized($subtaskId, $user, 'delete');
        $this->repo->delete($subtask);
    }

    public function findOwned(int $subtaskId, User $user): Subtask
    {
        return $this->findAuthorized($subtaskId, $user, 'view');
    }

    private function findAuthorized(int $subtaskId, User $user, string $ability): Subtask
    {
        $subtask = $this->repo->findWithGoal($subtaskId);

        if (! $subtask) {
            abort(404);
        }

        Gate::forUser($user)->authorize($ability, $subtask);

        return $subtask;
    }

    private function findOwnedGoal(int $goalId, User $user): Goal
    {
        $goal = $this->goalRepo->findById($goalId);

        if (! $goal) {
            abort(404);
        }

        Gate::forUser($user)->authorize('update', $goal);

        return $goal;
    }
}
