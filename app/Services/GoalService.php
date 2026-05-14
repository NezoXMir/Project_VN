<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\User;
use App\Repositories\GoalRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;

class GoalService
{
    public function __construct(private readonly GoalRepository $repo)
    {
    }

    public function listForUser(int $userId): Collection
    {
        return $this->repo->listForUser($userId);
    }

    public function create(int $userId, array $data): Goal
    {
        return $this->repo->create([
            'user_id' => $userId,
            'category_id' => (int) $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'deadline' => $data['deadline'] ?? null,
            'status' => Goal::STATUS_ACTIVE,
        ]);
    }

    public function update(int $goalId, User $user, array $data): Goal
    {
        $goal = $this->findAuthorized($goalId, $user, 'update');

        $goal->fill([
            'category_id' => (int) $data['category_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'deadline' => $data['deadline'] ?? null,
        ]);

        return $this->repo->save($goal);
    }

    public function archive(int $goalId, User $user): Goal
    {
        $goal = $this->findAuthorized($goalId, $user, 'archive');
        $goal->status = Goal::STATUS_ARCHIVED;
        $goal->archived_at = now();

        return $this->repo->save($goal);
    }

    public function complete(int $goalId, User $user): Goal
    {
        $goal = $this->findAuthorized($goalId, $user, 'complete');
        $goal->status = Goal::STATUS_COMPLETED;

        return $this->repo->save($goal);
    }

    /**
     * Восстановление цели из архива. archived_at очищается,
     * статус возвращается в active — UI/админка трактуют это
     * как «свежая активная цель».
     */
    public function restore(int $goalId, User $user): Goal
    {
        $goal = $this->findAuthorized($goalId, $user, 'restore');
        $goal->status = Goal::STATUS_ACTIVE;
        $goal->archived_at = null;

        return $this->repo->save($goal);
    }

    public function delete(int $goalId, User $user): void
    {
        $goal = $this->findAuthorized($goalId, $user, 'delete');
        $this->repo->delete($goal);
    }

    public function get(int $goalId, User $user): Goal
    {
        return $this->findAuthorized($goalId, $user, 'view');
    }

    /**
     * 404 если цели нет, 403 (через AuthorizationException) —
     * если правило в GoalPolicy не пропустило юзера.
     */
    private function findAuthorized(int $goalId, User $user, string $ability): Goal
    {
        $goal = $this->repo->findWithRelations($goalId);

        if (! $goal) {
            abort(404);
        }

        Gate::forUser($user)->authorize($ability, $goal);

        return $goal;
    }
}
