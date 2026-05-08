<?php

namespace App\Services;

use App\Models\Goal;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StatsService
{
    /**
     * Полный snapshot для дашборда: счётчики, серия, активность за 30 дней,
     * ближайшие дедлайны. Один публичный метод — контроллер не должен
     * собирать вью из 5 разных вызовов.
     */
    public function dashboard(int $userId): array
    {
        $goals = Goal::query()
            ->forUser($userId)
            ->with('subtasks.tasks')
            ->get();

        $byStatus = $goals->countBy('status');

        $tasks = $goals->flatMap->subtasks->flatMap->tasks;
        $totalTasks = $tasks->count();
        $doneTasks = $tasks->where('is_done', true)->count();

        return [
            'active_goals' => $byStatus->get('active', 0),
            'completed_goals' => $byStatus->get('completed', 0),
            'archived_goals' => $byStatus->get('archived', 0),
            'total_tasks' => $totalTasks,
            'done_tasks' => $doneTasks,
            'overall_progress' => $totalTasks === 0 ? 0 : (int) round($doneTasks / $totalTasks * 100),
            'streak' => $this->currentStreak($userId),
            'activity_30d' => $this->activity($userId, 30),
            'upcoming_deadlines' => $this->upcomingDeadlines($goals, 14),
        ];
    }

    /**
     * Серия подряд идущих дней с хотя бы одной завершённой задачей,
     * считая от сегодня (или вчера, если сегодня пусто).
     */
    private function currentStreak(int $userId): int
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

    /**
     * Массив [['date' => 'YYYY-MM-DD', 'count' => N], ...] длиной $days,
     * заполненный нулями для дней без активности (для ровного графика).
     */
    private function activity(int $userId, int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = Task::query()
            ->whereHas('subtask.goal', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd')
            ->all();

        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $result[] = ['date' => $date, 'count' => (int) ($rows[$date] ?? 0)];
        }

        return $result;
    }

    /**
     * Активные цели с дедлайном в ближайшие $daysAhead дней или просроченные.
     * Уже отсортированы по дате (ближайший сверху, потом просроченные).
     */
    private function upcomingDeadlines(Collection $goals, int $daysAhead): Collection
    {
        $until = Carbon::today()->addDays($daysAhead);

        return $goals
            ->where('status', 'active')
            ->whereNotNull('deadline')
            ->filter(fn (Goal $g) => $g->deadline->lte($until))
            ->sortBy('deadline')
            ->values();
    }
}
