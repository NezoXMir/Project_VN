@extends('layouts.app')

@section('title', 'Дашборд — ' . config('app.name'))

@php
$cards = [
['label' => 'Активные цели', 'value' => $stats['active_goals'], 'color' => '#4F46E5', 'icon' => '🎯'],
['label' => 'Завершённые', 'value' => $stats['completed_goals'], 'color' => '#10B981', 'icon' => '✅'],
['label' => 'Выполнено задач', 'value' => $stats['done_tasks'].' / '.$stats['total_tasks'], 'color' => '#0EA5E9', 'icon' => '📋'],
['label' => 'Серия подряд', 'value' => $stats['streak'], 'color' => '#F59E0B', 'icon' => '🔥', 'suffix' => trans_choice('день|дня|дней', (int) $stats['streak'])],
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

        <a href="{{ route('achievements.index') }}"
            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            Достижения
        </a>

        {{-- Bell-иконка с уведомлениями --}}
        <div x-data="notificationsBell({{ $unreadNotifications->count() }}, {{ $unreadNotifications->toJson() }})"
             @click.outside="open = false"
             class="relative">
            <button @click="open = !open" type="button"
                    class="relative bg-gray-100 hover:bg-gray-200 text-gray-700 p-2 rounded-lg"
                    title="Уведомления">
                🔔
                <span x-show="count > 0"
                      x-text="count"
                      class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1"></span>
            </button>

            <div x-show="open"
                 x-transition.opacity
                 x-cloak
                 class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 z-40">
                <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                    <h3 class="font-semibold text-sm">Уведомления</h3>
                    <button x-show="items.length > 0"
                            @click="markAllRead()"
                            type="button"
                            class="text-xs text-indigo-600 hover:text-indigo-700">
                        Прочитать все
                    </button>
                </div>

                <template x-if="items.length === 0">
                    <div class="px-4 py-6 text-sm text-gray-500 text-center">
                        Новых уведомлений нет.
                    </div>
                </template>

                <template x-if="items.length > 0">
                    <ul class="max-h-80 overflow-y-auto divide-y divide-gray-50">
                        <template x-for="n in items" :key="n.id">
                            <li class="px-4 py-3 hover:bg-gray-50">
                                <div class="flex items-start justify-between gap-2">
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-800" x-text="summarize(n.data)"></p>
                                        <p class="text-xs text-gray-400 mt-0.5" x-text="formatDate(n.created_at)"></p>
                                    </div>
                                    <button @click="markRead(n.id)" type="button"
                                            class="text-xs text-gray-400 hover:text-gray-600">×</button>
                                </div>
                            </li>
                        </template>
                    </ul>
                </template>
            </div>
        </div>

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
        <a href="{{ route('profile.index') }}"
            class="w-9 h-9 rounded-full overflow-hidden bg-indigo-100 hover:ring-2 hover:ring-indigo-300 flex items-center justify-center transition"
            title="Профиль">
            @if (auth()->user()->avatarUrl())
            <img src="{{ auth()->user()->avatarUrl() }}" alt="Аватар" class="w-full h-full object-cover">
            @else
            <span class="text-sm font-semibold text-indigo-700">{{ auth()->user()->initial() }}</span>
            @endif
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

{{-- Рекомендации --}}
@if (! empty($recommendations))
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mb-6">
    <h2 class="text-lg font-semibold mb-4">Рекомендации</h2>
    <ul class="space-y-3">
        @foreach ($recommendations as $rec)
        @php
        $tone = match ($rec['severity']) {
        'danger' => ['bg' => 'bg-red-50', 'border' => 'border-red-200', 'title' => 'text-red-900'],
        'warning' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'title' => 'text-amber-900'],
        'info' => ['bg' => 'bg-indigo-50', 'border' => 'border-indigo-200', 'title' => 'text-indigo-900'],
        };
        $iconText = match ($rec['severity']) {
        'danger' => '⚠️',
        'warning' => '⚡',
        'info' => '✨',
        };
        @endphp
        <li class="rounded-lg border {{ $tone['bg'] }} {{ $tone['border'] }} px-4 py-3">
            <div class="flex items-start gap-3">
                <div class="text-xl flex-shrink-0 leading-tight">{{ $iconText }}</div>
                <div class="flex-1 min-w-0">
                    <h3 class="font-semibold {{ $tone['title'] }}">{{ $rec['title'] }}</h3>
                    <p class="text-sm text-gray-700 mt-1">{{ $rec['message'] }}</p>
                </div>
                @if (! empty($rec['action_url']))
                <a href="{{ $rec['action_url'] }}"
                    class="bg-white hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg text-sm border border-gray-200 flex-shrink-0 whitespace-nowrap">
                    {{ $rec['action_label'] ?? 'Перейти' }}
                </a>
                @endif
            </div>
        </li>
        @endforeach
    </ul>
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
                ? ['bg'=> 'bg-red-50', 'text' => 'text-red-700', 'pill' => 'bg-red-100 text-red-700']
                : ($daysLeft < 3
                    ? ['bg'=> 'bg-red-50', 'text' => 'text-red-700', 'pill' => 'bg-red-100 text-red-700']
                    : ($daysLeft < 7
                        ? ['bg'=> 'bg-amber-50', 'text' => 'text-amber-700', 'pill' => 'bg-amber-100 text-amber-700']
                        : ['bg' => 'bg-gray-50', 'text' => 'text-gray-700', 'pill' => 'bg-gray-100 text-gray-700']));
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

