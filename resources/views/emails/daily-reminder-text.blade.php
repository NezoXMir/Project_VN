Привет, {{ $name }}!

Каждый день — маленький шаг к вашим целям. Вот что важно держать в фокусе сегодня.

@if (! empty($summary['with_deadline']))
ЦЕЛИ С ДЕДЛАЙНАМИ
(! — горит, ~ — на этой неделе, • — есть запас)

@foreach ($summary['with_deadline'] as $g)
@php
$marker = match ($g['tier']) {
'red' => '!',
'amber' => '~',
default => '·',
};
@endphp
{{ $marker }} «{{ $g['title'] }}» — {{ $g['deadline'] }} ({{ $g['status_text'] }}@if ($g['category']), {{ $g['category'] }}@endif)
@endforeach

@endif
@if (! empty($summary['no_deadline']))
ЦЕЛИ БЕЗ ДЕДЛАЙНА
(подумайте, какую дату хотели бы для них установить — это сильно помогает)

@foreach ($summary['no_deadline'] as $g)
- «{{ $g['title'] }}»@if ($g['category']) ({{ $g['category'] }})@endif
@endforeach

@endif

Открыть мои цели: {{ $goalsUrl }}

Даже одна закрытая задача в день — это уже движение вперёд. Удачи!

—
Письмо отправлено системой «{{ config('app.name') }}».
Чтобы перестать получать такие напоминания, отключите их в настройках профиля.