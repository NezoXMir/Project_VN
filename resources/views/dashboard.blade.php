@extends('layouts.app')

@section('title', 'Дашборд — ' . config('app.name'))

@php
    $cards = [
        ['label' => 'Активные цели',   'value' => $stats['active_goals'],    'color' => '#4F46E5', 'icon' => '🎯'],
        ['label' => 'Завершённые',     'value' => $stats['completed_goals'], 'color' => '#10B981', 'icon' => '✅'],
        ['label' => 'Выполнено задач', 'value' => $stats['done_tasks'].' / '.$stats['total_tasks'], 'color' => '#0EA5E9', 'icon' => '📋'],
        ['label' => 'Серия подряд',    'value' => $stats['streak'],          'color' => '#F59E0B', 'icon' => '🔥', 'suffix' => trans_choice('день|дня|дней', (int) $stats['streak'])],
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Здравствуйте, {{ auth()->user()->name }}!</h1>

        <div class="flex items-center gap-3">
            <a href="{{ route('goals.index') }}"
               class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                Мои цели
            </a>

            <a href="{{ route('categories.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                Категории
            </a>

            @if (auth()->user()->isAdmin())
                <a href="{{ route('admin.dashboard') }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    Админ-панель
                </a>
            @endif

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    Выйти
                </button>
            </form>
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

    {{-- KPI-карточки --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach ($cards as $c)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 border-l-4"
                 style="border-left-color: {{ $c['color'] }}">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ $c['label'] }}</div>
                        <div class="mt-1 flex items-baseline gap-1">
                            <div class="text-2xl font-bold" style="color: {{ $c['color'] }}">{{ $c['value'] }}</div>
                            @isset($c['suffix'])
                                <div class="text-sm text-gray-500">{{ $c['suffix'] }}</div>
                            @endisset
                        </div>
                    </div>
                    <div class="text-2xl opacity-80">{{ $c['icon'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Общий прогресс --}}
    @if ($stats['total_tasks'] > 0)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
            <div class="flex items-center justify-between mb-2">
                <div class="text-sm font-medium text-gray-700">Общий прогресс по всем задачам</div>
                <div class="text-sm font-semibold text-indigo-600">{{ $stats['overall_progress'] }}%</div>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                <div class="h-full bg-indigo-600 transition-all duration-500"
                     style="width: {{ $stats['overall_progress'] }}%"></div>
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- График активности --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-lg font-semibold mb-4">Активность за 30 дней</h2>
            @if (collect($stats['activity_30d'])->sum('count') === 0)
                <p class="text-sm text-gray-500 py-8 text-center">
                    Пока нет завершённых задач. Откройте цель, разбейте её на подцели
                    и начните выполнять — статистика подтянется.
                </p>
            @else
                <div class="relative h-64">
                    <canvas id="activityChart"></canvas>
                </div>
            @endif
        </div>

        {{-- Ближайшие дедлайны --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-lg font-semibold mb-4">Ближайшие дедлайны</h2>

            @if ($stats['upcoming_deadlines']->isEmpty())
                <p class="text-sm text-gray-500 py-4">
                    На ближайшие 14 дней дедлайнов нет.
                </p>
            @else
                <ul class="space-y-3">
                    @foreach ($stats['upcoming_deadlines'] as $g)
                        @php
                            $daysLeft = (int) ceil(now()->diffInDays($g->deadline, false));
                            $tone = $daysLeft < 0
                                ? ['bg' => 'bg-red-50',    'text' => 'text-red-700',    'pill' => 'bg-red-100 text-red-700']
                                : ($daysLeft < 3
                                    ? ['bg' => 'bg-red-50',    'text' => 'text-red-700',    'pill' => 'bg-red-100 text-red-700']
                                    : ($daysLeft < 7
                                        ? ['bg' => 'bg-amber-50',  'text' => 'text-amber-700',  'pill' => 'bg-amber-100 text-amber-700']
                                        : ['bg' => 'bg-gray-50',   'text' => 'text-gray-700',   'pill' => 'bg-gray-100 text-gray-700']));
                            $catColor = $g->category?->color ?? '#64748B';
                            $timeLeft = \App\Helpers\DateHelper::timeLeftLabel($g->deadline);
                            $catLabel = $g->category?->label ?? 'без категории';
                        @endphp
                        <li class="rounded-lg {{ $tone['bg'] }} p-3">
                            <a href="{{ route('goals.show', $g->id) }}" class="block hover:opacity-90">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="text-sm font-medium {{ $tone['text'] }} truncate">{{ $g->title }}</div>
                                        <div class="mt-1 flex items-center gap-2 flex-wrap">
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-xs"
                                                  style="background-color: {{ $catColor }}1A; color: {{ $catColor }}">
                                                {{ $catLabel }}
                                            </span>
                                            <span class="text-xs text-gray-500">
                                                {{ $g->deadline->format('d.m.Y') }}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs whitespace-nowrap {{ $tone['pill'] }}">
                                        @if ($daysLeft < 0)
                                            просрочено
                                        @else
                                            {{ $timeLeft }}
                                        @endif
                                    </span>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection

@if (collect($stats['activity_30d'])->sum('count') > 0)
@push('scripts')
<script>
(function() {
    const data = @json($stats['activity_30d']);
    const ctx = document.getElementById('activityChart');
    if (!ctx) return;

    // Метки дней — сокращённый формат "08.05"
    const labels = data.map(d => {
        const [y, m, day] = d.date.split('-');
        return `${day}.${m}`;
    });
    const counts = data.map(d => d.count);
    const maxVal = Math.max(...counts, 1);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Завершено задач',
                data: counts,
                backgroundColor: counts.map(c => c > 0 ? '#4F46E5' : '#E5E7EB'),
                borderRadius: 4,
                maxBarThickness: 24,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: ctx => `${ctx.parsed.y} задач(и)`,
                    },
                },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    suggestedMax: maxVal + 1,
                    ticks: { stepSize: 1, precision: 0 },
                    grid: { color: '#F3F4F6' },
                },
                x: {
                    ticks: {
                        autoSkip: true,
                        maxRotation: 0,
                        minRotation: 0,
                    },
                    grid: { display: false },
                },
            },
        },
    });
})();
</script>
@endpush
@endif
