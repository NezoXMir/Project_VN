<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>

    @include('partials.theme-init')

    {{-- Tailwind CSS (CDN) --}}
    <script src="https://cdn.tailwindcss.com"></script>

    @include('partials.dark-mode-styles')

    {{-- Alpine.js (defer обязателен) --}}
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    @stack('head')
</head>
<body class="bg-gray-50 text-gray-900 antialiased min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-6">
            <h1 class="text-2xl font-bold text-indigo-600">🎯 Виртуальный наставник</h1>
            <p class="text-sm text-gray-500 mt-1">Постановка целей и отслеживание прогресса</p>
        </div>

        @if (session('success'))
            <div x-data="{ show: true }"
                 x-show="show"
                 x-init="setTimeout(() => show = false, 4000)"
                 class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            @yield('content')
        </div>
    </div>

    @stack('scripts')
</body>
</html>
