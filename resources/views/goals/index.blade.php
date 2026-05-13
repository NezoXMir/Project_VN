@extends('layouts.app')

@section('title', 'Мои цели — ' . config('app.name'))

@php
$byCategory = $goals->groupBy('category_id');
@endphp

@section('content')
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold">Мои цели</h1>
        <p class="text-sm text-gray-500 mt-1">Всего: {{ $goals->count() }}</p>
    </div>

    <div class="flex items-center gap-3">
        <a href="{{ route('dashboard') }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            ← Дашборд
        </a>
        <a href="{{ route('goals.archive') }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            Архив
        </a>
        <a href="{{ route('categories.index') }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            Категории
        </a>
        <a href="{{ route('goals.create') }}"
            class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
            + Новая цель
        </a>
    </div>
</div>

<x-flash-message type="success" />

@if ($goals->isEmpty())
<x-card padding="p-8" class="text-center">
    <p class="text-gray-600 mb-4">У вас пока нет целей.</p>
    <a href="{{ route('goals.create') }}"
        class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
        Создать первую цель
    </a>
</x-card>
@else
@foreach ($byCategory as $categoryId => $items)
@php $cat = $items->first()->category; @endphp
@if ($cat)
<div class="mb-8">
    <div class="flex items-center gap-2 mb-3">
        <x-badge :color="$cat->color">{{ $cat->label }}</x-badge>
        <span class="text-sm text-gray-500">{{ $items->count() }} {{ trans_choice('цель|цели|целей', $items->count()) }}</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($items as $g)
        @php
            $progress = $g->progress;
            $deadlineSoon = $g->deadline && now()->diffInDays($g->deadline, false) >= 0 && now()->diffInDays($g->deadline, false) < 3;
            $statusTone = match ($g->status) {
                'active' => 'blue',
                'completed' => 'green',
                'archived' => 'gray',
            };
            $statusLabel = match ($g->status) {
                'active' => 'активная',
                'completed' => 'завершена',
                'archived' => 'в архиве',
            };
        @endphp
        <a href="{{ route('goals.show', $g->id) }}" class="block hover:shadow-md transition">
            <x-card :accent="$cat->color">
                <div class="flex items-start justify-between mb-2">
                    <h3 class="font-semibold text-gray-900 line-clamp-2">{{ $g->title }}</h3>
                    <x-badge :tone="$statusTone" class="ml-2">{{ $statusLabel }}</x-badge>
                </div>

                @if ($g->description)
                <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $g->description }}</p>
                @endif

                <div class="mb-2">
                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                        <span>Прогресс</span>
                        <span>{{ $progress }}%</span>
                    </div>
                    <x-progress-bar :value="$progress" :color="$cat->color" />
                </div>

                @if ($g->deadline)
                @php $timeLeft = \App\Helpers\DateHelper::timeLeftLabel($g->deadline); @endphp
                <div class="text-xs {{ $deadlineSoon ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                    Дедлайн: {{ $g->deadline->format('d.m.Y') }}
                    @if ($timeLeft)
                    ({{ $timeLeft }})
                    @endif
                </div>
                @endif
            </x-card>
        </a>
        @endforeach
    </div>
</div>
@endif
@endforeach
@endif
@endsection