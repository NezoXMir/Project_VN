@extends('layouts.admin')

@section('title', 'Редактирование пользователя — Админ-панель')
@section('page-title', 'Редактирование пользователя')

@section('content')
    <x-admin.page-header
        :title="'Редактирование: ' . $user->name"
        :subtitle="$user->email">
        <x-slot:actions>
            <a href="{{ route('admin.users.show', $user->id) }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                ← К пользователю
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-card class="max-w-2xl">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-lg mb-4">
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-4">
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

            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Роль</label>
                @php $isSelf = $user->id === auth()->id(); @endphp
                <select name="role" required @disabled($isSelf)
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm @if($isSelf) bg-gray-100 @endif">
                    <option value="user"    @selected(old('role', $user->role) === 'user')>Пользователь</option>
                    <option value="manager" @selected(old('role', $user->role) === 'manager')>Менеджер</option>
                    <option value="admin"   @selected(old('role', $user->role) === 'admin')>Администратор</option>
                </select>
                @if ($isSelf)
                    {{-- скрытое поле подменяет disabled select, иначе post не уйдёт --}}
                    <input type="hidden" name="role" value="{{ $user->role }}">
                    <p class="text-xs text-gray-500 mt-1">Нельзя изменить собственную роль.</p>
                @endif
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    Сохранить
                </button>
                <a href="{{ route('admin.users.show', $user->id) }}"
                   class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">
                    Отмена
                </a>
            </div>
        </form>
    </x-card>
@endsection
