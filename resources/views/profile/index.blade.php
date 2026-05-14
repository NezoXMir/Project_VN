@extends('layouts.app')

@section('title', 'Профиль — ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Профиль</h1>

        <a href="{{ route('dashboard') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            ← Дашборд
        </a>
    </div>

    <x-flash-message type="success" />

    {{-- Статус подтверждения email --}}
    <x-card padding="p-5" class="mb-6">
        <x-section-header title="Подтверждение email" />

        @if ($user->isEmailVerified())
            <div class="flex items-center gap-3 bg-green-50 border border-green-200 rounded-lg px-4 py-3">
                <span class="text-green-600 text-lg font-bold">✓</span>
                <div>
                    <div class="text-sm font-medium text-green-800">Email подтверждён</div>
                    <div class="text-xs text-green-600">{{ $user->email_verified_at->format('d.m.Y') }}</div>
                </div>
            </div>
        @else
            <div class="flex items-center justify-between gap-4 bg-amber-50 border border-amber-200 rounded-lg px-4 py-3">
                <div>
                    <div class="text-sm font-medium text-amber-800">Email не подтверждён</div>
                    <div class="text-xs text-amber-600 mt-0.5">Подтвердите почту для полного доступа к возможностям платформы</div>
                </div>
                <form method="POST" action="{{ route('email.verification.resend') }}" class="shrink-0">
                    @csrf
                    <button type="submit"
                            class="bg-amber-500 hover:bg-amber-600 text-white text-xs font-medium px-3 py-1.5 rounded-lg transition whitespace-nowrap">
                        Подтвердить email
                    </button>
                </form>
            </div>
        @endif
    </x-card>

    {{-- Профиль: аватар, имя, email, bio --}}
    <x-card padding="p-6" class="mb-6">
        <x-section-header title="Основные данные" />

        <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-4">
            @csrf
            @method('PATCH')

            @if ($errors->hasAny(['name', 'email', 'bio', 'avatar']))
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach (['name', 'email', 'bio', 'avatar'] as $field)
                            @foreach ($errors->get($field) as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="flex flex-col sm:flex-row gap-4" x-data="{
                preview: '{{ $user->avatarUrl() }}',
                onPick(e) {
                    const f = e.target.files?.[0];
                    if (!f) return;
                    this.preview = URL.createObjectURL(f);
                }
            }">
                <div class="w-20 h-20 rounded-full overflow-hidden bg-indigo-100 flex items-center justify-center flex-shrink-0">
                    <template x-if="preview">
                        <img :src="preview" alt="Аватар" class="w-full h-full object-cover">
                    </template>
                    <template x-if="!preview">
                        <span class="text-2xl font-semibold text-indigo-700">{{ $user->initial() }}</span>
                    </template>
                </div>

                <div class="flex-1">
                    <label for="avatar" class="block text-sm font-medium text-gray-700 mb-1">Аватар</label>
                    <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"
                           @change="onPick"
                           class="block w-full text-sm text-gray-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-gray-100 file:text-gray-700 hover:file:bg-gray-200 file:cursor-pointer">
                    <p class="text-xs text-gray-500 mt-1">JPEG / PNG / WebP, до 2 МБ.</p>
                </div>
            </div>

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Имя</label>
                <input id="name" name="name" type="text" required maxlength="120"
                       value="{{ old('name', $user->name) }}"
                       class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            </div>

            <div>
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input id="email" name="email" type="email" required maxlength="160"
                       value="{{ old('email', $user->email) }}"
                       class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            </div>

            <div>
                <label for="bio" class="block text-sm font-medium text-gray-700 mb-1">О себе (необязательно)</label>
                <textarea id="bio" name="bio" rows="3" maxlength="500"
                          class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">{{ old('bio', $user->bio) }}</textarea>
                <p class="text-xs text-gray-500 mt-1">До 500 символов.</p>
            </div>

            <div>
                <label class="flex items-center gap-2 text-sm font-medium text-gray-700">
                    <input type="checkbox" name="email_reminders_enabled" value="1"
                           {{ old('email_reminders_enabled', $user->email_reminders_enabled) ? 'checked' : '' }}
                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                    Получать ежедневные email-напоминания о дедлайнах
                </label>
                <p class="text-xs text-gray-500 mt-1 ml-6">
                    Письмо приходит утром, если есть просроченные или приближающиеся дедлайны.
                    Уведомления в bell остаются всегда.
                </p>
            </div>

            <div>
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    Сохранить
                </button>
            </div>
        </form>
    </x-card>

    {{-- Смена пароля --}}
    <x-card padding="p-6" class="mb-6">
        <x-section-header title="Смена пароля" />

        <form method="POST" action="{{ route('profile.password') }}" class="space-y-4">
            @csrf
            @method('PATCH')

            @if ($errors->hasAny(['current_password', 'password']))
                <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                    <ul class="list-disc list-inside space-y-0.5">
                        @foreach (['current_password', 'password'] as $field)
                            @foreach ($errors->get($field) as $msg)
                                <li>{{ $msg }}</li>
                            @endforeach
                        @endforeach
                    </ul>
                </div>
            @endif

            <div>
                <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1">Текущий пароль</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password"
                       class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Новый пароль</label>
                <input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"
                       class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                <p class="text-xs text-gray-500 mt-1">Минимум 8 символов.</p>
            </div>

            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">Повторите новый пароль</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password"
                       class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            </div>

            <div>
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    Сменить пароль
                </button>
            </div>
        </form>
    </x-card>

    {{-- Опасная зона. Используем x-card с явно переопределённым border --}}
    <x-card padding="p-6" class="!border-red-200">
        <h2 class="text-lg font-semibold text-red-700 mb-2">Опасная зона</h2>
        <p class="text-sm text-gray-600 mb-4">
            Удаление аккаунта необратимо. Все ваши категории, цели, подцели и задачи будут удалены вместе с аккаунтом.
        </p>

        <div x-data="{ show: false }">
            <button type="button"
                    x-show="!show"
                    @click="show = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                Удалить аккаунт
            </button>

            <form x-show="show"
                  x-cloak
                  method="POST" action="{{ route('profile.destroy') }}"
                  onsubmit="return confirm('Точно удалить аккаунт? Действие необратимо.');"
                  class="space-y-3 max-w-md">
                @csrf
                @method('DELETE')

                @if ($errors->has('confirm_password'))
                    <div class="rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                        {{ $errors->first('confirm_password') }}
                    </div>
                @endif

                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Подтвердите паролем</label>
                    <input id="confirm_password" name="confirm_password" type="password" required autocomplete="current-password"
                           class="w-full rounded-lg border-gray-300 focus:border-red-500 focus:ring-red-500 px-3 py-2 border">
                </div>

                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                        Удалить навсегда
                    </button>
                    <button type="button"
                            @click="show = false"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                        Отмена
                    </button>
                </div>
            </form>
        </div>
    </x-card>
@endsection
