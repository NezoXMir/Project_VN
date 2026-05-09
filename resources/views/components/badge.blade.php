@props([
    'tone' => 'gray',
    'color' => null,
])

@php
    // Pill-бейдж двух режимов:
    //
    // 1. Tailwind-палитра через :tone — стандартные цвета проекта
    //    (indigo / emerald / amber / red / sky / gray).
    // 2. Произвольный hex через :color — для категорийных бейджей,
    //    у которых цвет хранится в БД и Tailwind-purger не знает
    //    заранее какие классы понадобятся. Используется inline-стиль
    //    `background: {color}1A; color: {color}` — тот же приём, что
    //    был в Этапе 04 (доп.).
    $base = 'inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium';
    $toneClasses = match ($tone) {
        'indigo'  => 'bg-indigo-100 text-indigo-700',
        'emerald' => 'bg-emerald-100 text-emerald-700',
        'amber'   => 'bg-amber-100 text-amber-700',
        'red'     => 'bg-red-100 text-red-700',
        'sky'     => 'bg-sky-100 text-sky-700',
        'green'   => 'bg-green-100 text-green-700',
        'blue'    => 'bg-blue-100 text-blue-700',
        default   => 'bg-gray-100 text-gray-700',
    };
@endphp

@if ($color)
    <span {{ $attributes->class([$base])->merge(['style' => "background-color: {$color}1A; color: {$color}"]) }}>
        {{ $slot }}
    </span>
@else
    <span {{ $attributes->class([$base, $toneClasses]) }}>
        {{ $slot }}
    </span>
@endif
