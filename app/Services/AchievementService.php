<?php

namespace App\Services;

use App\Models\Achievement;
use App\Models\Category;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AchievementService
{
    /**
     * Проверяет все 8 правил и разблокирует те, что юзер заслужил
     * и которые ещё не разблокированы. Возвращает массив только что
     * разблокированных Achievement-моделей (для toast-уведомлений).
     */
    public function check(int $userId): array
    {
        $unlockedCodes = DB::table('user_achievements')
            ->join('achievements', 'achievements.id', '=', 'user_achievements.achievement_id')
            ->where('user_achievements.user_id', $userId)
            ->pluck('achievements.code')
            ->all();

        $unlockedSet = array_flip($unlockedCodes);

        $rules = [
            'first_task'   => fn () => $this->checkTaskCount($userId, 1),
            'tasks_10'     => fn () => $this->checkTaskCount($userId, 10),
            'tasks_100'    => fn () => $this->checkTaskCount($userId, 100),
            'first_goal'   => fn () => $this->checkGoalCount($userId, 1),
            'goals_5'      => fn () => $this->checkGoalCount($userId, 5),
            'streak_7'     => fn () => $this->checkStreak($userId, 7),
            'streak_30'   => fn () => $this->checkStreak($userId, 30),
            'own_category' => fn () => $this->checkOwnCategory($userId),
        ];

        $newlyUnlocked = [];
        foreach ($rules as $code => $check) {
            if (isset($unlockedSet[$code])) {
                continue; // уже разблокировано
            }
            if ($check()) {
                $achievement = Achievement::where('code', $code)->first();
                if ($achievement === null) {
                    continue; // на случай рассинхрона миграции и сервиса
                }
                DB::table('user_achievements')->insert([
                    'user_id' => $userId,
                    'achievement_id' => $achievement->id,
                    'unlocked_at' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $newlyUnlocked[] = $achievement;
            }
        }

        return $newlyUnlocked;
    }

    /**
     * Текущий прогресс пользователя по каждому правилу — для отображения
     * мини-бара под заблокированными бейджами на странице достижений.
     * Возвращает массив [code => ['current' => N, 'threshold' => M]].
     * `current` ограничен `threshold` — нет смысла показывать «150 / 100».
     */
    public function progressFor(int $userId): array
    {
        $tasksDone = Task::query()
            ->whereHas('subtask.goal', fn ($q) => $q->where('user_id', $userId))
            ->where('is_done', true)
            ->count();

        $goalsCompleted = Goal::query()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        $streak = $this->currentStreak($userId);

        $hasOwnCategory = Category::query()
            ->where('user_id', $userId)
            ->where('is_system', false)
            ->exists() ? 1 : 0;

        $rules = [
            'first_task'   => ['current' => $tasksDone,      'threshold' => 1],
            'tasks_10'     => ['current' => $tasksDone,      'threshold' => 10],
            'tasks_100'    => ['current' => $tasksDone,      'threshold' => 100],
            'first_goal'   => ['current' => $goalsCompleted, 'threshold' => 1],
            'goals_5'      => ['current' => $goalsCompleted, 'threshold' => 5],
            'streak_7'     => ['current' => $streak,         'threshold' => 7],
            'streak_30'    => ['current' => $streak,         'threshold' => 30],
            'own_category' => ['current' => $hasOwnCategory, 'threshold' => 1],
        ];

        // Cap current на threshold для UI.
        foreach ($rules as &$r) {
            $r['current'] = min($r['current'], $r['threshold']);
            $r['percent'] = $r['threshold'] > 0
                ? (int) round($r['current'] / $r['threshold'] * 100)
                : 0;
        }

        return $rules;
    }

    private function checkTaskCount(int $userId, int $threshold): bool
    {
        $count = Task::query()
            ->whereHas('subtask.goal', fn ($q) => $q->where('user_id', $userId))
            ->where('is_done', true)
            ->count();

        return $count >= $threshold;
    }

    private function checkGoalCount(int $userId, int $threshold): bool
    {
        $count = Goal::query()
            ->where('user_id', $userId)
            ->where('status', 'completed')
            ->count();

        return $count >= $threshold;
    }

    private function checkStreak(int $userId, int $threshold): bool
    {
        return $this->currentStreak($userId) >= $threshold;
    }

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

    private function checkOwnCategory(int $userId): bool
    {
        return Category::query()
            ->where('user_id', $userId)
            ->where('is_system', false)
            ->exists();
    }
}
