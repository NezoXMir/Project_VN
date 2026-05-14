@extends('layouts.app')

@section('title', 'Дашборд — ' . config('app.name'))

@push('head')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@php
$cards = [
['label' => 'Активные цели', 'value' => $stats['active_goals'], 'color' => '#4F46E5', 'icon' => '🎯'],
['label' => 'Завершённые', 'value' => $stats['completed_goals'], 'color' => '#10B981', 'icon' => '✅'],
['label' => 'Выполнено задач', 'value' => $stats['done_tasks'].' / '.$stats['total_tasks'], 'color' => '#0EA5E9', 'icon' => '📋'],
['label' => 'Серия подряд', 'value' => $stats['streak'], 'color' => '#F59E0B', 'icon' => '🔥', 'suffix' => trans_choice('день|дня|дней', (int) $stats['streak'])],
];
@endphp

@section('content')

{{-- ============================================================
     МОБИЛЬНАЯ НАВИГАЦИЯ — только на дашборде, только на mobile
     ============================================================ --}}
<div class="sm:hidden"
     x-data="dashboardMobileNav()"
     @keydown.escape.window="open && closeDrawer()">

    {{-- Топ-бар --}}
    <div class="flex items-center justify-between py-2 mb-4">

        {{-- Бургер --}}
        <button x-ref="burgerBtn"
                @click="openDrawer()"
                aria-label="Открыть меню"
                :aria-expanded="open"
                aria-controls="mobile-nav-drawer"
                class="p-2 -ml-1 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100
                       active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>

        {{-- Название приложения --}}
        <span class="font-semibold text-gray-900 text-sm tracking-tight">{{ config('app.name') }}</span>

        {{-- Правая группа: колокол + аватар + выход --}}
        <div class="flex items-center gap-0.5">

            {{-- Колокол уведомлений --}}
            <div x-data="notificationsBell({{ $unreadNotifications->count() }}, {{ $unreadNotifications->toJson() }})"
                 @click.outside="open = false"
                 class="relative">
                <button @click="open = !open" type="button"
                        aria-label="Уведомления"
                        :aria-expanded="open"
                        class="relative p-2 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100
                               active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span x-show="count > 0" x-text="count"
                          class="absolute -top-0.5 -right-0.5 bg-red-500 text-white text-[10px] font-bold rounded-full
                                 min-w-[16px] h-[16px] flex items-center justify-center px-0.5 leading-none"></span>
                </button>

                <div x-show="open" x-transition.opacity x-cloak
                     class="absolute right-0 mt-2 w-72 max-w-[calc(100vw-1rem)] bg-white rounded-xl
                            shadow-lg border border-gray-100 z-40">
                    <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                        <h3 class="font-semibold text-sm">Уведомления</h3>
                        <button x-show="items.length > 0" @click="markAllRead()" type="button"
                                class="text-xs text-indigo-600 hover:text-indigo-700">Прочитать все</button>
                    </div>
                    <template x-if="items.length === 0">
                        <div class="px-4 py-5 text-sm text-gray-500 text-center">Новых уведомлений нет.</div>
                    </template>
                    <template x-if="items.length > 0">
                        <ul class="max-h-64 overflow-y-auto divide-y divide-gray-50">
                            <template x-for="n in items" :key="n.id">
                                <li class="px-4 py-3 hover:bg-gray-50">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-800" x-text="summarize(n.data)"></p>
                                            <p class="text-xs text-gray-400 mt-0.5" x-text="formatDate(n.created_at)"></p>
                                        </div>
                                        <button @click="markRead(n.id)" type="button"
                                                class="text-xs text-gray-400 hover:text-gray-600 flex-shrink-0">×</button>
                                    </div>
                                </li>
                            </template>
                        </ul>
                    </template>
                </div>
            </div>

            {{-- Аватар / профиль --}}
            <a href="{{ route('profile.index') }}"
               aria-label="Профиль"
               class="w-8 h-8 mx-1 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center
                      hover:ring-2 hover:ring-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                @if (auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="Аватар" class="w-full h-full object-cover">
                @else
                    <span class="text-xs font-semibold text-indigo-700">{{ auth()->user()->initial() }}</span>
                @endif
            </a>

            {{-- Выход --}}
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        aria-label="Выйти из аккаунта"
                        class="p-2 rounded-xl text-gray-500 hover:text-red-600 hover:bg-red-50
                               active:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>

    {{-- Затемнение фона --}}
    <div x-show="open" x-cloak
         x-transition:enter="transition-opacity duration-300 ease-out"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity duration-200 ease-in"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="closeDrawer()"
         class="fixed inset-0 bg-black/50 z-40"
         aria-hidden="true"></div>

    {{-- Drawer --}}
    <aside id="mobile-nav-drawer"
           x-show="open" x-cloak
           x-ref="drawer"
           x-transition:enter="transition transform duration-300 ease-out"
           x-transition:enter-start="-translate-x-full"
           x-transition:enter-end="translate-x-0"
           x-transition:leave="transition transform duration-200 ease-in"
           x-transition:leave-start="translate-x-0"
           x-transition:leave-end="-translate-x-full"
           @keydown.tab="trapFocus($event)"
           role="dialog"
           aria-modal="true"
           aria-label="Навигация"
           class="fixed inset-y-0 left-0 w-72 bg-white shadow-2xl z-50 flex flex-col">

        {{-- Шапка drawer --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100 flex-shrink-0">
            <span class="font-bold text-gray-900">{{ config('app.name') }}</span>
            <button @click="closeDrawer()"
                    aria-label="Закрыть меню"
                    class="p-1.5 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100
                           focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Информация о пользователе --}}
        <div class="flex items-center gap-3 px-5 py-4 bg-gray-50 border-b border-gray-100 flex-shrink-0">
            <div class="w-10 h-10 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center flex-shrink-0">
                @if (auth()->user()->avatarUrl())
                    <img src="{{ auth()->user()->avatarUrl() }}" alt="Аватар" class="w-full h-full object-cover">
                @else
                    <span class="text-sm font-semibold text-indigo-700">{{ auth()->user()->initial() }}</span>
                @endif
            </div>
            <div class="min-w-0">
                <div class="font-medium text-gray-900 truncate text-sm">{{ auth()->user()->name }}</div>
                <div class="text-xs text-gray-500 truncate">{{ auth()->user()->email }}</div>
            </div>
        </div>

        {{-- Ссылки меню --}}
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" aria-label="Основная навигация">
            <a href="{{ route('goals.index') }}"
               @click="closeDrawer()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-gray-700
                      hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 transition group">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 flex-shrink-0 transition"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Мои цели
            </a>

            <a href="{{ route('categories.index') }}"
               @click="closeDrawer()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-gray-700
                      hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 transition group">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 flex-shrink-0 transition"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 10V5a2 2 0 012-2z"/>
                </svg>
                Категории
            </a>

            <a href="{{ route('achievements.index') }}"
               @click="closeDrawer()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-gray-700
                      hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 transition group">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 flex-shrink-0 transition"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                Достижения
            </a>
        </nav>

        {{-- Подвал drawer: профиль + выход --}}
        <div class="flex-shrink-0 px-3 py-4 border-t border-gray-100 space-y-1">
            <a href="{{ route('profile.index') }}"
               @click="closeDrawer()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium text-gray-700
                      hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100
                      focus:outline-none focus:ring-2 focus:ring-indigo-500 transition group">
                <svg class="w-5 h-5 text-gray-400 group-hover:text-indigo-500 flex-shrink-0 transition"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                </svg>
                Профиль
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="flex items-center gap-3 w-full px-4 py-3 rounded-xl text-sm font-medium text-gray-600
                               hover:text-red-600 hover:bg-red-50 active:bg-red-100
                               focus:outline-none focus:ring-2 focus:ring-red-400 transition group">
                    <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 flex-shrink-0 transition"
                         fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Выйти
                </button>
            </form>
        </div>
    </aside>
</div>

{{-- ============================================================
     ДЕСКТОПНАЯ НАВИГАЦИЯ — скрыта на mobile (sm:)
     ============================================================ --}}
<div class="hidden sm:flex items-center justify-between gap-3 mb-6">
    <h1 class="text-2xl font-bold">Здравствуйте, {{ auth()->user()->name }}!</h1>

    <div class="flex flex-wrap items-center gap-2">
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
                 class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-1rem)] bg-white rounded-xl shadow-lg border border-gray-100 z-40">
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

