@extends('layouts.admin')

@section('title', 'Категории — Админ-панель')
@section('page-title', 'Управление категориями')

@section('content')
    <x-admin.page-header
        title="Категории"
        :subtitle="'Всего по фильтру: ' . $categories->total()">
        <x-slot:actions>
            @can('staffCreate', \App\Models\Category::class)
                <a href="{{ route('admin.categories.create') }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    + Добавить системную
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Фильтры --}}
    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.categories.index') }}"
              class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Поиск по названию</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Тип</label>
                <select name="type" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Любой</option>
                    <option value="system" @selected(($filters['type'] ?? '') === 'system')>Системные</option>
                    <option value="user"   @selected(($filters['type'] ?? '') === 'user')>Пользовательские</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm">
                    Применить
                </button>
                <a href="{{ route('admin.categories.index') }}"
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
                    <th class="px-4 py-3 text-left">Тип</th>
                    <th class="px-4 py-3 text-left">Владелец</th>
                    <th class="px-4 py-3 text-left">Целей</th>
                    <th class="px-4 py-3 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($categories as $cat)
                    <tr>
                        <td class="px-4 py-3 text-gray-400">{{ $cat->id }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-2">
                                <span class="w-3 h-3 rounded-full flex-shrink-0"
                                      style="background-color: {{ $cat->color }}"></span>
                                <span class="font-medium text-gray-800">{{ $cat->label }}</span>
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            @if ($cat->is_system)
                                <x-badge tone="indigo">системная</x-badge>
                            @else
                                <x-badge tone="gray">пользовательская</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            @if ($cat->user)
                                <a href="{{ route('admin.users.show', $cat->user_id) }}"
                                   class="hover:text-indigo-600">
                                    {{ $cat->user->name }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $cat->goals_count }}</td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                @can('staffUpdate', $cat)
                                    <a href="{{ route('admin.categories.edit', $cat->id) }}"
                                       class="text-xs text-indigo-600 hover:text-indigo-800 px-2 py-1">
                                        Редактировать
                                    </a>
                                @endcan

                                @can('staffDelete', $cat)
                                    <form method="POST"
                                          action="{{ route('admin.categories.destroy', $cat->id) }}"
                                          class="inline"
                                          onsubmit="return confirm('Удалить категорию «{{ $cat->label }}»?')">
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded
                                                   {{ $cat->goals_count > 0 ? 'opacity-50 cursor-not-allowed' : '' }}"
                                            @if($cat->goals_count > 0)
                                                type="button"
                                                onclick="alert('Нельзя удалить: в категории {{ $cat->goals_count }} {{ $cat->goals_count === 1 ? 'цель' : 'целей' }}.')"
                                            @endif
                                        >
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
                            Категории по фильтру не найдены.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </x-card>

    @if ($categories->hasPages())
        <div class="mt-4">
            {{ $categories->links() }}
        </div>
    @endif
@endsection
