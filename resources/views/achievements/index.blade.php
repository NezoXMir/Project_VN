@extends('layouts.app')

@section('title', 'Достижения — ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Достижения</h1>
            <p class="text-sm text-gray-500 mt-1">
                Получено: {{ $unlockedCount }} из {{ $totalCount }}
            </p>
        </div>

        <a href="{{ route('dashboard') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            ← Дашборд
        </a>
    </div>

    {{-- Прогресс по бейджам --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <div class="flex items-center justify-between mb-2">
            <div class="text-sm font-medium text-gray-700">Общий прогресс</div>
            <div class="text-sm font-semibold text-indigo-600">
                {{ $totalCount > 0 ? (int) round($unlockedCount / $totalCount * 100) : 0 }}%
            </div>
        </div>
        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
            <div class="h-full bg-indigo-600 transition-all duration-500"
                 style="width: {{ $totalCount > 0 ? ($unlockedCount / $totalCount * 100) : 0 }}%"></div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach ($achievements as $a)
            @php $isUnlocked = $a->unlocked_at !== null; @endphp
            <div class="bg-white rounded-xl shadow-sm border p-5 transition
                        {{ $isUnlocked ? 'border-indigo-200' : 'border-gray-100' }}">
                <div class="text-4xl mb-2 {{ $isUnlocked ? '' : 'opacity-30' }}">
                    {{ $isUnlocked ? $a->icon : '🔒' }}
                </div>
                <h3 class="font-semibold {{ $isUnlocked ? 'text-gray-900' : 'text-gray-500' }}">{{ $a->name }}</h3>
                <p class="text-xs text-gray-500 mt-1">{{ $a->description }}</p>

                @if ($isUnlocked)
                    <div class="mt-3 text-xs">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-700">
                            ✓ Получено {{ \Illuminate\Support\Carbon::parse($a->unlocked_at)->format('d.m.Y') }}
                        </span>
                    </div>
                @elseif ($a->progress)
                    <div class="mt-3">
                        <div class="flex items-center justify-between text-xs text-gray-600 mb-1">
                            <span>Прогресс</span>
                            <span class="font-medium">{{ $a->progress['current'] }} / {{ $a->progress['threshold'] }}</span>
                        </div>
                        <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div class="h-full bg-indigo-400 transition-all duration-500"
                                 style="width: {{ $a->progress['percent'] }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
@endsection
