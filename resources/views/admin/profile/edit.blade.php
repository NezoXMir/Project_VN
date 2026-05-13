@extends('layouts.admin')

@section('title', 'Мой профиль — Админ-панель')
@section('page-title', 'Мой профиль')

@php
    $roleBadge = [
        \App\Models\User::ROLE_ADMIN   => ['Администратор', 'red'],
        \App\Models\User::ROLE_MANAGER => ['Менеджер',      'amber'],
        \App\Models\User::ROLE_USER    => ['Пользователь',  'gray'],
    ];
    [$roleLabel, $roleTone] = $roleBadge[$user->role] ?? ['—', 'gray'];
@endphp

@section('content')
    <x-admin.page-header
        :title="$user->name"
        :subtitle="$user->email">
    </x-admin.page-header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Основные данные --}}
        <x-card>
            <x-section-header title="Основные данные" />

            @if (session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-2 text-sm">
                    {{ session('success') }}
                </div>
            @endif

            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm mb-6">
                <div>
                    <dt class="text-xs text-gray-500">Роль</dt>
                    <dd><x-badge :tone="$roleTone">{{ $roleLabel }}</x-badge></dd>
                </div>
                <div>
                    <dt class="text-xs text-gray-500">Зарегистрирован</dt>
                    <dd class="text-gray-700">{{ $user->created_at->format('d.m.Y') }}</dd>
                </div>
            </dl>

            @if ($errors->has('name') || $errors->has('email'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->only(['name','email']) as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Имя</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Email</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>

                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    Сохранить
                </button>
            </form>
        </x-card>

        {{-- Смена пароля --}}
        <x-card>
            <x-section-header title="Смена пароля" />

            @if ($errors->has('current_password') || $errors->has('password'))
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach ($errors->only(['current_password','password']) as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('admin.profile.password') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Текущий пароль</label>
                    <input type="password" name="current_password" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Новый пароль</label>
                    <input type="password" name="password" required minlength="8"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 block mb-1">Повторите новый пароль</label>
                    <input type="password" name="password_confirmation" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                </div>

                <button type="submit"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm">
                    Изменить пароль
                </button>
            </form>
        </x-card>

    </div>
@endsection
