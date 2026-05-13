@extends('layouts.admin')

@section('title', 'Пользователи — Админ-панель')
@section('page-title', 'Пользователи')

@php
    /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\User> $users */
    $filters = $filters ?? [];
    $roleBadge = [
        \App\Models\User::ROLE_ADMIN   => ['admin', 'red'],
        \App\Models\User::ROLE_MANAGER => ['manager', 'amber'],
        \App\Models\User::ROLE_USER    => ['user', 'gray'],
    ];
@endphp

@section('content')
    <x-admin.page-header
        title="Пользователи"
        :subtitle="'Всего по фильтру: ' . $users->count()">
        <x-slot:actions>
            @can('create', \App\Models\User::class)
                <a href="{{ route('admin.users.create') }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    + Добавить пользователя
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Фильтры --}}
    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Поиск (имя/email)</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Роль</label>
                <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Любая</option>
                    <option value="user"    @selected(($filters['role'] ?? '') === 'user')>Пользователь</option>
                    <option value="manager" @selected(($filters['role'] ?? '') === 'manager')>Менеджер</option>
                    <option value="admin"   @selected(($filters['role'] ?? '') === 'admin')>Администратор</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Статус</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Любой</option>
                    <option value="active"  @selected(($filters['status'] ?? '') === 'active')>Активные</option>
                    <option value="blocked" @selected(($filters['status'] ?? '') === 'blocked')>Заблокированные</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm">
                    Применить
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">
                    Сбросить
                </a>
            </div>
        </form>
    </x-card>

    <x-card padding="p-0" class="overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">#</th>
                    <th class="px-4 py-3 text-left">Имя</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Роль</th>
                    <th class="px-4 py-3 text-left">Статус</th>
                    <th class="px-4 py-3 text-left">Зарегистрирован</th>
                    <th class="px-4 py-3 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($users as $u)
                    @php
                        $isSelf = $u->id === auth()->id();
                        [$roleLabel, $roleTone] = $roleBadge[$u->role] ?? ['user', 'gray'];
                    @endphp
                    <tr class="{{ $isSelf ? 'bg-indigo-50/40' : '' }}">
                        <td class="px-4 py-3 text-gray-400">{{ $u->id }}</td>
                        <td class="px-4 py-3">
                            <a href="{{ route('admin.users.show', $u->id) }}"
                               class="text-gray-900 hover:text-indigo-600 font-medium">
                                {{ $u->name }}
                            </a>
                            @if ($isSelf)
                                <span class="ml-1 text-xs text-indigo-600">(вы)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $u->email }}</td>
                        <td class="px-4 py-3">
                            <x-badge :tone="$roleTone">{{ $roleLabel }}</x-badge>
                        </td>
                        <td class="px-4 py-3">
                            @if ($u->isBlocked())
                                <x-badge tone="red">заблокирован</x-badge>
                            @else
                                <x-badge tone="green">активен</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $u->created_at->format('d.m.Y') }}
                        </td>
                        <td class="px-4 py-3 text-right whitespace-nowrap">
                            <div class="inline-flex items-center gap-1">
                                <a href="{{ route('admin.users.show', $u->id) }}"
                                   class="text-xs text-gray-600 hover:text-indigo-600 px-2 py-1">
                                    Открыть
                                </a>

                                @can('block', $u)
                                    @if ($u->isBlocked())
                                        <form method="POST" action="{{ route('admin.users.unblock', $u->id) }}" class="inline">
                                            @csrf
                                            <button class="text-xs bg-green-100 hover:bg-green-200 text-green-700 px-2 py-1 rounded">
                                                Разблокировать
                                            </button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.block', $u->id) }}" class="inline"
                                              onsubmit="return confirm('Заблокировать пользователя {{ $u->email }}?')">
                                            @csrf
                                            <button class="text-xs bg-amber-100 hover:bg-amber-200 text-amber-800 px-2 py-1 rounded">
                                                Заблокировать
                                            </button>
                                        </form>
                                    @endif
                                @endcan

                                @can('delete', $u)
                                    <form method="POST" action="{{ route('admin.users.destroy', $u->id) }}" class="inline"
                                          onsubmit="return confirm('Удалить пользователя {{ $u->email }}? Все его цели и задачи будут удалены безвозвратно.')">
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
                    <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">Пользователи по фильтру не найдены.</td></tr>
                @endforelse
            </tbody>
        </table>
    </x-card>
@endsection
