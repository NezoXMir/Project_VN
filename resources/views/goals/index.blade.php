@extends('layouts.app')

@section('title', 'Мои цели — ' . config('app.name'))

@php
    $byCategory = $goals->groupBy('category_id');
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Мои цели</h1>
            <p class="text-sm text-gray-500 mt-1">Всего: {{ $goals->count() }}</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                ← Дашборд
            </a>
            <a href="{{ route('categories.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                Категории
            </a>
            <a href="{{ route('goals.create') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                + Новая цель
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

    @if ($goals->isEmpty())
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
            <p class="text-gray-600 mb-4">У вас пока нет целей.</p>
            <a href="{{ route('goals.create') }}"
               class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                Создать первую цель
            </a>
        </div>
    @else
        @foreach ($byCategory as $categoryId => $items)
            @php $cat = $items->first()->category; @endphp
            @if ($cat)
                <div class="mb-8">
                    <div class="flex items-center gap-2 mb-3">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                              style="background-color: {{ $cat->color }}1A; color: {{ $cat->color }}">
                            {{ $cat->label }}
                        </span>
                        <span class="text-sm text-gray-500">{{ $items->count() }} {{ trans_choice('цель|цели|целей', $items->count()) }}</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($items as $g)
                            @php
                                $progress = $g->progress;
                                $deadlineSoon = $g->deadline && now()->diffInDays($g->deadline, false) >= 0 && now()->diffInDays($g->deadline, false) < 3;
                                $statusBadge = match ($g->status) {
                                    'active'    => 'bg-blue-100 text-blue-700',
                                    'completed' => 'bg-green-100 text-green-700',
                                    'archived'  => 'bg-gray-200 text-gray-600',
                                };
                                $statusLabel = match ($g->status) {
                                    'active'    => 'активная',
                                    'completed' => 'завершена',
                                    'archived'  => 'в архиве',
                                };
                            @endphp
                            <a href="{{ route('goals.show', $g->id) }}"
                               class="block bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition p-5 border-l-4"
                               style="border-left-color: {{ $cat->color }}">
                                <div class="flex items-start justify-between mb-2">
                                    <h3 class="font-semibold text-gray-900 line-clamp-2">{{ $g->title }}</h3>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs {{ $statusBadge }}">
                                        {{ $statusLabel }}
                                    </span>
                                </div>

                                @if ($g->description)
                                    <p class="text-sm text-gray-500 mb-3 line-clamp-2">{{ $g->description }}</p>
                                @endif

                                <div class="mb-2">
                                    <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                        <span>Прогресс</span>
                                        <span>{{ $progress }}%</span>
                                    </div>
                                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                                        <div class="h-full" style="width: {{ $progress }}%; background-color: {{ $cat->color }}"></div>
                                    </div>
                                </div>

                                @if ($g->deadline)
                                    <div class="text-xs {{ $deadlineSoon ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                                        Дедлайн: {{ $g->deadline->format('d.m.Y') }}
                                        @if ($deadlineSoon)
                                            (осталось {{ (int) ceil(now()->diffInDays($g->deadline, false)) }} {{ trans_choice('день|дня|дней', (int) ceil(now()->diffInDays($g->deadline, false))) }})
                                        @endif
                                    </div>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    @endif
@endsection
