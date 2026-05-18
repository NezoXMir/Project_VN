@extends('layouts.app')

@section('title', 'Категории — ' . config('app.name'))

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Категории</h1>
            <p class="text-sm text-gray-500 mt-1">
                Системные доступны всем, ваши — только вам.
            </p>
        </div>

        <a href="{{ route('categories.create') }}"
           class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg text-sm transition">
            <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Новая категория
        </a>
    </div>

    <x-flash-message type="success" />
    <x-flash-message type="error" />

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
        @foreach ($all as $cat)
            <x-card padding="p-4" class="flex items-center justify-between">
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

                @can('update', $cat)
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
                @endcan
            </x-card>
        @endforeach
    </div>
@endsection
