<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
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
        $usersTotal = User::count();
        $usersAdmin = User::where('role', User::ROLE_ADMIN)->count();
        $usersRegisteredLast30 = User::where('created_at', '>=', Carbon::today()->subDays(29))->count();

        $usersActive30d = User::query()
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

        $goalsByStatus = Goal::query()
            ->selectRaw('status, COUNT(*) as c')
            ->groupBy('status')
            ->pluck('c', 'status');

        $tasksTotal = Task::count();
        $tasksDone = Task::done()->count();

        return [
            'users_total' => $usersTotal,
            'users_admin' => $usersAdmin,
            'users_active_30d' => $usersActive30d,
            'users_new_30d' => $usersRegisteredLast30,
            'goals_active' => (int) $goalsByStatus->get('active', 0),
            'goals_completed' => (int) $goalsByStatus->get('completed', 0),
            'goals_archived' => (int) $goalsByStatus->get('archived', 0),
            'goals_total' => (int) $goalsByStatus->sum(),
            'tasks_total' => $tasksTotal,
            'tasks_done' => $tasksDone,
            'tasks_completion_rate' => $tasksTotal === 0 ? 0 : (int) round($tasksDone / $tasksTotal * 100),
            'registrations_30d' => $this->registrations(30),
            'top_categories' => $this->topCategories(5),
        ];
    }

    /**
     * Массив регистраций по дням за последние $days дней,
     * заполненный нулями для пустых дней.
     */
    private function registrations(int $days): array
    {
        $start = Carbon::today()->subDays($days - 1);

        $rows = User::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')
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
