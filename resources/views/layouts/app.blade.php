<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    {{-- Tailwind CSS (CDN, без npm/vite — см. CLAUDE_CODE_PROMPT.md) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Alpine.js (defer обязателен) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    {{-- Chart.js — для дашбордных графиков --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-6">
        @yield('content')
    </div>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
