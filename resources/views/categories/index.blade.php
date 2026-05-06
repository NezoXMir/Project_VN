@extends('layouts.app')

@section('title', 'Категории — ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Категории</h1>
            <p class="text-sm text-gray-500 mt-1">
                Системные доступны всем, ваши — только вам.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                ← Дашборд
            </a>
            <a href="{{ route('categories.create') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                + Новая категория
            </a>
        </div>
    </div>

    @if (session('success'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 4000)"
             class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div x-data="{ show: true }"
             x-show="show"
             x-init="setTimeout(() => show = false, 6000)"
             class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($all as $cat)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-block w-4 h-4 rounded-full"
                          style="background-color: {{ $cat->color }}"></span>
                    <div>
                        <div class="font-medium">{{ $cat->label }}</div>
                        @if ($cat->is_system)
                            <span class="text-xs text-gray-500">системная</span>
                        @endif
                    </div>
                </div>

                @if (! $cat->is_system)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('categories.edit', $cat->id) }}"
                           class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-lg">
                            Изменить
                        </a>
                        <form method="POST" action="{{ route('categories.destroy', $cat->id) }}"
                              onsubmit="return confirm('Удалить категорию «{{ $cat->label }}»?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-lg">
                                Удалить
                            </button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
