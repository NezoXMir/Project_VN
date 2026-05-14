@extends('layouts.admin')

@section('title', 'Дашборд — Админ-панель')
@section('page-title', 'Дашборд')

@section('content')
    <x-admin.page-header
        title="Системная статистика"
        subtitle="Сводка по пользователям, целям, задачам и уведомлениям." />

    {{-- Группа 1: пользователи --}}
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Пользователи</h3>
    @php
        $userCards = [
            ['label' => 'Всего',              'value' => $stats['users_total'],     'sub' => $stats['users_new_30d'].' новых за 30 дн.', 'color' => '#4F46E5', 'icon' => '👥'],
            ['label' => 'Активных за 30 дн.', 'value' => $stats['users_active_30d'],'sub' => 'выполняли задачи',                          'color' => '#10B981', 'icon' => '📈'],
            ['label' => 'Заблокированных',    'value' => $stats['users_blocked'],   'sub' => 'не могут войти',                            'color' => '#EF4444', 'icon' => '🚫'],
            ['label' => 'Администраторов',    'value' => $stats['users_admin'],     'sub' => $stats['users_manager'].' менеджеров',       'color' => '#8B5CF6', 'icon' => '🛡️'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach ($userCards as $c)
            <x-card :accent="$c['color']">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ $c['label'] }}</div>
                        <div class="mt-1 text-2xl font-bold" style="color: {{ $c['color'] }}">{{ $c['value'] }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $c['sub'] }}</div>
                    </div>
                    <div class="text-2xl opacity-80">{{ $c['icon'] }}</div>
                </div>
            </x-card>
        @endforeach
    </div>

    {{-- Группа 2: цели --}}
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Цели</h3>
    @php
        $goalCards = [
            ['label' => 'Всего',         'value' => $stats['goals_total'],     'sub' => 'в системе',                  'color' => '#0EA5E9', 'icon' => '🎯'],
            ['label' => 'Активных',      'value' => $stats['goals_active'],    'sub' => 'в работе',                   'color' => '#4F46E5', 'icon' => '🚀'],
            ['label' => 'Завершённых',   'value' => $stats['goals_completed'], 'sub' => 'успешно достигнуто',         'color' => '#10B981', 'icon' => '✅'],
            ['label' => 'В архиве',      'value' => $stats['goals_archived'],  'sub' => 'отложено',                   'color' => '#64748B', 'icon' => '🗄️'],
            ['label' => 'Просроченных',  'value' => $stats['goals_overdue'],   'sub' => 'дедлайн в прошлом',          'color' => '#EF4444', 'icon' => '⏰'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        @foreach ($goalCards as $c)
            <x-card :accent="$c['color']">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ $c['label'] }}</div>
                        <div class="mt-1 text-2xl font-bold" style="color: {{ $c['color'] }}">{{ $c['value'] }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $c['sub'] }}</div>
                    </div>
                    <div class="text-2xl opacity-80">{{ $c['icon'] }}</div>
                </div>
            </x-card>
        @endforeach
    </div>

    {{-- Группа 3: задачи + уведомления --}}
    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Задачи и уведомления</h3>
    @php
        $mixCards = [
            ['label' => 'Задач выполнено',  'value' => $stats['tasks_done'].' / '.$stats['tasks_total'], 'sub' => $stats['tasks_completion_rate'].'% от всех', 'color' => '#10B981', 'icon' => '☑️'],
            ['label' => 'Задач сегодня',    'value' => $stats['tasks_done_today'],                       'sub' => 'выполнено',                                'color' => '#4F46E5', 'icon' => '🌞'],
            ['label' => 'Задач за неделю',  'value' => $stats['tasks_done_week'],                        'sub' => 'выполнено',                                'color' => '#0EA5E9', 'icon' => '📅'],
            ['label' => 'Уведомлений 30дн', 'value' => $stats['notifications_sent_30d'],                 'sub' => $stats['notifications_unread'].' непрочитанных', 'color' => '#F59E0B', 'icon' => '🔔'],
        ];
    @endphp
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach ($mixCards as $c)
            <x-card :accent="$c['color']">
                <div class="flex items-start justify-between">
                    <div>
                        <div class="text-xs text-gray-500 uppercase tracking-wide">{{ $c['label'] }}</div>
                        <div class="mt-1 text-2xl font-bold" style="color: {{ $c['color'] }}">{{ $c['value'] }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $c['sub'] }}</div>
                    </div>
                    <div class="text-2xl opacity-80">{{ $c['icon'] }}</div>
                </div>
            </x-card>
        @endforeach
    </div>

    {{-- Распределение целей по статусам --}}
    <x-card class="mb-6">
        <x-section-header title="Распределение целей по статусам" />
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
    </x-card>

    {{-- Два графика рядом --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <x-card>
            <x-section-header title="Регистрации за 30 дней" />
            @if (collect($stats['registrations_30d'])->sum('count') === 0)
                <p class="text-sm text-gray-500 py-8 text-center">
                    Новых регистраций за последние 30 дней нет.
                </p>
            @else
                <div class="relative h-56">
                    <canvas id="registrationsChart"></canvas>
                </div>
            @endif
        </x-card>

        <x-card>
            <x-section-header title="Активность (задачи) за 30 дней" />
            @if (collect($stats['activity_30d'])->sum('count') === 0)
                <p class="text-sm text-gray-500 py-8 text-center">
                    Задач за последние 30 дней не выполнено.
                </p>
            @else
                <div class="relative h-56">
                    <canvas id="activityChart"></canvas>
                </div>
            @endif
        </x-card>
    </div>

    {{-- Топ категорий --}}
    <x-card>
        <x-section-header title="Популярные категории" />

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
    </x-card>
@endsection

@push('scripts')
<script>
(function () {
    // Общий рендерер столбчатого графика — две метрики одинаковые по форме.
    function renderBar(canvasId, data, color) {
        const ctx = document.getElementById(canvasId);
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
                    data: counts,
                    backgroundColor: counts.map(c => c > 0 ? color : '#E5E7EB'),
                    borderRadius: 4,
                    maxBarThickness: 18,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: ctx => `${ctx.parsed.y}` } },
                },
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 1, precision: 0 }, grid: { color: '#F3F4F6' } },
                    x: { ticks: { autoSkip: true, maxRotation: 0 }, grid: { display: false } },
                },
            },
        });
    }

    @if (collect($stats['registrations_30d'])->sum('count') > 0)
        renderBar('registrationsChart', @json($stats['registrations_30d']), '#10B981');
    @endif
    @if (collect($stats['activity_30d'])->sum('count') > 0)
        renderBar('activityChart', @json($stats['activity_30d']), '#4F46E5');
    @endif
})();
</script>
@endpush
