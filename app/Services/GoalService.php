<?php

namespace App\Services;

use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;

class GoalService
{
    public function listForUser(int $userId): Collection
    {
        return Goal::query()
            ->forUser($userId)
            ->with(['subtasks.tasks'])
            ->orderByRaw("FIELD(status, 'active', 'completed', 'archived')")
            ->orderByDesc('created_at')
            ->get();
    }

    public function create(int $userId, array $data): Goal
    {
        return Goal::create([
            'user_id' => $userId,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'deadline' => $data['deadline'] ?? null,
            'status' => Goal::STATUS_ACTIVE,
        ]);
    }

    public function update(int $goalId, int $userId, array $data): Goal
    {
        $goal = $this->findOwned($goalId, $userId);

        $goal->fill([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'category' => $data['category'],
            'deadline' => $data['deadline'] ?? null,
        ])->save();

        return $goal;
    }

    public function archive(int $goalId, int $userId): Goal
    {
        $goal = $this->findOwned($goalId, $userId);
        $goal->status = Goal::STATUS_ARCHIVED;
        $goal->save();

        return $goal;
    }

    public function complete(int $goalId, int $userId): Goal
    {
        $goal = $this->findOwned($goalId, $userId);
        $goal->status = Goal::STATUS_COMPLETED;
        $goal->save();

        return $goal;
    }

    public function delete(int $goalId, int $userId): void
    {
        $goal = $this->findOwned($goalId, $userId);
        $goal->delete();
    }

    public function get(int $goalId, int $userId): Goal
    {
        return $this->findOwned($goalId, $userId);
    }

    private function findOwned(int $goalId, int $userId): Goal
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
