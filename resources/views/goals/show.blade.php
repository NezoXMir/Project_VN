@extends('layouts.app')

@section('title', $goal->title . ' — ' . config('app.name'))

@php
    $statusBadge = match ($goal->status) {
        'active'    => 'bg-blue-100 text-blue-700',
        'completed' => 'bg-green-100 text-green-700',
        'archived'  => 'bg-gray-200 text-gray-600',
    };
    $statusLabel = match ($goal->status) {
        'active'    => 'активная',
        'completed' => 'завершена',
        'archived'  => 'в архиве',
    };
    $progress = $goal->progress;
    $catColor = $goal->category?->color ?? '#64748B';
    $catLabel = $goal->category?->label ?? 'без категории';
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('goals.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            ← К списку
        </a>

        <div class="flex items-center gap-2">
            @if ($goal->status === 'active')
                <a href="{{ route('goals.edit', $goal->id) }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    Редактировать
                </a>
                <form method="POST" action="{{ route('goals.complete', $goal->id) }}">
                    @csrf
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                        Завершить
                    </button>
                </form>
                <form method="POST" action="{{ route('goals.archive', $goal->id) }}">
                    @csrf
                    <button type="submit"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                        В архив
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('goals.destroy', $goal->id) }}"
                  onsubmit="return confirm('Удалить цель безвозвратно?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                    Удалить
                </button>
            </form>
        </div>
    </div>

    @if (session('success'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 4000)"
             class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 mb-6 border-l-4"
         style="border-left-color: {{ $catColor }}">
        <div class="flex items-start gap-3 mb-4">
            <div class="flex-1">
                <div class="flex items-center gap-2 mb-2">
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                          style="background-color: {{ $catColor }}1A; color: {{ $catColor }}">
                        {{ $catLabel }}
                    </span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $statusBadge }}">
                        {{ $statusLabel }}
                    </span>
                </div>
                <h1 class="text-2xl font-bold">{{ $goal->title }}</h1>
            </div>
        </div>

        @if ($goal->description)
            <p class="text-gray-600 whitespace-pre-line mb-4">{{ $goal->description }}</p>
        @endif

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <div class="text-gray-500">Создана</div>
                <div class="font-medium">{{ $goal->created_at->format('d.m.Y') }}</div>
            </div>

            <div>
                <div class="text-gray-500">Дедлайн</div>
                <div class="font-medium">
                    {{ $goal->deadline ? $goal->deadline->format('d.m.Y') : '—' }}
                </div>
            </div>

            <div>
                <div class="text-gray-500">Прогресс</div>
                <div class="font-medium">{{ $progress }}%</div>
            </div>
        </div>

        <div class="mt-4">
            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                <div class="h-full" style="width: {{ $progress }}%; background-color: {{ $catColor }}"></div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-lg font-semibold mb-2">Подцели и задачи</h2>
        <p class="text-sm text-gray-500">
            Этот раздел появится на Этапе 05 (иерархия Цель → Подцель → Задача).
        </p>
    </div>
@endsection
