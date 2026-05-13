<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminStatsService
{
    /**
     * Полный snapshot для админ-дашборда. Только агрегаты, никаких
     * персональных данных (ни имён, ни email, ни id пользователей).
     */
    public function dashboard(): array
    {
        return array_merge(
            $this->userStats(),
            $this->goalStats(),
            $this->taskStats(),
            $this->notificationStats(),
            [
                'registrations_30d' => $this->registrations(30),
                'activity_30d' => $this->activity(30),
                'top_categories' => $this->topCategories(5),
            ],
        );
    }

    /** Метрики по пользователям и ролям. */
    private function userStats(): array
    {
        $byRole = User::query()
            ->selectRaw('role, COUNT(*) as c')
            ->groupBy('role')
            ->pluck('c', 'role');

        $today30 = Carbon::today()->subDays(29);

        return [
            'users_total' => (int) $byRole->sum(),
            'users_admin' => (int) $byRole->get(User::ROLE_ADMIN, 0),
            'users_manager' => (int) $byRole->get(User::ROLE_MANAGER, 0),
            'users_regular' => (int) $byRole->get(User::ROLE_USER, 0),
            'users_blocked' => User::whereNotNull('blocked_at')->count(),
            'users_new_30d' => User::where('created_at', '>=', $today30)->count(),
            'users_active_30d' => $this->activeUsersIn30Days(),
        ];
    }

    /** Метрики по целям. */
    private function goalStats(): array
    {
        $byStatus = Goal::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $overdue = Goal::query()
            ->where('status', Goal::STATUS_ACTIVE)
            ->whereNotNull('deadline')
            ->where('deadline', '<', Carbon::today())
            ->count();

        return [
            'goals_total' => (int) $byStatus->sum(),
            'goals_active' => (int) $byStatus->get(Goal::STATUS_ACTIVE, 0),
            'goals_completed' => (int) $byStatus->get(Goal::STATUS_COMPLETED, 0),
            'goals_archived' => (int) $byStatus->get(Goal::STATUS_ARCHIVED, 0),
            'goals_overdue' => $overdue,
        ];
    }

    /** Метрики по задачам. */
    private function taskStats(): array
    {
        $total = Task::count();
        $done = Task::done()->count();
        $today = Task::where('is_done', true)
            ->whereDate('completed_at', Carbon::today())
            ->count();
        $week = Task::where('is_done', true)
            ->where('completed_at', '>=', Carbon::today()->subDays(6))
            ->count();

        return [
            'tasks_total' => $total,
            'tasks_done' => $done,
            'tasks_done_today' => $today,
            'tasks_done_week' => $week,
            'tasks_completion_rate' => $total === 0 ? 0 : (int) round($done / $total * 100),
        ];
    }

    /** Метрики по уведомлениям в БД-канале. */
    private function notificationStats(): array
    {
        $today30 = Carbon::today()->subDays(29);

        $total30 = DatabaseNotification::where('created_at', '>=', $today30)->count();
        $unreadTotal = DatabaseNotification::whereNull('read_at')->count();
        $readTotal = DatabaseNotification::whereNotNull('read_at')->count();
        $allTotal = $readTotal + $unreadTotal;

        return [
            'notifications_sent_30d' => $total30,
            'notifications_unread' => $unreadTotal,
            'notifications_total' => $allTotal,
            'notifications_read_rate' => $allTotal === 0 ? 0 : (int) round($readTotal / $allTotal * 100),
        ];
    }

    /**
     * «Активные за 30 дней» — пользователи с хотя бы одной выполненной
     * задачей в последние 30 дней. См. Notes в stage-06-admin-log.md
     * о whereExists vs whereHas — мы намеренно используем явный JOIN.
     */
    private function activeUsersIn30Days(): int
    {
        return User::query()
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('tasks')
                    ->join('subtasks', 'subtasks.id', '=', 'tasks.subtask_id')
                    ->join('goals', 'goals.id', '=', 'subtasks.goal_id')
                    ->whereColumn('goals.user_id', 'users.id')
                    ->whereNotNull('tasks.completed_at')
                    ->where('tasks.completed_at', '>=', Carbon::today()->subDays(29));
            })
            ->count();
    }

    /** Регистрации по дням за N дней — массив [{date, count}]. */
    private function registrations(int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = User::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd')
            ->all();

        return $this->fillDateSeries($start, $days, $rows);
    }

    /** Активность (выполнение задач) по дням за N дней. */
    private function activity(int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = Task::query()
            ->where('is_done', true)
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $start)
            ->selectRaw('DATE(completed_at) as d, COUNT(*) as c')
            ->groupBy('d')
            ->pluck('c', 'd')
            ->all();

        return $this->fillDateSeries($start, $days, $rows);
    }

    /**
     * Превращает разреженный массив `[YYYY-MM-DD => count]` в плотный
     * массив длины $days, заполненный нулями для пустых дней.
     * Нужен для ровного bar-chart без «прыжков».
     */
    private function fillDateSeries(Carbon $start, int $days, array $rows): array
    {
        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->toDateString();
            $result[] = ['date' => $date, 'count' => (int) ($rows[$date] ?? 0)];
        }

        return $result;
    }

    /**
     * Топ-N категорий по числу целей. Системные и пользовательские
     * вместе, агрегаты по label/color (без user_id, без id —
     * не утекают связи кто чьё).
     */
    private function topCategories(int $limit): array
    {
        return Category::query()
            ->select('categories.label', 'categories.color', 'categories.is_system')
            ->selectRaw('COUNT(goals.id) as goals_count')
            ->leftJoin('goals', 'goals.category_id', '=', 'categories.id')
            ->groupBy('categories.id', 'categories.label', 'categories.color', 'categories.is_system')
            ->orderByDesc('goals_count')
            ->limit($limit)
            ->get()
            ->map(fn ($row) => [
                'label' => $row->label,
                'color' => $row->color,
                'is_system' => (bool) $row->is_system,
                'goals_count' => (int) $row->goals_count,
            ])
            ->all();
    }
}
