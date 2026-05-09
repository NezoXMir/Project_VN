<?php

namespace App\Services;

use App\Models\Goal;
use Illuminate\Support\Carbon;

class ReminderService
{
    /**
     * Собирает контекст для ежедневного напоминания.
     * Возвращает массив { overdue: [...], upcoming: [...] }
     * или null, если для пользователя нет активных целей с дедлайнами,
     * требующими внимания (тогда напоминание не шлётся вовсе).
     */
    public function buildSummary(int $userId, int $upcomingDaysAhead = 10): ?array
    {
        $today = Carbon::today();
        $until = $today->copy()->addDays($upcomingDaysAhead);

        $goals = Goal::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->whereNotNull('deadline')
            ->with('category')
            ->get();

        $overdue = $goals
            ->filter(fn (Goal $g) => $g->deadline->lt($today))
            ->sortBy('deadline')
            ->values();

        $upcoming = $goals
            ->filter(fn (Goal $g) => $g->deadline->gte($today) && $g->deadline->lte($until))
            ->sortBy('deadline')
            ->values();

        if ($overdue->isEmpty() && $upcoming->isEmpty()) {
            return null;
        }

        $mapGoal = fn (Goal $g) => [
            'id' => $g->id,
            'title' => $g->title,
            'category' => $g->category?->label,
            'deadline' => $g->deadline->format('d.m.Y'),
            'days_left' => (int) ceil(Carbon::now()->diffInDays($g->deadline, false)),
        ];

        return [
            'overdue' => $overdue->map($mapGoal)->all(),
            'upcoming' => $upcoming->map($mapGoal)->all(),
        ];
    }
}
