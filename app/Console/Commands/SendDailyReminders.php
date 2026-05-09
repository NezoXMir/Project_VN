<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\DailyReminder;
use App\Services\ReminderService;
use Illuminate\Console\Command;
use Throwable;

class SendDailyReminders extends Command
{
    protected $signature = 'reminders:send {--user= : Email конкретного пользователя для тестовой отправки}';

    protected $description = 'Отправляет ежедневные напоминания о целях (просроченные + ближайшие 3 дня)';

    public function handle(ReminderService $reminders): int
    {
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        $query = User::query();
        if ($email = $this->option('user')) {
            $query->where('email', $email);
        }

        $query->chunk(100, function ($users) use ($reminders, &$sent, &$skipped, &$failed) {
            foreach ($users as $user) {
                $summary = $reminders->buildSummary($user->id);

                if ($summary === null) {
                    $skipped++;

                    continue;
                }

                try {
                    $user->notify(new DailyReminder($summary));
                    $sent++;
                } catch (Throwable $e) {
                    $failed++;
                    $this->warn("  ✗ {$user->email}: {$e->getMessage()}");
                }

                // Пауза 2 сек между отправками — вписывается в free-лимит
                // Mailtrap (~3 письма / 10 сек). Для production под нагрузку
                // правильнее перевести Notification на queue.
                usleep(2_000_000);
            }
        });

        $this->info("Готово. Отправлено: {$sent}, пропущено (нет дедлайнов): {$skipped}, ошибок: {$failed}.");

        return $failed > 0 && $sent === 0 ? self::FAILURE : self::SUCCESS;
    }
}
