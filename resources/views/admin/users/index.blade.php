@extends('layouts.app')

@section('title', 'Пользователи — ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold">Пользователи</h1>
            <p class="text-sm text-gray-500 mt-1">Всего: {{ $users->count() }}</p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('admin.dashboard') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                ← Админ-панель
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    Выйти
                </button>
            </form>
        </div>
    </div>

    <x-flash-message type="success" />
    <x-flash-message type="error" />

    <x-card padding="p-0" class="overflow-hidden">
        <table class="min-w-full divide-y divide-gray-100 text-sm">
            <thead class="bg-gray-50 text-gray-600 uppercase text-xs">
                <tr>
                    <th class="px-4 py-3 text-left">Имя</th>
                    <th class="px-4 py-3 text-left">Email</th>
                    <th class="px-4 py-3 text-left">Роль</th>
                    <th class="px-4 py-3 text-left">Зарегистрирован</th>
                    <th class="px-4 py-3 text-right">Действия</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                @foreach ($users as $u)
                    @php $isSelf = $u->id === auth()->id(); @endphp
                    <tr class="{{ $isSelf ? 'bg-indigo-50/40' : '' }}">
                        <td class="px-4 py-3">
                            {{ $u->name }}
                            @if ($isSelf)
                                <span class="ml-1 text-xs text-indigo-600">(вы)</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-600">{{ $u->email }}</td>
                        <td class="px-4 py-3">
                            @if ($u->isAdmin())
                                <x-badge tone="indigo">admin</x-badge>
                            @else
                                <x-badge tone="gray">user</x-badge>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-500">
                            {{ $u->created_at->format('d.m.Y') }}
                        </td>
                        <td class="px-4 py-3 text-right">
                            @if ($isSelf)
                                <button disabled
                                        class="text-xs text-gray-400 cursor-not-allowed px-3 py-1 border border-gray-200 rounded-lg">
                                    Нельзя
                                </button>
                            @else
                                <form method="POST"
                                      action="{{ route('admin.users.update-role', $u->id) }}"
                                      class="inline-flex items-center gap-2">
                                    @csrf
                                    @method('PATCH')
                                    @if ($u->isAdmin())
                                        <input type="hidden" name="role" value="user">
                                        <button type="submit"
                                                class="text-xs bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-1 rounded-lg">
                                            Сделать пользователем
                                        </button>
                                    @else
                                        <input type="hidden" name="role" value="admin">
                                        <button type="submit"
                                                class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-lg">
                                            Сделать админом
                                        </button>
                                    @endif
                                </form>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </x-card>
@endsection
