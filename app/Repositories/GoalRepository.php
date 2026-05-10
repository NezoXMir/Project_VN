<?php

namespace App\Repositories;

use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;

/**
 * Чистая работа с таблицей `goals`.
 *
 * Бизнес-правила (статусы, владение, авторизация) живут в
 * GoalService — здесь только Eloquent-запросы и базовые
 * мутации, без логики «что можно/нельзя».
 */
class GoalRepository
{
    /**
     * Список целей пользователя в порядке: активные → завершённые →
     * архив, внутри каждой группы свежие сверху. С eager-load
     * категории и иерархии задач (нужно для расчёта прогресса).
     */
    public function listForUser(int $userId): Collection
    {
        return Goal::query()
            ->forUser($userId)
            ->with(['category', 'subtasks.tasks'])
            ->orderByRaw("FIELD(status, 'active', 'completed', 'archived')")
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Найти цель по id с предзагруженными отношениями
     * (категория + подцели по позициям + задачи каждой подцели).
     */
    public function findWithRelations(int $goalId): ?Goal
    {
        return Goal::query()
            ->with([
                'category',
                'subtasks' => fn ($q) => $q->orderBy('position'),
                'subtasks.tasks',
            ])
            ->find($goalId);
    }

    public function findById(int $goalId): ?Goal
    {
        return Goal::find($goalId);
    }

    public function create(array $attrs): Goal
    {
        return Goal::create($attrs);
    }

    public function save(Goal $goal): Goal
    {
        $goal->save();

        return $goal;
    }

    public function delete(Goal $goal): void
    {
        $goal->delete();
    }
}
