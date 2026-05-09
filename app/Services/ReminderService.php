<?php

namespace App\Services;

use App\Models\Goal;
use Illuminate\Support\Carbon;

class ReminderService
{
    /**
     * Собирает контекст для ежедневного напоминания.
     * Возвращает массив:
     *   {
     *     with_deadline: [...],  // активные цели с дедлайном (просроченные + ближайшие $upcomingDaysAhead дней)
     *     no_deadline:  [...]    // активные цели без дедлайна
     *   }
     * или null, если у пользователя вообще нет активных целей,
     * требующих внимания (тогда напоминание не шлётся вовсе).
     *
     * Каждый элемент with_deadline имеет tier (red/amber/gray) и
     * status_text — то же зонирование, что и на дашборде в секции
     * «Ближайшие дедлайны».
     */
    public function buildSummary(int $userId, int $upcomingDaysAhead = 10): ?array
    {
        $today = Carbon::today();
        $until = $today->copy()->addDays($upcomingDaysAhead);

        $allActive = Goal::query()
            ->where('user_id', $userId)
            ->where('status', 'active')
            ->with('category')
            ->get();

        $withDeadline = $allActive
            ->filter(fn (Goal $g) => $g->deadline !== null && $g->deadline->lte($until))
            ->sortBy('deadline')
            ->values();

        $noDeadline = $allActive
            ->filter(fn (Goal $g) => $g->deadline === null)
            ->sortBy('created_at')
            ->values();

        if ($withDeadline->isEmpty() && $noDeadline->isEmpty()) {
            return null;
        }

        return [
            'with_deadline' => $withDeadline->map(fn (Goal $g) => $this->serializeWithDeadline($g))->all(),
            'no_deadline' => $noDeadline->map(fn (Goal $g) => $this->serializeNoDeadline($g))->all(),
        ];
    }

    private function serializeWithDeadline(Goal $g): array
    {
        $days = (int) ceil(Carbon::now()->diffInDays($g->deadline, false));

        if ($days < 0) {
            $tier = 'red';
            $statusText = 'просрочено';
        } elseif ($days < 3) {
            $tier = 'red';
            $statusText = match ($days) {
                0 => 'сегодня',
                1 => 'завтра',
                default => "через {$days} дн.",
            };
        } elseif ($days < 7) {
            $tier = 'amber';
            $statusText = "через {$days} дн.";
        } else {
            $tier = 'gray';
            $statusText = "через {$days} дн.";
        }

        return [
            'id' => $g->id,
            'title' => $g->title,
            'category' => $g->category?->label,
            'deadline' => $g->deadline->format('d.m.Y'),
            'days_left' => $days,
            'tier' => $tier,
            'status_text' => $statusText,
        ];
    }

    private function serializeNoDeadline(Goal $g): array
    {
        return [
            'id' => $g->id,
            'title' => $g->title,
            'category' => $g->category?->label,
        ];
    }
}
