<?php

namespace App\Services;

use App\Helpers\StreakCalculator;
use App\Models\Goal;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecommendationService
{
    private const SEVERITY_INFO = 'info';
    private const SEVERITY_WARNING = 'warning';
    private const SEVERITY_DANGER = 'danger';

    public function __construct(private readonly StreakCalculator $streak)
    {
    }

    /**
     * Анализирует состояние пользователя по 7 правилам и возвращает
     * массив рекомендаций. Каждая рекомендация — массив с полями
     * type/severity/title/message/action_url/action_label.
     * Возвращаются в порядке серьёзности (danger → warning → info).
     */
    public function generate(int $userId): array
    {
        $goals = Goal::query()
            ->forUser($userId)
            ->with(['subtasks.tasks', 'category'])
            ->get();

        $recommendations = [];

        foreach ([
            'checkOverdueDeadlines',
            'checkNoActivity',
            'checkUpcomingDeadlines',
            'checkLowProgress',
            'checkEmptyGoals',
            'checkTooManyGoals',
            'checkStreak',
        ] as $check) {
            $r = $this->$check($goals, $userId);
            if ($r !== null) {
                $recommendations[] = $r;
            }
        }

        return $recommendations;
    }

    private function checkOverdueDeadlines(Collection $goals, int $userId): ?array
    {
        $today = Carbon::today();
        $overdue = $goals
            ->where('status', 'active')
            ->filter(fn (Goal $g) => $g->deadline && $g->deadline->lt($today))
            ->values();

        if ($overdue->isEmpty()) {
            return null;
        }

        $count = $overdue->count();
        $message = $count === 1
            ? "Цель «{$overdue->first()->title}» просрочена. Перенесите дедлайн или завершите задачи."
            : "У вас {$count} ".trans_choice('цель|цели|целей', $count).' с просроченным дедлайном. Перенесите даты или завершите задачи.';

        return [
            'type' => 'overdue_deadlines',
            'severity' => self::SEVERITY_DANGER,
            'title' => 'Просроченные дедлайны',
            'message' => $message,
            'action_url' => route('goals.index'),
            'action_label' => 'К целям',
        ];
    }

    private function checkUpcomingDeadlines(Collection $goals, int $userId): ?array
    {
        $today = Carbon::today();
        $threeDays = $today->copy()->addDays(3);

        $upcoming = $goals
            ->where('status', 'active')
            ->filter(fn (Goal $g) =>
                $g->deadline
                && $g->deadline->gte($today)
                && $g->deadline->lte($threeDays)
            )
            ->values();

        if ($upcoming->isEmpty()) {
            return null;
        }

        $count = $upcoming->count();
        if ($count === 1) {
            $g = $upcoming->first();
            $days = (int) ceil(Carbon::now()->diffInDays($g->deadline, false));
            $left = \App\Helpers\DateHelper::timeLeftLabel($g->deadline) ?? "{$days} дней";
            $message = "До дедлайна цели «{$g->title}» — {$left}.";
        } else {
            $word = trans_choice('цель|цели|целей', $count);
            $message = "У {$count} {$word} дедлайн в ближайшие 3 дня. Проверьте оставшиеся задачи.";
        }

        return [
            'type' => 'upcoming_deadlines',
            'severity' => self::SEVERITY_WARNING,
            'title' => 'Близкие дедлайны',
            'message' => $message,
            'action_url' => route('goals.index'),
            'action_label' => 'К целям',
        ];
    }

    private function checkLowProgress(Collection $goals, int $userId): ?array
    {
        $threshold = Carbon::today()->subDays(14);

        $stale = $goals
            ->where('status', 'active')
            ->filter(fn (Goal $g) =>
                $g->created_at->lt($threshold)
                && $g->progress < 30
            )
            ->values();

        if ($stale->isEmpty()) {
            return null;
        }

        $count = $stale->count();
        $word = trans_choice('цель|цели|целей', $count);
        $verb = $count === 1 ? 'висит' : 'висят';

        return [
            'type' => 'low_progress',
            'severity' => self::SEVERITY_WARNING,
            'title' => 'Низкий прогресс',
            'message' => "{$count} {$word} {$verb} больше 2 недель с прогрессом ниже 30%. Возможно, стоит разбить на меньшие шаги или пересмотреть подход.",
            'action_url' => route('goals.index'),
            'action_label' => 'Посмотреть',
        ];
    }

    private function checkStreak(Collection $goals, int $userId): ?array
    {
        $streak = $this->streak->compute($userId);
        if ($streak < 3) {
            return null;
        }

        $word = trans_choice('день|дня|дней', $streak);

        return [
            'type' => 'streak',
            'severity' => self::SEVERITY_INFO,
            'title' => 'Серия активности',
            'message' => "Вы закрываете задачи {$streak} {$word} подряд. Так держать!",
            'action_url' => null,
            'action_label' => null,
        ];
    }

    private function checkNoActivity(Collection $goals, int $userId): ?array
    {
        $hasActive = $goals->where('status', 'active')->isNotEmpty();
        if (! $hasActive) {
            return null;
        }

        $cutoff = Carbon::today()->subDays(3);
        $recentDoneCount = Task::query()
            ->whereHas('subtask.goal', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('completed_at')
            ->where('completed_at', '>=', $cutoff)
            ->count();

        if ($recentDoneCount > 0) {
            return null;
        }

        $lastDone = Task::query()
            ->whereHas('subtask.goal', fn ($q) => $q->where('user_id', $userId))
            ->whereNotNull('completed_at')
            ->latest('completed_at')
            ->value('completed_at');

        $message = $lastDone !== null
            ? 'Последняя завершённая задача — '.Carbon::parse($lastDone)->diffForHumans().'. Закройте одну сегодня — серия начнётся заново.'
            : 'У вас есть активные цели, но ни одна задача ещё не завершена. Начните с малого — закройте одну подзадачу сегодня.';

        return [
            'type' => 'no_activity',
            'severity' => self::SEVERITY_DANGER,
            'title' => 'Долгий простой',
            'message' => $message,
            'action_url' => route('goals.index'),
            'action_label' => 'К целям',
        ];
    }

    private function checkEmptyGoals(Collection $goals, int $userId): ?array
    {
        $threshold = Carbon::today()->subDay();

        $empty = $goals
            ->where('status', 'active')
            ->filter(fn (Goal $g) =>
                $g->created_at->lt($threshold)
                && $g->subtasks->isEmpty()
            )
            ->values();

        if ($empty->isEmpty()) {
            return null;
        }

        $count = $empty->count();
        $message = $count === 1
            ? "Цель «{$empty->first()->title}» не разбита на подцели. Добавьте план — без шагов прогресс не считается."
            : "У {$count} ".trans_choice('цели|целей|целей', $count).' нет подцелей. Без плана прогресс не считается.';

        return [
            'type' => 'empty_goals',
            'severity' => self::SEVERITY_WARNING,
            'title' => 'Цели без плана',
            'message' => $message,
            'action_url' => route('goals.index'),
            'action_label' => 'К целям',
        ];
    }

    private function checkTooManyGoals(Collection $goals, int $userId): ?array
    {
        $activeCount = $goals->where('status', 'active')->count();
        if ($activeCount <= 5) {
            return null;
        }

        return [
            'type' => 'too_many_goals',
            'severity' => self::SEVERITY_WARNING,
            'title' => 'Много активных целей',
            'message' => "Активных целей: {$activeCount}. Лучше сфокусироваться на 3–4 ключевых — остальные можно отправить в архив, чтобы не распыляться.",
            'action_url' => route('goals.index'),
            'action_label' => 'К целям',
        ];
    }

}
