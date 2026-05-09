<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DailyReminder extends Notification
{
    use Queueable;

    /**
     * @param  array  $summary  ['overdue' => [...], 'upcoming' => [...]]
     */
    public function __construct(public readonly array $summary)
    {
    }

    /**
     * Отправляем в БД (для bell-иконки) всегда.
     * Email — только если пользователь его не отключил.
     */
    public function via(object $notifiable): array
    {
        return $notifiable->email_reminders_enabled
            ? ['database', 'mail']
            : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        // Используем явные view-шаблоны, а не цепочку ->line(). Цепочка
        // рендерится через Markdown → commonmark, который требует
        // mb_strcut() — функция не всегда есть в Windows-сборках PHP.
        return (new MailMessage())
            ->subject('Напоминание о ваших целях — '.config('app.name'))
            ->view(
                ['emails.daily-reminder', 'emails.daily-reminder-text'],
                [
                    'name' => $notifiable->name,
                    'summary' => $this->summary,
                    'goalsUrl' => route('goals.index'),
                ]
            );
    }

    /**
     * Что сохраняется в notifications.data — JSON для bell-иконки.
     * Считаем дополнительно overdue_count (tier='red' для просроченных)
     * — bell-dropdown отрисовывает короткое резюме и подсветку.
     */
    public function toArray(object $notifiable): array
    {
        $withDeadline = $this->summary['with_deadline'] ?? [];
        $noDeadline = $this->summary['no_deadline'] ?? [];

        $overdueCount = count(array_filter(
            $withDeadline,
            fn ($g) => ($g['days_left'] ?? 0) < 0
        ));

        return [
            'type' => 'daily_reminder',
            'with_deadline_count' => count($withDeadline),
            'no_deadline_count' => count($noDeadline),
            'overdue_count' => $overdueCount,
            'with_deadline' => $withDeadline,
            'no_deadline' => $noDeadline,
        ];
    }
}