<x-flash-message type="success" />

{{-- KPI-карточки --}}
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @foreach ($cards as $c)
    <x-card :accent="$c['color']">
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
    </x-card>
    @endforeach
</div>

{{-- Общий прогресс --}}
@if ($stats['total_tasks'] > 0)
<x-card class="mb-6">
    <div class="flex items-center justify-between mb-2">
        <div class="text-sm font-medium text-gray-700">Общий прогресс по всем задачам</div>
        <div class="text-sm font-semibold text-indigo-600">{{ $stats['overall_progress'] }}%</div>
    </div>
    <x-progress-bar :value="$stats['overall_progress']" />
</x-card>
@endif

{{-- Рекомендации --}}
@if (! empty($recommendations))
<x-card class="mb-6">
    <x-section-header title="Рекомендации" />
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
</x-card>
@endif

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- График активности --}}
    <x-card class="lg:col-span-2">
        <x-section-header title="Активность за 30 дней" />
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
    </x-card>

    {{-- Ближайшие дедлайны --}}
    <x-card>
        <x-section-header title="Ближайшие дедлайны" />

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
    </x-card>
</div>

{{-- Последние достижения --}}
<x-card class="mt-6">
    <x-section-header title="Последние достижения">
        <x-slot:action>
            <a href="{{ route('achievements.index') }}"
                class="text-sm text-indigo-600 hover:text-indigo-700">
                Все →
            </a>
        </x-slot:action>
    </x-section-header>

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
</x-card>
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

function dashboardMobileNav() {
    return {
        open: false,

        openDrawer() {
            this.open = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                const focusable = this.$refs.drawer?.querySelector(
                    'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
                );
                focusable?.focus();
            });
        },

        closeDrawer() {
            this.open = false;
            document.body.style.overflow = '';
            this.$nextTick(() => this.$refs.burgerBtn?.focus());
        },

        trapFocus(e) {
            if (!this.open) return;
            const focusable = [...this.$refs.drawer.querySelectorAll(
                'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )];
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
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