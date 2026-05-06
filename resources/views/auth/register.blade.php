@extends('layouts.guest')

@section('title', 'Регистрация — ' . config('app.name'))

@section('content')
    <h2 class="text-xl font-semibold mb-4">Регистрация</h2>

    @if ($errors->any())
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            <ul class="list-disc list-inside space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
            <input id="name"
                   name="name"
                   type="text"
                   value="{{ old('name') }}"
                   required
                   autofocus
                   class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
            <input id="email"
                   name="email"
                   type="email"
                   value="{{ old('email') }}"
                   required
                   class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Пароль</label>
            <input id="password"
                   name="password"
                   type="password"
                   required
                   minlength="8"
                   class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            <p class="text-xs text-gray-500 mt-1">Минимум 8 символов.</p>
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Повтор пароля</label>
            <input id="password_confirmation"
                   name="password_confirmation"
                   type="password"
                   required
                   minlength="8"
                   class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
        </div>

        <button type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-4 py-2 rounded-lg transition">
            Создать аккаунт
        </button>
    </form>

    <p class="text-sm text-gray-500 text-center mt-6">
        Уже есть аккаунт?
        <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Войти</a>
    </p>
@endsection
