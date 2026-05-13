<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\User;
use App\Repositories\GoalRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;

class AdminGoalService
{
    public function __construct(private readonly GoalRepository $repo)
    {
    }

    /**
     * Пагинированный список целей для admin-раздела.
     * Принимает массив фильтров: search, status, user_id, overdue.
     */
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Goal::query()
            ->with(['user:id,name,email', 'category:id,label,color'])
            ->orderByDesc('created_at');

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%' . $filters['search'] . '%');
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['overdue'])) {
            $query->where('status', Goal::STATUS_ACTIVE)
                  ->whereNotNull('deadline')
                  ->where('deadline', '<', now()->toDateString());
        }

        return $query->paginate($perPage)->withQueryString();
    }

    public function findWithRelations(int $goalId): Goal
    {
        $goal = $this->repo->findWithRelations($goalId);

        if (! $goal) {
            abort(404);
        }

        return $goal;
    }

    public function staffArchive(User $actor, int $goalId): Goal
    {
        $goal = $this->repo->findById($goalId) ?? abort(404);
        Gate::forUser($actor)->authorize('staffArchive', $goal);

        $goal->status = Goal::STATUS_ARCHIVED;
        $goal->archived_at = now();

        return $this->repo->save($goal);
    }

    public function staffRestore(User $actor, int $goalId): Goal
    {
        $goal = $this->repo->findById($goalId) ?? abort(404);
        Gate::forUser($actor)->authorize('staffRestore', $goal);

        $goal->status = Goal::STATUS_ACTIVE;
        $goal->archived_at = null;

        return $this->repo->save($goal);
    }

    public function staffDelete(User $actor, int $goalId): void
    {
        $goal = $this->repo->findById($goalId) ?? abort(404);
        Gate::forUser($actor)->authorize('staffDelete', $goal);

        $this->repo->delete($goal);
    }
}
