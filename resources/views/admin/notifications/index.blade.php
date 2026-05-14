@extends('layouts.admin')

@section('title', 'Уведомления — Админ-панель')
@section('page-title', 'Управление уведомлениями')

@section('content')
    <x-admin.page-header title="Уведомления" :subtitle="'Всего по фильтру: ' . $notifications->total()">
        <x-slot:actions>
            @if(auth()->user()->isAdmin())
                {{-- Очистка прочитанных — только для admin --}}
                <form method="POST" action="{{ route('admin.notifications.purge') }}"
                      x-data="{ days: 30 }"
                      onsubmit="return confirm('Удалить прочитанные уведомления старше ' + document.getElementById('purge-days').value + ' дней?')"
                      class="flex flex-wrap items-center gap-2">
                    @csrf
                    <input id="purge-days" name="days" type="number" min="1" max="365"
                           x-model="days"
                           class="w-20 px-2 py-1.5 border border-gray-300 rounded-lg text-sm text-center">
                    <span class="text-xs text-gray-500">дней</span>
                    <button type="submit"
                            class="bg-red-100 hover:bg-red-200 text-red-700 px-3 py-1.5 rounded-lg text-sm">
                        Очистить прочитанные
                    </button>
                </form>
            @endif
        </x-slot:actions>
    </x-admin.page-header>

    {{-- Stat-карточки --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <x-card padding="p-4">
            <div class="text-2xl font-bold text-gray-800">{{ number_format($stats['total']) }}</div>
            <div class="text-xs text-gray-500 mt-1">Всего в БД</div>
        </x-card>
        <x-card padding="p-4">
            <div class="text-2xl font-bold text-amber-600">{{ number_format($stats['unread']) }}</div>
            <div class="text-xs text-gray-500 mt-1">Непрочитанных</div>
        </x-card>
        <x-card padding="p-4">
            <div class="text-2xl font-bold text-indigo-600">{{ number_format($stats['sent30d']) }}</div>
            <div class="text-xs text-gray-500 mt-1">За 30 дней</div>
        </x-card>
        <x-card padding="p-4">
            <div class="text-2xl font-bold text-green-600">{{ $stats['readRate'] }}%</div>
            <div class="text-xs text-gray-500 mt-1">Прочитано</div>
        </x-card>
    </div>

    {{-- Фильтры --}}
    <x-card class="mb-4">
        <form method="GET" action="{{ route('admin.notifications.index') }}"
              class="grid grid-cols-1 md:grid-cols-3 gap-3">
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">ID пользователя</label>
                <input type="number" name="user_id" value="{{ $filters['user_id'] ?? '' }}"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                       placeholder="например, 5">
            </div>
            <div>
                <label class="text-xs text-gray-500 font-medium block mb-1">Статус</label>
                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Любой</option>
                    <option value="unread" @selected(($filters['status'] ?? '') === 'unread')>Непрочитанные</option>
                    <option value="read"   @selected(($filters['status'] ?? '') === 'read')>Прочитанные</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit"
                        class="bg-gray-800 hover:bg-gray-900 text-white px-4 py-2 rounded-lg text-sm">
                    Применить
                </button>
                <a href="{{ route('admin.notifications.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">
                    Сбросить
                </a>
            </div>
        </form>
    </x-card>

    <x-card padding="p-0" class="overflow-hidden">
        <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Пользователь</th>
                    <th class="px-4 py-3 text-left">Тип</th>
                    <th class="px-4 py-3 text-left">Содержимое</th>
                    <th class="px-4 py-3 text-left">Статус</th>
                    <th class="px-4 py-3 text-left">Отправлено</th>
                    <th class="px-4 py-3 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @forelse ($notifications as $n)
                    @php
                        $data    = json_decode($n->data, true) ?? [];
                        $isRead  = ! is_null($n->read_at);
                        // Краткая сводка из JSON payload
                        $summary = match ($data['type'] ?? '') {
                            'daily_reminder' => implode(' · ', array_filter([
                                ($data['with_deadline_count'] ?? 0) > 0
                                    ? ($data['with_deadline_count'] . ' с дедл.') : null,
                                ($data['no_deadline_count'] ?? 0) > 0
                                    ? ($data['no_deadline_count'] . ' без дедл.') : null,
                                ($data['overdue_count'] ?? 0) > 0
                                    ? ($data['overdue_count'] . ' просроч.') : null,
                            ])) ?: 'нет активных целей',
                            default => '—',
                        };
                    @endphp
                    <tr class="{{ $isRead ? '' : 'bg-amber-50/30' }}">
                        <td class="px-4 py-3">
                            @if ($n->user_name)
                                <a href="{{ route('admin.users.show', $n->notifiable_id) }}"
                                   class="text-gray-800 hover:text-indigo-600 font-medium">
                                    {{ $n->user_name }}
                                </a>
                                <div class="text-xs text-gray-400">{{ $n->user_email }}</div>
                            @else
                                <span class="text-gray-400">#{{ $n->notifiable_id }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs">
                            {{ $data['type'] ?? class_basename($n->type) }}
                        </td>
                        <td class="px-4 py-3 text-gray-600 text-xs max-w-xs truncate">
                            {{ $summary }}
                        </td>
                        <td class="px-4 py-3">
                            @if ($isRead)
                                <x-badge tone="gray">прочитано</x-badge>
                            @else
                                <x-badge tone="amber">новое</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500 text-xs whitespace-nowrap">
                            {{ \Illuminate\Support\Carbon::parse($n->created_at)->format('d.m.Y H:i') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            <form method="POST"
                                  action="{{ route('admin.notifications.destroy', $n->id) }}"
                                  class="inline"
                                  onsubmit="return confirm('Удалить уведомление?')">
                                @csrf
                                @method('DELETE')
                                <button class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded">
                                    Удалить
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                            Уведомления по фильтру не найдены.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </x-card>

    @if ($notifications->hasPages())
        <div class="mt-4">
            {{ $notifications->links() }}
        </div>
    @endif
@endsection
