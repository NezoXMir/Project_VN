@extends('layouts.admin')

@section('title', 'Пользователь — Админ-панель')
@section('page-title', 'Карточка пользователя')

@php
    $roleBadge = [
        \App\Models\User::ROLE_ADMIN   => ['Администратор', 'red'],
        \App\Models\User::ROLE_MANAGER => ['Менеджер', 'amber'],
        \App\Models\User::ROLE_USER    => ['Пользователь', 'gray'],
    ];
    [$roleLabel, $roleTone] = $roleBadge[$user->role] ?? ['Пользователь', 'gray'];
    $isSelf = $user->id === auth()->id();
@endphp

@section('content')
    <x-admin.page-header
        :title="$user->name"
        :subtitle="$user->email">
        <x-slot:actions>
            <a href="{{ route('admin.users.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                ← К списку
            </a>
            @can('update', $user)
                <a href="{{ route('admin.users.edit', $user->id) }}"
                   class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    Редактировать
                </a>
            @endcan
        </x-slot:actions>
    </x-admin.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <x-card class="lg:col-span-2">
            <x-section-header title="Основные данные" />

            <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-gray-500">ID</dt>
                    <dd class="text-gray-900">#{{ $user->id }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Роль</dt>
                    <dd><x-badge :tone="$roleTone">{{ $roleLabel }}</x-badge></dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Имя</dt>
                    <dd class="text-gray-900">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Email</dt>
                    <dd class="text-gray-900">{{ $user->email }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Статус</dt>
                    <dd>
                        @if ($user->isBlocked())
                            <x-badge tone="red">заблокирован</x-badge>
                            <span class="text-xs text-gray-500 ml-2">{{ $user->blocked_at->format('d.m.Y H:i') }}</span>
                        @else
                            <x-badge tone="green">активен</x-badge>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Зарегистрирован</dt>
                    <dd class="text-gray-900">{{ $user->created_at->format('d.m.Y H:i') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Email-напоминания</dt>
                    <dd class="text-gray-900">{{ $user->email_reminders_enabled ? 'Включены' : 'Отключены' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Изменён</dt>
                    <dd class="text-gray-900">{{ $user->updated_at->format('d.m.Y H:i') }}</dd>
                </div>
            </dl>

            {{-- Действия с пользователем --}}
            <div class="mt-6 pt-6 border-t border-gray-100 flex flex-wrap gap-2">
                @can('block', $user)
                    @if ($user->isBlocked())
                        <form method="POST" action="{{ route('admin.users.unblock', $user->id) }}">
                            @csrf
                            <button class="bg-green-100 hover:bg-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
                                Разблокировать
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.block', $user->id) }}"
                              onsubmit="return confirm('Заблокировать {{ $user->email }}?')">
                            @csrf
                            <button class="bg-amber-100 hover:bg-amber-200 text-amber-800 px-4 py-2 rounded-lg text-sm">
                                Заблокировать
                            </button>
                        </form>
                    @endif
                @endcan

                @can('delete', $user)
                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}"
                          onsubmit="return confirm('Удалить пользователя {{ $user->email }} безвозвратно? Все его цели и задачи будут удалены.')">
                        @csrf
                        @method('DELETE')
                        <button class="bg-red-100 hover:bg-red-200 text-red-700 px-4 py-2 rounded-lg text-sm">
                            Удалить аккаунт
                        </button>
                    </form>
                @endcan

                @if ($isSelf)
                    <p class="text-xs text-gray-500 self-center">
                        Действия над собственным аккаунтом ограничены. Используйте «Мой профиль».
                    </p>
                @endif
            </div>
        </x-card>

        <x-card>
            <x-section-header title="Активность" />

            <div class="space-y-3 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Активных целей:</span>
                    <span class="font-semibold">{{ $stats['goals_active'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">Завершённых целей:</span>
                    <span class="font-semibold">{{ $stats['goals_completed'] }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-gray-600">В архиве:</span>
                    <span class="font-semibold">{{ $stats['goals_archived'] }}</span>
                </div>
                <div class="flex items-center justify-between pt-3 border-t border-gray-100">
                    <span class="text-gray-600">Задач выполнено:</span>
                    <span class="font-semibold">{{ $stats['tasks_done'] }}</span>
                </div>
            </div>
        </x-card>
    </div>
@endsection
