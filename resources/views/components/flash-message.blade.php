@props([
    'type' => 'success',
])

@php
    // Сессионный flash, тип определяет цветовую палитру
    // и таймер автозакрытия. Если в сессии нет ничего по
    // указанному типу — компонент ничего не рендерит.
    $message = session($type);
    if ($message === null) {
        return;
    }

    $tone = match ($type) {
        'error'   => ['bg' => 'bg-red-50',    'border' => 'border-red-200',    'text' => 'text-red-700'],
        'warning' => ['bg' => 'bg-amber-50',  'border' => 'border-amber-200',  'text' => 'text-amber-700'],
        default   => ['bg' => 'bg-green-50',  'border' => 'border-green-200',  'text' => 'text-green-700'],
    };

    // Ошибки виснут дольше — пользователю нужно успеть прочитать.
    $timeout = $type === 'error' ? 6000 : 4000;
@endphp

<div x-data="{ show: true }"
     x-show="show"
     x-init="setTimeout(() => show = false, {{ $timeout }})"
     class="mb-4 rounded-lg border {{ $tone['bg'] }} {{ $tone['border'] }} {{ $tone['text'] }} px-4 py-3 text-sm">
    {{ $message }}
</div>
