@extends('layouts.admin')

@section('title', 'Цель — Админ-панель')
@section('page-title', 'Просмотр цели')

@php
    $statusBadge = [
        \App\Models\Goal::STATUS_ACTIVE    => ['активна',   'green'],
        \App\Models\Goal::STATUS_COMPLETED => ['завершена', 'indigo'],
        \App\Models\Goal::STATUS_ARCHIVED  => ['архив',     'gray'],
    ];
    [$statusLabel, $statusTone] = $statusBadge[$goal->status] ?? ['—', 'gray'];
    $isOverdue = $goal->status === \App\Models\Goal::STATUS_ACTIVE
              && $goal->deadline
              && $goal->deadline->isPast();
@endphp

@section('content')
    <x-admin.page-header
        :title="$goal->title"
        :subtitle="'#' . $goal->id . ' · ' . ($goal->user?->name ?? 'нет пользователя')">
        <x-slot:actions>
            <a href="{{ route('admin.goals.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                ← К списку
            </a>

            @can('staffArchive', $goal)
                @if ($goal->status !== \App\Models\Goal::STATUS_ARCHIVED)
                    <form method="POST" action="{{ route('admin.goals.archive', $goal->id) }}"
                          onsubmit="return confirm('Отправить цель в архив?')">
                        @csrf
                        <button class="bg-amber-100 hover:bg-amber-200 text-amber-800 px-4 py-2 rounded-lg text-sm">
                            В архив
                        </button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.goals.restore', $goal->id) }}">
                        @csrf
                        <button class="bg-green-100 hover:bg-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
                            Восстановить
                        </button>
                    </form>
                @endif
            @endcan

            @can('staffDelete', $goal)
                <form method="POST" action="{{ route('admin.goals.destroy', $goal->id) }}"
                      onsubmit="return confirm('Удалить цель безвозвратно? Все подцели и задачи будут удалены.')">
                    @csrf
                    @method('DELETE')
                    <button class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg text-sm">
                        Удалить
                    </button>
                </form>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card class="lg:col-span-2">
            <x-section-header title="Основные данные" />

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-gray-500">ID</dt>
                    <dd class="text-gray-900">#{{ $goal->id }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Статус</dt>
                    <dd>
                        <x-badge :tone="$statusTone">{{ $statusLabel }}</x-badge>
                        @if ($isOverdue)
                            <x-badge tone="red" class="ml-1">просрочена</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Пользователь</dt>
                    <dd>
                        <a href="{{ route('admin.users.show', $goal->user_id) }}"
                           class="text-indigo-600 hover:underline">
                            {{ $goal->user?->name ?? 'ID ' . $goal->user_id }}
                        </a>
                        <span class="text-gray-400 text-xs ml-1">{{ $goal->user?->email }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Категория</dt>
                    <dd class="text-gray-900">{{ $goal->category?->label ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Дедлайн</dt>
                    <dd class="text-gray-900 {{ $isOverdue ? 'text-red-600' : '' }}">
                        {{ $goal->deadline ? $goal->deadline->format('d.m.Y') : '—' }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Создана</dt>
                    <dd class="text-gray-900">{{ $goal->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                @if ($goal->archived_at)
                    <div>
                        <dt class="text-xs text-gray-500">В архив</dt>
                        <dd class="text-gray-900">{{ $goal->archived_at->format('d.m.Y H:i') }}</dd>
                    </div>
                @endif
                @if ($goal->description)
                    <div class="md:col-span-2">
                        <dt class="text-xs text-gray-500">Описание</dt>
                        <dd class="text-gray-900 whitespace-pre-wrap">{{ $goal->description }}</dd>
                    </div>
                @endif
            </dl>
        </x-card>

        <x-card>
            <x-section-header title="Прогресс" />

            @php
                $subtasks = $goal->subtasks ?? collect();
                $totalTasks = $subtasks->sum(fn ($s) => $s->tasks->count());
                $doneTasks = $subtasks->sum(fn ($s) => $s->tasks->where('is_done', true)->count());
                $pct = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0;
            @endphp

            <div class="text-center mb-4">
                <div class="text-3xl font-bold text-indigo-600">{{ $pct }}%</div>
                <div class="text-xs text-gray-500 mt-1">
                    {{ $doneTasks }} / {{ $totalTasks }} задач выполнено
                </div>
            </div>

            <x-progress-bar :percent="$pct" />

            <div class="mt-4 text-xs text-gray-500 space-y-1">
                <div class="flex justify-between">
                    <span>Подцелей:</span>
                    <span class="font-medium text-gray-700">{{ $subtasks->count() }}</span>
                </div>
                <div class="flex justify-between">
                    <span>Задач всего:</span>
                    <span class="font-medium text-gray-700">{{ $totalTasks }}</span>
                </div>
            </div>
        </x-card>
    </div>

    @if ($goal->subtasks && $goal->subtasks->count() > 0)
        <x-card class="mt-6">
            <x-section-header title="Подцели и задачи" />

            <div class="space-y-4">
                @foreach ($goal->subtasks as $subtask)
                    <div class="border border-gray-100 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-2">
                            <span class="font-medium text-sm text-gray-800">{{ $subtask->title }}</span>
                            <span class="text-xs text-gray-400">
                                {{ $subtask->tasks->where('is_done', true)->count() }}/{{ $subtask->tasks->count() }}
                            </span>
                        </div>
                        @if ($subtask->tasks->count() > 0)
                            <ul class="space-y-1">
                                @foreach ($subtask->tasks as $task)
                                    <li class="flex items-center gap-2 text-xs text-gray-600">
                                        <span class="{{ $task->is_done ? 'text-green-500' : 'text-gray-300' }}">
                                            {{ $task->is_done ? '✓' : '○' }}
                                        </span>
                                        <span class="{{ $task->is_done ? 'line-through text-gray-400' : '' }}">
                                            {{ $task->title }}
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-xs text-gray-400">Задачи не добавлены.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-card>
    @endif
@endsection
