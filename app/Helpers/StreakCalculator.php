<?php

namespace App\Helpers;

use App\Models\Task;
use Illuminate\Support\Carbon;

/**
 * Единый источник истины для расчёта серии дней с активностью.
 * До Этапа 11 алгоритм был скопирован в трёх сервисах
 * (StatsService, AchievementService, RecommendationService) —
 * это создавало риск рассинхрона при правках.
 */
class StreakCalculator
{
    /**
     * Серия подряд идущих дней с хотя бы одной завершённой задачей,
     * считая от сегодня (или вчера, если сегодня пусто). Возвращает
     * 0 если активности не было ни вчера, ни сегодня.
     */
    public function compute(int $userId): int
    {
        $dates = Task::query()
            ->whereHas('subtask.goal', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('completed_at')
            ->selectRaw('DATE(completed_at) as d')
            ->distinct()
            ->orderByDesc('d')
            ->pluck('d')
            ->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->all();

        if (empty($dates)) {
            return 0;
        }

        $set = array_flip($dates);
        $cursor = Carbon::today();

        // Если сегодня нет активности, серия может всё ещё идти, считая от вчера.
        if (! isset($set[$cursor->toDateString()])) {
            $cursor = $cursor->subDay();
            if (! isset($set[$cursor->toDateString()])) {
                return 0;
            }
        }

        $streak = 0;
        while (isset($set[$cursor->toDateString()])) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }
}
