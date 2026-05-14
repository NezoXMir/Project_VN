<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Админ-панель — ' . config('app.name'))</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <style>[x-cloak] { display: none !important; }</style>
    @stack('head')
</head>
<body class="bg-gray-100 text-gray-900 antialiased min-h-screen">

<div class="min-h-screen flex"
     x-data="adminLayout()"
     @keydown.escape.window="sidebarOpen && closeSidebar()">

    {{-- Desktop sidebar --}}
    <aside class="hidden md:flex md:flex-col w-64 bg-slate-900 text-slate-100 fixed inset-y-0 left-0 z-30">
        <x-admin.sidebar />
    </aside>

    {{-- Mobile sidebar drawer --}}
    <div x-show="sidebarOpen" x-cloak class="md:hidden fixed inset-0 z-40">
        {{-- Overlay --}}
        <div class="fixed inset-0 bg-black/50"
             @click="closeSidebar()"
             aria-hidden="true"
             x-transition:enter="transition-opacity duration-200 ease-out"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity duration-150 ease-in"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"></div>

        {{-- Drawer panel --}}
        <aside id="admin-mobile-sidebar"
               class="fixed inset-y-0 left-0 w-72 bg-slate-900 text-slate-100 flex flex-col shadow-2xl"
               role="dialog"
               aria-modal="true"
               aria-label="Меню администратора"
               tabindex="-1"
               x-transition:enter="transition transform duration-250 ease-out"
               x-transition:enter-start="-translate-x-full"
               x-transition:enter-end="translate-x-0"
               x-transition:leave="transition transform duration-200 ease-in"
               x-transition:leave-start="translate-x-0"
               x-transition:leave-end="-translate-x-full">
            <x-admin.sidebar />
        </aside>
    </div>

    {{-- Main column — min-w-0 prevents flex child from expanding beyond viewport --}}
    <div class="flex-1 md:ml-64 flex flex-col min-w-0">

        {{-- Top bar --}}
        <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
            <div class="px-4 sm:px-6 py-3 flex items-center justify-between gap-3">

                <div class="flex items-center gap-3 min-w-0">
                    {{-- Burger (mobile only) --}}
                    <button x-ref="menuBtn"
                            @click="openSidebar()"
                            aria-label="Открыть меню"
                            :aria-expanded="sidebarOpen.toString()"
                            aria-controls="admin-mobile-sidebar"
                            class="md:hidden flex-shrink-0 p-2 -ml-1 rounded-lg text-gray-600
                                   hover:text-gray-900 hover:bg-gray-100 active:bg-gray-200
                                   focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>

                    <h1 class="text-base sm:text-lg font-semibold text-gray-800 truncate">
                        @yield('page-title', 'Админ-панель')
                    </h1>
                </div>

                @auth
                    <div class="flex items-center gap-2 flex-shrink-0">
                        <div class="text-right hidden sm:block">
                            <div class="text-sm font-medium text-gray-800 truncate max-w-[140px]">{{ auth()->user()->name }}</div>
                            <div class="text-xs text-gray-500">{{ auth()->user()->roleLabel() }}</div>
                        </div>
                        <div class="w-8 h-8 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold text-sm flex-shrink-0">
                            {{ auth()->user()->initial() }}
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                    aria-label="Выйти из системы"
                                    title="Выйти"
                                    class="p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50
                                           active:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                            </button>
                        </form>
                    </div>
                @endauth
            </div>
        </header>

        {{-- Page content --}}
        <main class="flex-1 p-4 sm:p-6">
            <x-flash-message type="success" />
            <x-flash-message type="error" />

            @yield('content')
        </main>
    </div>
</div>

<script>
function adminLayout() {
    return {
        sidebarOpen: false,

        openSidebar() {
            this.sidebarOpen = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                document.getElementById('admin-mobile-sidebar')?.focus();
            });
        },

        closeSidebar() {
            this.sidebarOpen = false;
            document.body.style.overflow = '';
            this.$nextTick(() => this.$refs.menuBtn?.focus());
        },
    };
}
</script>

@yield('scripts')
@stack('scripts')
</body>
</html>