{{-- Последние достижения --}}
<div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 mt-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold">Последние достижения</h2>
        <a href="{{ route('achievements.index') }}"
            class="text-sm text-indigo-600 hover:text-indigo-700">
            Все →
        </a>
    </div>

    @if ($recentAchievements->isEmpty())
    <p class="text-sm text-gray-500 py-3">
        Достижений пока нет — закройте первую задачу, и кое-что разблокируется.
    </p>
    @else
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
        @foreach ($recentAchievements as $a)
        <div class="rounded-lg border border-indigo-200 bg-indigo-50/40 p-3 flex items-center gap-3">
            <div class="text-3xl">{{ $a->icon }}</div>
            <div class="min-w-0">
                <div class="font-semibold text-gray-900 truncate">{{ $a->name }}</div>
                <div class="text-xs text-gray-500">
                    {{ \Illuminate\Support\Carbon::parse($a->pivot->unlocked_at)->diffForHumans() }}
                </div>
            </div>
        </div>
        @endforeach
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
function notificationsBell(initialCount, initialItems) {
    return {
        open: false,
        count: initialCount,
        items: initialItems,

        formatDate(iso) {
            const d = new Date(iso);
            return d.toLocaleString('ru-RU', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' });
        },

        summarize(data) {
            const overdue = data.overdue_count || 0;
            const withDeadline = data.with_deadline_count || 0;
            const noDeadline = data.no_deadline_count || 0;
            const parts = [];
            if (overdue > 0) parts.push(`${overdue} просрочено`);
            if (withDeadline - overdue > 0) parts.push(`${withDeadline - overdue} с дедлайном`);
            if (noDeadline > 0) parts.push(`${noDeadline} без дедлайна`);
            return parts.length ? parts.join(', ') : 'Сводка по целям';
        },

        async req(url) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('Не удалось обновить уведомление');
            return res.json();
        },

        async markRead(id) {
            try {
                await this.req(`/notifications/${id}/read`);
                this.items = this.items.filter(n => n.id !== id);
                this.count = Math.max(0, this.count - 1);
            } catch (e) { /* fail silently */ }
        },

        async markAllRead() {
            try {
                await this.req('/notifications/read-all');
                this.items = [];
                this.count = 0;
            } catch (e) { /* fail silently */ }
        },
    };
}
</script>
@endpush

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
                    legend: {
                        display: false
                    },
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
                        ticks: {
                            stepSize: 1,
                            precision: 0
                        },
                        grid: {
                            color: '#F3F4F6'
                        },
                    },
                    x: {
                        ticks: {
                            autoSkip: true,
                            maxRotation: 0,
                            minRotation: 0,
                        },
                        grid: {
                            display: false
                        },
                    },
                },
            },
        });
    })();
</script>
@endpush
@endif