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

        @php
            // Tier → набор inline-стилей. Email-клиенты не любят CSS-классы,
            // поэтому стили проставляются прямо на элементах.
            $toneFor = fn (string $tier) => match ($tier) {
                'red'   => ['bg' => '#fef2f2', 'border' => '#ef4444', 'pillBg' => '#fee2e2', 'pillFg' => '#991b1b'],
                'amber' => ['bg' => '#fffbeb', 'border' => '#f59e0b', 'pillBg' => '#fef3c7', 'pillFg' => '#92400e'],
                'gray'  => ['bg' => '#f9fafb', 'border' => '#94a3b8', 'pillBg' => '#e5e7eb', 'pillFg' => '#374151'],
            };
        @endphp

        @if (! empty($summary['with_deadline']))
            <h3 style="margin: 28px 0 12px 0; color: #111827; font-size: 16px;">
                Цели с дедлайнами
            </h3>
            <p style="margin: 0 0 12px 0; color: #6b7280; font-size: 13px;">
                Цвет показывает срочность: красный — горит, оранжевый — на этой неделе, серый — есть запас.
            </p>

            @foreach ($summary['with_deadline'] as $g)
                @php $tone = $toneFor($g['tier']); @endphp
                <div style="margin-bottom: 8px; padding: 12px 16px; background: {{ $tone['bg'] }}; border-left: 4px solid {{ $tone['border'] }}; border-radius: 6px;">
                    <div style="font-weight: 600; color: #1f2937;">«{{ $g['title'] }}»</div>
                    <div style="margin-top: 4px; font-size: 13px; color: #6b7280;">
                        Дедлайн: {{ $g['deadline'] }}
                        <span style="display: inline-block; margin-left: 8px; padding: 2px 8px; border-radius: 999px; background: {{ $tone['pillBg'] }}; color: {{ $tone['pillFg'] }}; font-weight: 600; font-size: 12px;">
                            {{ $g['status_text'] }}
                        </span>
                        @if ($g['category'])
                            <span style="color: #9ca3af;"> · {{ $g['category'] }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        @endif

        @if (! empty($summary['no_deadline']))
            <h3 style="margin: 28px 0 12px 0; color: #111827; font-size: 16px;">
                Цели без дедлайна
            </h3>
            <p style="margin: 0 0 12px 0; color: #6b7280; font-size: 13px;">
                Вот цели, для которых вы пока не зафиксировали срок.
                Поставить дату — это самый простой способ сделать цель из «когда-нибудь» в «к такому-то».
            </p>

            <div style="padding: 12px 16px; background: #f9fafb; border-left: 4px solid #94a3b8; border-radius: 6px;">
                <ul style="margin: 0; padding-left: 18px; color: #1f2937;">
                    @foreach ($summary['no_deadline'] as $g)
                        <li style="margin-bottom: 4px;">
                            <strong>«{{ $g['title'] }}»</strong>
                            @if ($g['category'])
                                <span style="color: #9ca3af; font-size: 13px;"> · {{ $g['category'] }}</span>
                            @endif
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
