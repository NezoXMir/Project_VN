@extends('layouts.app')

@section('title', 'Админ-панель — ' . config('app.name'))

@php
    $cards = [
        ['label' => 'Пользователи',         'value' => $stats['users_total'],         'sub' => $stats['users_admin'].' admin', 'color' => '#4F46E5', 'icon' => '👥'],
        ['label' => 'Активные за 30 дней',  'value' => $stats['users_active_30d'],    'sub' => $stats['users_new_30d'].' новых', 'color' => '#10B981', 'icon' => '📈'],
        ['label' => 'Всего целей',           'value' => $stats['goals_total'],         'sub' => $stats['goals_active'].' активных', 'color' => '#0EA5E9', 'icon' => '🎯'],
        ['label' => 'Задач выполнено',       'value' => $stats['tasks_done'].' / '.$stats['tasks_total'], 'sub' => $stats['tasks_completion_rate'].'%', 'color' => '#F59E0B', 'icon' => '✅'],
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Админ-панель</h1>
            <p class="text-sm text-gray-500 mt-1">Системная статистика — только агрегаты, без персональных данных.</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('dashboard') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                ← Мой дашборд
            </a>
            <a href="{{ route('admin.users.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                Пользователи
            </a>
        </div>
    </div>

    {{-- KPI-карточки --}}
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach ($cards as $c)
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 border-l-4"
                 style="border-left-color: {{ $c['color'] }}">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ $c['label'] }}</div>
                        <div class="mt-1 text-2xl font-bold" style="color: {{ $c['color'] }}">{{ $c['value'] }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $c['sub'] }}</div>
                    </div>
                    <div class="text-2xl opacity-80">{{ $c['icon'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Распределение целей по статусам --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
        <h2 class="text-lg font-semibold mb-4">Распределение целей</h2>
        @if ($stats['goals_total'] === 0)
            <p class="text-sm text-gray-500">В системе пока нет целей.</p>
        @else
            @php
                $segments = [
                    ['label' => 'Активные',     'value' => $stats['goals_active'],    'color' => '#4F46E5'],
                    ['label' => 'Завершённые',  'value' => $stats['goals_completed'], 'color' => '#10B981'],
                    ['label' => 'В архиве',     'value' => $stats['goals_archived'],  'color' => '#94A3B8'],
                ];
            @endphp
            <div class="flex w-full h-3 rounded-full overflow-hidden mb-3">
                @foreach ($segments as $s)
                    @if ($s['value'] > 0)
                        <div style="background-color: {{ $s['color'] }}; width: {{ ($s['value'] / $stats['goals_total']) * 100 }}%"
                             title="{{ $s['label'] }}: {{ $s['value'] }}"></div>
                    @endif
                @endforeach
            </div>
            <div class="grid grid-cols-3 gap-3 text-sm">
                @foreach ($segments as $s)
                    <div class="flex items-center gap-2">
                        <span class="inline-block w-3 h-3 rounded-full" style="background-color: {{ $s['color'] }}"></span>
                        <span class="text-gray-600">{{ $s['label'] }}:</span>
                        <span class="font-semibold">{{ $s['value'] }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Регистрации за 30 дней --}}
        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-lg font-semibold mb-4">Регистрации за 30 дней</h2>
            @if (collect($stats['registrations_30d'])->sum('count') === 0)
                <p class="text-sm text-gray-500 py-8 text-center">
                    Новых регистраций за последние 30 дней нет.
                </p>
            @else
                <div class="relative h-64">
                    <canvas id="registrationsChart"></canvas>
                </div>

                @php
                    $regDays = collect($stats['registrations_30d'])
                        ->where('count', '>', 0)
                        ->sortByDesc('date')
                        ->values();
                @endphp
                @if ($regDays->isNotEmpty())
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700 mb-2">Расшифровка по дням</h3>
                        <table class="w-full text-sm border-collapse">
                            <thead>
                                <tr class="bg-gray-50">
                                    <th class="border border-gray-200 px-3 py-1.5 text-left font-semibold text-gray-700">Дата</th>
                                    <th class="border border-gray-200 px-3 py-1.5 text-right font-semibold text-gray-700">Зарегистрировано</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($regDays as $row)
                                    <tr>
                                        <td class="border border-gray-200 px-3 py-1.5 text-gray-700">
                                            {{ \Illuminate\Support\Carbon::parse($row['date'])->format('d.m.Y') }}
                                        </td>
                                        <td class="border border-gray-200 px-3 py-1.5 text-right font-medium text-gray-900">
                                            {{ $row['count'] }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </div>

        {{-- Топ категорий --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <h2 class="text-lg font-semibold mb-4">Популярные категории</h2>

            @if (empty($stats['top_categories']) || collect($stats['top_categories'])->sum('goals_count') === 0)
                <p class="text-sm text-gray-500 py-4">
                    Пока нет данных по категориям.
                </p>
            @else
                <ul class="space-y-2">
                    @foreach ($stats['top_categories'] as $cat)
                        <li class="flex items-center justify-between py-1.5 px-2 rounded">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="inline-block w-3 h-3 rounded-full flex-shrink-0"
                                      style="background-color: {{ $cat['color'] }}"></span>
                                <span class="text-sm text-gray-800 truncate">{{ $cat['label'] }}</span>
                                @if ($cat['is_system'])
                                    <span class="text-xs text-gray-400">сист.</span>
                                @endif
                            </div>
                            <span class="text-sm font-semibold text-gray-700 ml-2">{{ $cat['goals_count'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
@endsection

@if (collect($stats['registrations_30d'])->sum('count') > 0)
@push('scripts')
<script>
(function() {
    const data = @json($stats['registrations_30d']);
    const ctx = document.getElementById('registrationsChart');
    if (!ctx) return;

    const labels = data.map(d => {
        const [, m, day] = d.date.split('-');
        return `${day}.${m}`;
    });
    const counts = data.map(d => d.count);

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels,
            datasets: [{
                label: 'Регистрации',
                data: counts,
                backgroundColor: counts.map(c => c > 0 ? '#10B981' : '#E5E7EB'),
                borderRadius: 4,
                maxBarThickness: 24,
            }],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: { callbacks: { label: ctx => `${ctx.parsed.y} новых` } },
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: { stepSize: 1, precision: 0 },
                    grid: { color: '#F3F4F6' },
                },
                x: {
                    ticks: { autoSkip: true, maxRotation: 0 },
                    grid: { display: false },
                },
            },
        },
    });
})();
</script>
@endpush
@endif
