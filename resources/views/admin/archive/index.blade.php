@extends('layouts.admin')

@section('title', 'Архив — Админ-панель')
@section('page-title', 'Архив целей')

@section('content')
    <x-admin.page-header
        title="Архив целей"
        :subtitle="'Всего в архиве: ' . $goals->total()">
    </x-admin.page-header>

    {{-- Фильтры --}}
    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.archive.index') }}"
              class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Поиск по названию</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">ID пользователя</label>
                <input type="number" name="user_id" value="{{ $filters['user_id'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                       placeholder="например, 5">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm">
                    Применить
                </button>
                <a href="{{ route('admin.archive.index') }}"
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
                    <th class="px-4 py-3 text-left">В архиве с</th>
                    <th class="px-4 py-3 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($goals as $goal)
                    <tr>
                        <td class="px-4 py-3 text-gray-400">{{ $goal->id }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.goals.show', $goal->id) }}"
                               class="font-medium text-gray-800 hover:text-indigo-600">
                                {{ $goal->title }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-600">
                            <a href="{{ route('admin.users.show', $goal->user_id) }}"
                               class="hover:text-indigo-600">
                                {{ $goal->user?->name ?? '#' . $goal->user_id }}
                            </a>
                        </td>
                        <td class="px-4 py-3 text-gray-500">{{ $goal->category?->label ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $goal->archived_at?->format('d.m.Y H:i') ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                @can('staffRestore', $goal)
                                    <form method="POST"
                                          action="{{ route('admin.archive.restore', $goal->id) }}"
                                          class="inline">
                                        @csrf
                                        <button class="text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-2 py-1 rounded">
                                            Восстановить
                                        </button>
                                    </form>
                                @endcan

                                @can('staffDelete', $goal)
                                    <form method="POST"
                                          action="{{ route('admin.archive.destroy', $goal->id) }}"
                                          class="inline"
                                          onsubmit="return confirm('Удалить «{{ $goal->title }}» безвозвратно?')">
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
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            Архив пуст.
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
