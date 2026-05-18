@extends('layouts.app')

@section('title', 'Архив целей — ' . config('app.name'))

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold">Архив целей</h1>
    <p class="text-sm text-gray-500 mt-1">Архивных целей: {{ $goals->count() }}</p>
</div>

<x-flash-message type="success" />

@if ($goals->isEmpty())
    <x-card padding="p-8" class="text-center">
        <p class="text-gray-500 text-lg mb-2">Архив пуст</p>
        <p class="text-sm text-gray-400">Заархивированные цели появятся здесь.</p>
    </x-card>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach ($goals as $goal)
            @php
                $cat = $goal->category;
                $catColor = $cat?->color ?? '#64748B';
            @endphp
            {{-- h-full + flex flex-col чтобы кнопки всегда были внизу --}}
            <x-card :accent="$catColor" class="h-full flex flex-col">

                {{-- Заголовок --}}
                <div class="flex items-start justify-between gap-2 mb-2">
                    <a href="{{ route('goals.show', $goal->id) }}"
                       class="font-semibold text-gray-900 hover:text-indigo-600 line-clamp-2 leading-snug">
                        {{ $goal->title }}
                    </a>
                    <x-badge tone="gray" class="flex-shrink-0">архив</x-badge>
                </div>

                {{-- Категория --}}
                @if ($cat)
                    <div class="mb-2">
                        <x-badge :color="$catColor">{{ $cat->label }}</x-badge>
                    </div>
                @endif

                {{-- Описание (занимает доступное пространство) --}}
                <p class="text-sm text-gray-500 line-clamp-3 flex-1 mb-3 min-h-0">
                    {{ $goal->description ?: '' }}
                </p>

                {{-- Дата — всегда перед кнопками --}}
                <p class="text-xs text-gray-400 mb-3">
                    В архиве с {{ $goal->archived_at?->format('d.m.Y') ?? $goal->updated_at->format('d.m.Y') }}
                </p>

                {{-- Кнопки — всегда внизу за счёт mt-auto на родителе выше --}}
                <div class="flex items-center gap-2 pt-3 border-t border-gray-100">
                    <form method="POST" action="{{ route('goals.restore', $goal->id) }}">
                        @csrf
                        <button type="submit"
                                class="text-sm bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-1.5 rounded-lg">
                            Восстановить
                        </button>
                    </form>
                    <form method="POST" action="{{ route('goals.destroy', $goal->id) }}"
                          onsubmit="return confirm('Удалить «{{ $goal->title }}» безвозвратно?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="text-sm text-red-600 hover:text-red-800 px-3 py-1.5 rounded-lg hover:bg-red-50">
                            Удалить
                        </button>
                    </form>
                </div>

            </x-card>
        @endforeach
    </div>
@endif
@endsection
