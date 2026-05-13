@php
    /**
     * Меню админ-панели. Элемент скрыт, если у текущего пользователя нет
     * указанной ability — это делает sidebar самосогласованным с Policy.
     * Например, manager не увидит пункт «Пользователи» если admin-only.
     */
    $items = [
        ['route' => 'admin.dashboard',          'label' => 'Дашборд',      'icon' => '📊', 'ability' => null],
        ['route' => 'admin.users.index',        'label' => 'Пользователи', 'icon' => '👥', 'ability' => null],
        ['route' => 'admin.goals.index',        'label' => 'Цели',         'icon' => '🎯', 'ability' => null],
        ['route' => 'admin.categories.index',   'label' => 'Категории',    'icon' => '🏷️', 'ability' => null],
        ['route' => 'admin.archive.index',      'label' => 'Архив',        'icon' => '🗄️', 'ability' => null],
        ['route' => 'admin.notifications.index','label' => 'Уведомления',  'icon' => '🔔', 'ability' => null],
        ['route' => 'admin.profile.edit',       'label' => 'Мой профиль',  'icon' => '👤', 'ability' => null],
    ];
@endphp

<div class="flex items-center gap-2 px-5 py-5 border-b border-slate-800">
    <span class="text-2xl">🛠️</span>
    <div>
        <div class="font-bold text-sm leading-tight">Виртуальный наставник</div>
        <div class="text-xs text-slate-400">Админ-панель</div>
    </div>
</div>

<nav class="flex-1 px-3 py-4 space-y-1 overflow-y-auto">
    @foreach ($items as $item)
        @php
            // Используем Route::has — пока на разных этапах часть маршрутов
            // ещё не зарегистрирована, sidebar не должен падать на route().
            $exists = \Illuminate\Support\Facades\Route::has($item['route']);
            $href = $exists ? route($item['route']) : '#';
            $active = $exists && request()->routeIs($item['route']);
        @endphp
        <a href="{{ $href }}"
           class="flex items-center gap-3 px-3 py-2 rounded-lg text-sm
                  {{ $active ? 'bg-indigo-600 text-white' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}
                  {{ $exists ? '' : 'opacity-40 pointer-events-none' }}">
            <span class="text-base">{{ $item['icon'] }}</span>
            <span>{{ $item['label'] }}</span>
        </a>
    @endforeach
</nav>

<div class="px-5 py-3 border-t border-slate-800 text-xs text-slate-500">
    v1.0 · {{ now()->format('d.m.Y') }}
</div>
