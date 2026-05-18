<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    @include('partials.theme-init')

    {{-- Tailwind CSS (CDN, без npm/vite — см. CLAUDE.md) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    @include('partials.dark-mode-styles')

    {{-- Alpine.js (defer обязателен) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Chart.js — для дашбордных графиков --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        /* Sticky navbar wrapper background follows the page background in both themes */
        .nav-sticky-bg { background-color: #f9fafb; }          /* gray-50  */
        html.dark .nav-sticky-bg { background-color: #0f172a; } /* dark body */
    </style>

    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen">

@auth

{{-- ============================================================
     МОБИЛЬНАЯ НАВИГАЦИЯ (< sm) — sticky топ-бар + drawer
     ============================================================ --}}
<div class="sm:hidden" x-data="appMobileNav()" @keydown.escape.window="open && closeDrawer()">

    {{-- Sticky топ-бар --}}
    <div class="sticky top-0 z-30 nav-sticky-bg border-b border-gray-100">
        <div class="flex items-center justify-between px-4 py-2">

            {{-- Бургер --}}
            <button x-ref="burgerBtn"
                    @click="openDrawer()"
                    aria-label="Открыть меню"
                    :aria-expanded="open"
                    aria-controls="app-mobile-drawer"
                    class="p-2 -ml-1 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100
                           active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>

            {{-- Название приложения --}}
            <a href="{{ route('dashboard') }}"
               class="font-semibold text-gray-900 text-sm tracking-tight hover:text-indigo-600 transition">
                {{ config('app.name') }}
            </a>

            {{-- Правая группа: тема + колокол + аватар + выход --}}
            <div class="flex items-center gap-0.5">

                {{-- Переключатель темы --}}
                @include('partials.theme-switcher', [
                    'btnClass'      => 'p-2 rounded-xl text-gray-600 hover:text-gray-900 hover:bg-gray-100 active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition',
                    'iconSize'      => 'w-5 h-5',
                    'dropdownClass' => 'absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50',
                ])

                {{-- Колокол уведомлений --}}
                @php $navNtf = $navUnreadNotifications ?? collect(); @endphp
                <div x-data="notificationsBell({{ $navNtf->count() }}, {{ $navNtf->toJson() }})"
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
                   class="w-8 h-8 mx-0.5 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center
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
    <aside id="app-mobile-drawer"
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

        {{-- Ссылки меню с активным состоянием --}}
        @php $cr = request()->route()?->getName() ?? ''; @endphp
        <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1" aria-label="Основная навигация">

            <a href="{{ route('dashboard') }}"
               @click="closeDrawer()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition group
                      {{ $cr === 'dashboard'
                         ? 'bg-indigo-50 text-indigo-700'
                         : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500' }}">
                <svg class="w-5 h-5 flex-shrink-0 transition {{ $cr === 'dashboard' ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Дашборд
            </a>

            <a href="{{ route('goals.index') }}"
               @click="closeDrawer()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition group
                      {{ str_starts_with($cr, 'goals')
                         ? 'bg-indigo-50 text-indigo-700'
                         : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500' }}">
                <svg class="w-5 h-5 flex-shrink-0 transition {{ str_starts_with($cr, 'goals') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                </svg>
                Мои цели
            </a>

            <a href="{{ route('categories.index') }}"
               @click="closeDrawer()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition group
                      {{ str_starts_with($cr, 'categories')
                         ? 'bg-indigo-50 text-indigo-700'
                         : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500' }}">
                <svg class="w-5 h-5 flex-shrink-0 transition {{ str_starts_with($cr, 'categories') ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"
                     fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round"
                          d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 10V5a2 2 0 012-2z"/>
                </svg>
                Категории
            </a>

            <a href="{{ route('achievements.index') }}"
               @click="closeDrawer()"
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition group
                      {{ $cr === 'achievements.index'
                         ? 'bg-indigo-50 text-indigo-700'
                         : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500' }}">
                <svg class="w-5 h-5 flex-shrink-0 transition {{ $cr === 'achievements.index' ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"
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
               class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition group
                      {{ $cr === 'profile.index'
                         ? 'bg-indigo-50 text-indigo-700'
                         : 'text-gray-700 hover:text-indigo-700 hover:bg-indigo-50 active:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500' }}">
                <svg class="w-5 h-5 flex-shrink-0 transition {{ $cr === 'profile.index' ? 'text-indigo-500' : 'text-gray-400 group-hover:text-indigo-500' }}"
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
     ДЕСКТОПНАЯ НАВИГАЦИЯ (≥ sm) — sticky карточка-навбар
     ============================================================ --}}
<div class="hidden sm:block sticky top-0 z-30 nav-sticky-bg">
    <div class="max-w-7xl mx-auto px-4 py-3">
        <nav class="bg-white rounded-2xl shadow-sm border border-gray-100 px-3 py-2 flex items-center gap-2"
             aria-label="Основная навигация">

            {{-- Логотип + название --}}
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-2 px-2 py-1.5 rounded-xl hover:bg-gray-50 transition flex-shrink-0"
               aria-label="На главную">
                <span class="text-xl leading-none" aria-hidden="true">🎯</span>
                <span class="font-bold text-gray-900 tracking-tight text-sm hidden lg:block whitespace-nowrap">
                    {{ config('app.name') }}
                </span>
            </a>

            {{-- Разделитель --}}
            <div class="w-px h-5 bg-gray-200 flex-shrink-0 mx-0.5"></div>

            {{-- Навигационные ссылки с иконками --}}
            @php $r = request()->route()?->getName() ?? ''; @endphp
            <div class="flex items-center gap-0.5 flex-1">

                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium transition whitespace-nowrap
                          {{ $r === 'dashboard' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Дашборд
                </a>

                <a href="{{ route('goals.index') }}"
                   class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium transition whitespace-nowrap
                          {{ str_starts_with($r, 'goals') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                    Мои цели
                </a>

                <a href="{{ route('categories.index') }}"
                   class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium transition whitespace-nowrap
                          {{ str_starts_with($r, 'categories') ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-5 5a2 2 0 01-2.828 0l-7-7A2 2 0 013 10V5a2 2 0 012-2z"/>
                    </svg>
                    Категории
                </a>

                <a href="{{ route('achievements.index') }}"
                   class="flex items-center gap-1.5 px-3 py-2 rounded-xl text-sm font-medium transition whitespace-nowrap
                          {{ $r === 'achievements.index' ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100' }}">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                    </svg>
                    Достижения
                </a>
            </div>

            {{-- Правые элементы управления --}}
            <div class="flex items-center gap-0.5 flex-shrink-0">

                {{-- Переключатель темы --}}
                @include('partials.theme-switcher', [
                    'btnClass'      => 'p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100 active:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition',
                    'iconSize'      => 'w-5 h-5',
                    'dropdownClass' => 'absolute right-0 mt-2 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-40',
                ])

                {{-- Колокол уведомлений --}}
                <div x-data="notificationsBell({{ $navNtf->count() }}, {{ $navNtf->toJson() }})"
                     @click.outside="open = false"
                     class="relative">
                    <button @click="open = !open" type="button"
                            aria-label="Уведомления"
                            :aria-expanded="open"
                            class="relative p-2 rounded-xl text-gray-500 hover:text-gray-900 hover:bg-gray-100
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
                         class="absolute right-0 mt-2 w-80 max-w-[calc(100vw-1rem)] bg-white rounded-xl
                                shadow-lg border border-gray-100 z-40">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                            <h3 class="font-semibold text-sm">Уведомления</h3>
                            <button x-show="items.length > 0" @click="markAllRead()" type="button"
                                    class="text-xs text-indigo-600 hover:text-indigo-700">Прочитать все</button>
                        </div>
                        <template x-if="items.length === 0">
                            <div class="px-4 py-6 text-sm text-gray-500 text-center">Новых уведомлений нет.</div>
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
                                                    class="text-xs text-gray-400 hover:text-gray-600 flex-shrink-0">×</button>
                                        </div>
                                    </li>
                                </template>
                            </ul>
                        </template>
                    </div>
                </div>

                {{-- Разделитель --}}
                <div class="w-px h-5 bg-gray-200 flex-shrink-0 mx-0.5"></div>

                {{-- Ссылка на админ-панель (только для staff) --}}
                @if (auth()->user()->isStaff())
                <a href="{{ route('admin.dashboard') }}"
                   title="Админ-панель"
                   aria-label="Перейти в админ-панель"
                   class="p-2 rounded-xl text-gray-500 hover:text-indigo-600 hover:bg-indigo-50
                          active:bg-indigo-100 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                </a>
                @endif

                {{-- Аватар / профиль --}}
                <a href="{{ route('profile.index') }}"
                   aria-label="Профиль"
                   title="{{ auth()->user()->name }}"
                   class="w-8 h-8 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center
                          hover:ring-2 hover:ring-indigo-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition flex-shrink-0">
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
                            title="Выйти"
                            class="p-2 rounded-xl text-gray-500 hover:text-red-600 hover:bg-red-50
                                   active:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </nav>
    </div>
</div>

@endauth

{{-- Контент страницы --}}
<div class="max-w-7xl mx-auto px-4 py-6">
    @yield('content')
</div>

@yield('scripts')
@stack('scripts')

<script>
function appMobileNav() {
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
            const last  = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last)  { e.preventDefault(); first.focus(); }
            }
        },
    };
}

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
            const overdue      = data.overdue_count || 0;
            const withDeadline = data.with_deadline_count || 0;
            const noDeadline   = data.no_deadline_count || 0;
            const parts = [];
            if (overdue > 0)                        parts.push(`${overdue} просрочено`);
            if (withDeadline - overdue > 0)         parts.push(`${withDeadline - overdue} с дедлайном`);
            if (noDeadline > 0)                     parts.push(`${noDeadline} без дедлайна`);
            return parts.length ? parts.join(', ') : 'Сводка по целям';
        },

        async req(url) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const res  = await fetch(url, {
                method: 'POST',
                headers: {
                    'Accept':            'application/json',
                    'X-CSRF-TOKEN':      csrf,
                    'X-Requested-With':  'XMLHttpRequest',
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
            } catch (e) { /* silent */ }
        },

        async markAllRead() {
            try {
                await this.req('/notifications/read-all');
                this.items = [];
                this.count = 0;
            } catch (e) { /* silent */ }
        },
    };
}

function themeSwitcher() {
    return {
        theme: localStorage.getItem('theme') || 'system',
        open: false,

        get currentIcon() {
            if (this.theme === 'dark')  return 'moon';
            if (this.theme === 'light') return 'sun';
            return 'system';
        },

        setTheme(t) {
            this.theme = t;
            localStorage.setItem('theme', t);
            const dark = t === 'dark' ||
                (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.add('theme-transitioning');
            document.documentElement.classList.toggle('dark', dark);
            setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 300);
            this.open = false;
        },

        init() {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (this.theme === 'system') {
                    document.documentElement.classList.add('theme-transitioning');
                    document.documentElement.classList.toggle('dark', e.matches);
                    setTimeout(() => document.documentElement.classList.remove('theme-transitioning'), 300);
                }
            });
        },
    };
}
</script>
</body>
</html>
