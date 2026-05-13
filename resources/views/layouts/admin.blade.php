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

    @stack('head')
</head>
<body class="bg-gray-100 text-gray-900 antialiased min-h-screen">
    <div class="min-h-screen flex" x-data="{ sidebarOpen: false }">
        {{-- Sidebar --}}
        <aside class="hidden md:flex md:flex-col w-64 bg-slate-900 text-slate-100 fixed inset-y-0 left-0 z-30">
            <x-admin.sidebar />
        </aside>

        {{-- Mobile sidebar drawer --}}
        <div x-show="sidebarOpen" x-cloak class="md:hidden fixed inset-0 z-40">
            <div class="fixed inset-0 bg-black/40" @click="sidebarOpen = false"></div>
            <aside class="fixed inset-y-0 left-0 w-64 bg-slate-900 text-slate-100 flex flex-col"
                   x-transition:enter="transition transform duration-200"
                   x-transition:enter-start="-translate-x-full"
                   x-transition:enter-end="translate-x-0"
                   x-transition:leave="transition transform duration-150"
                   x-transition:leave-start="translate-x-0"
                   x-transition:leave-end="-translate-x-full">
                <x-admin.sidebar />
            </aside>
        </div>

        {{-- Main column --}}
        <div class="flex-1 md:ml-64 flex flex-col">
            {{-- Top bar --}}
            <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
                <div class="px-6 py-3 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button @click="sidebarOpen = true" class="md:hidden text-gray-600 hover:text-gray-900">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                        <h1 class="text-lg font-semibold text-gray-800">@yield('page-title', 'Админ-панель')</h1>
                    </div>

                    @auth
                        <div class="flex items-center gap-4">
                            <div class="text-right hidden sm:block">
                                <div class="text-sm font-medium text-gray-800">{{ auth()->user()->name }}</div>
                                <div class="text-xs text-gray-500">{{ auth()->user()->roleLabel() }}</div>
                            </div>
                            <div class="w-9 h-9 rounded-full bg-indigo-600 text-white flex items-center justify-center font-semibold text-sm">
                                {{ auth()->user()->initial() }}
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"
                                        class="text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-lg hover:bg-gray-100">
                                    Выйти
                                </button>
                            </form>
                        </div>
                    @endauth
                </div>
            </header>

            {{-- Page content --}}
            <main class="flex-1 p-6">
                <x-flash-message type="success" />
                <x-flash-message type="error" />

                @yield('content')
            </main>
        </div>
    </div>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
