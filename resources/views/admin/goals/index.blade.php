@extends('layouts.admin')

@section('title', 'Цели — Админ-панель')
@section('page-title', 'Управление целями')

@php
    $statusBadge = [
        \App\Models\Goal::STATUS_ACTIVE    => ['активна',    'green'],
        \App\Models\Goal::STATUS_COMPLETED => ['завершена',  'indigo'],
        \App\Models\Goal::STATUS_ARCHIVED  => ['архив',      'gray'],
    ];
@endphp

@section('content')
    <x-admin.page-header
        title="Цели"
        :subtitle="'Всего по фильтру: ' . $goals->total()">
    </x-admin.page-header>

    {{-- Фильтры --}}
    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.goals.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Поиск по названию</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Статус</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Любой</option>
                    <option value="active"    @selected(($filters['status'] ?? '') === 'active')>Активна</option>
                    <option value="completed" @selected(($filters['status'] ?? '') === 'completed')>Завершена</option>
                    <option value="archived"  @selected(($filters['status'] ?? '') === 'archived')>В архиве</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">ID пользователя</label>
                <input type="number" name="user_id" value="{{ $filters['user_id'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                       placeholder="например, 5">
            </div>
            <div class="flex items-end gap-2">
                <label class="flex items-center gap-1 text-sm text-gray-700 mb-2 cursor-pointer">
                    <input type="checkbox" name="overdue" value="1"
                           @checked(! empty($filters['overdue']))
                           class="rounded border-gray-300">
                    Просроченные
                </label>
            </div>
            <div class="md:col-span-4 flex gap-2">
                <button type="submit"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm">
                    Применить
                </button>
                <a href="{{ route('admin.goals.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">
                    Сбросить
                </a>
            </div>
        </form>
    </x-card>

    <x-card padding="p-0" class="overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Название</th>
                    <th class="px-4 py-3 text-left">Пользователь</th>
                    <th class="px-4 py-3 text-left">Категория</th>
                    <th class="px-4 py-3 text-left">Статус</th>
                    <th class="px-4 py-3 text-left">Дедлайн</th>
                    <th class="px-4 py-3 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($goals as $goal)
                    @php
                        [$statusLabel, $statusTone] = $statusBadge[$goal->status] ?? ['—', 'gray'];
                        $isOverdue = $goal->status === \App\Models\Goal::STATUS_ACTIVE
                                  && $goal->deadline
                                  && $goal->deadline->isPast();
                    @endphp
                    <tr class="{{ $isOverdue ? 'bg-red-50/30' : '' }}">
                        <td class="px-4 py-3 text-gray-400">{{ $goal->id }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.goals.show', $goal->id) }}"
                               class="text-gray-900 hover:text-indigo-600 font-medium">
                                {{ $goal->title }}
                            </a>
                            @if ($isOverdue)
                                <span class="ml-1 text-xs text-red-500">просрочена</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <a href="{{ route('admin.users.show', $goal->user_id) }}"
                               class="hover:text-indigo-600">
                                {{ $goal->user?->name ?? '#' . $goal->user_id }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $goal->category?->label ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-badge :tone="$statusTone">{{ $statusLabel }}</x-badge>
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $goal->deadline ? $goal->deadline->format('d.m.Y') : '—' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('admin.goals.show', $goal->id) }}"
                                   class="text-xs text-gray-600 hover:text-indigo-600 px-2 py-1">
                                    Открыть
                                </a>

                                @can('staffArchive', $goal)
                                    @if ($goal->status !== \App\Models\Goal::STATUS_ARCHIVED)
                                        <form method="POST"
                                              action="{{ route('admin.goals.archive', $goal->id) }}"
                                              class="inline"
                                              onsubmit="return confirm('Отправить цель «{{ $goal->title }}» в архив?')">
                                            @csrf
                                            <button class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-800 px-2 py-1 rounded">
                                                В архив
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST"
                                              action="{{ route('admin.goals.restore', $goal->id) }}"
                                              class="inline">
                                            @csrf
                                            <button class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-2 py-1 rounded">
                                                Восстановить
                                            </button>
                                        </form>
                                    @endif
                                @endcan

                                @can('staffDelete', $goal)
                                    <form method="POST"
                                          action="{{ route('admin.goals.destroy', $goal->id) }}"
                                          class="inline"
                                          onsubmit="return confirm('Удалить цель «{{ $goal->title }}» безвозвратно?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded">
                                            Удалить
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-500">
                            Цели по фильтру не найдены.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </x-card>

    @if ($goals->hasPages())
        <div class="mt-4">
            {{ $goals->links() }}
        </div>
    @endif
@endsection
