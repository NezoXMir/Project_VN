@props([
    'padding' => 'p-5',
    'accent' => null,
])

@php
    $base = "bg-white rounded-xl shadow-sm border border-gray-100 {$padding}";
    $accentClass = $accent ? 'border-l-4' : '';
    $accentStyle = $accent ? "border-left-color: {$accent}" : '';
@endphp

<div {{ $attributes->class([$base, $accentClass])->merge(['style' => $accentStyle]) }}>
    {{ $slot }}
</div>
