@props([
    'value' => 0,
    'color' => '#4F46E5',
    'size' => 'md',
])

@php
    $value = max(0, min(100, (int) $value));
    $heightClass = match ($size) {
        'sm' => 'h-1.5',
        'lg' => 'h-3',
        default => 'h-2',
    };
@endphp

<div {{ $attributes->class(["w-full bg-gray-100 rounded-full overflow-hidden {$heightClass}"]) }}>
    <div class="h-full transition-all duration-500"
         style="width: {{ $value }}%; background-color: {{ $color }}"></div>
</div>
