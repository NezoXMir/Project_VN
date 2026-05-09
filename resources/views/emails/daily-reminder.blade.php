<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Напоминание о целях</title>
</head>
<body style="font-family: -apple-system, Segoe UI, Roboto, Arial, sans-serif; color: #1f2937; line-height: 1.6; padding: 24px; background: #f9fafb; margin: 0;">
    <div style="max-width: 600px; margin: 0 auto; background: white; padding: 32px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">

        <h2 style="margin-top: 0; margin-bottom: 8px; color: #4F46E5; font-size: 22px;">
            Привет, {{ $name }}!
        </h2>
        <p style="margin-top: 0; color: #6b7280; font-size: 14px;">
            Каждый день — маленький шаг к вашим целям. Вот что важно держать в фокусе сегодня.
        </p>

        @if (! empty($summary['overdue']))
            <div style="margin-top: 28px; padding: 20px; background: #fef2f2; border-left: 4px solid #ef4444; border-radius: 6px;">
                <h3 style="margin: 0 0 6px 0; color: #991b1b; font-size: 16px;">
                    Дедлайны, которые уже прошли
                </h3>
                <p style="margin: 0 0 12px 0; color: #6b7280; font-size: 13px;">
                    Бывает — время уходит быстрее, чем планируется. Ничего страшного:
                    перенесите дату или завершите оставшиеся задачи. Главное — вернуться в работу.
                </p>
                <ul style="margin: 0; padding-left: 20px; color: #1f2937;">
                    @foreach ($summary['overdue'] as $g)
                        <li style="margin-bottom: 4px;">
                            <strong>«{{ $g['title'] }}»</strong>
                            <span style="color: #6b7280; font-size: 13px;">— был {{ $g['deadline'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! empty($summary['upcoming']))
            <div style="margin-top: 20px; padding: 20px; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 6px;">
                <h3 style="margin: 0 0 6px 0; color: #92400e; font-size: 16px;">
                    Что на горизонте ближайших полутора недель
                </h3>
                <p style="margin: 0 0 12px 0; color: #6b7280; font-size: 13px;">
                    План есть — двигайтесь по нему. Каждая закрытая задача приближает к финишу.
                </p>
                <ul style="margin: 0; padding-left: 20px; color: #1f2937;">
                    @foreach ($summary['upcoming'] as $g)
                        @php
                            $left = $g['days_left'] === 0
                                ? 'сегодня'
                                : ($g['days_left'] === 1 ? 'завтра' : "через {$g['days_left']} дн.");
                        @endphp
                        <li style="margin-bottom: 4px;">
                            <strong>«{{ $g['title'] }}»</strong>
                            <span style="color: #6b7280; font-size: 13px;">— {{ $g['deadline'] }} ({{ $left }})</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div style="margin-top: 32px; text-align: center;">
            <a href="{{ $goalsUrl }}"
               style="display: inline-block; background: #4F46E5; color: white; padding: 12px 28px; text-decoration: none; border-radius: 8px; font-weight: 600;">
                Открыть мои цели
            </a>
        </div>

        <p style="margin-top: 24px; color: #4b5563; font-size: 14px; text-align: center; font-style: italic;">
            Даже одна закрытая задача в день — это уже движение вперёд. Удачи!
        </p>

        <hr style="border: 0; border-top: 1px solid #e5e7eb; margin: 32px 0 16px 0;">

        <p style="color: #9ca3af; font-size: 12px; text-align: center; margin: 0;">
            Письмо отправлено системой «{{ config('app.name') }}».
            Если хотите перестать получать такие напоминания —
            отключите их в настройках профиля.
        </p>
    </div>
</body>
</html>
