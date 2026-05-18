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

    <style>[x-cloak] { display: none !important; }</style>

    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
    </div>

    {{-- Floating theme switcher — hidden on pages that embed their own (via @push('head')) --}}
    <div id="theme-float" class="fixed bottom-5 right-5 z-50">
        @include('partials.theme-switcher', [
            'btnClass'      => 'w-10 h-10 flex items-center justify-center rounded-full bg-white shadow-lg border border-gray-200 text-gray-600 hover:text-gray-900 hover:shadow-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 transition',
            'iconSize'      => 'w-5 h-5',
            'dropdownClass' => 'absolute bottom-full right-0 mb-2 w-44 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50',
        ])
    </div>

    @yield('scripts')
    @stack('scripts')

    <script>
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
