@extends('layouts.app')

@section('title', 'Дашборд — ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Здравствуйте, {{ auth()->user()->name }}!</h1>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                Выйти
            </button>
        </form>
    </div>

    @if (session('success'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 4000)"
             class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <p class="text-gray-600">
            Аккаунт активен. Роль: <span class="font-medium">{{ auth()->user()->role }}</span>.
        </p>
        <p class="text-gray-500 text-sm mt-2">
            Это временная страница. Полноценный дашборд появится на Этапе 06.
        </p>
    </div>
@endsection
