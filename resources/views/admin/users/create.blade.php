@extends('layouts.admin')

@section('title', 'Создать пользователя — Админ-панель')
@section('page-title', 'Создать пользователя')

@section('content')
    <x-admin.page-header
        title="Новый пользователь"
        subtitle="Заполните поля. Пароль можно сгенерировать кнопкой справа.">
        <x-slot:actions>
            <a href="{{ route('admin.users.index') }}"
               class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                ← К списку
            </a>
        </x-slot:actions>
    </x-admin.page-header>

    <x-card class="max-w-2xl">
        @if ($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 text-sm p-3 rounded-lg mb-4">
                <ul class="list-disc pl-5 space-y-0.5">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.store') }}"
              x-data="{
                  password: @js(old('password', '')),
                  /**
                   * Алфавит без визуально похожих символов (0/O, 1/l/I) —
                   * пароль легко продиктовать по телефону.
                   */
                  alphabet: 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789',
                  generate() {
                      let out = '';
                      for (let i = 0; i < 8; i++) {
                          out += this.alphabet[Math.floor(Math.random() * this.alphabet.length)];
                      }
                      this.password = out;
                  },
              }"
              class="space-y-4">
            @csrf

            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Имя</label>
                <input type="text" name="name" value="{{ old('name') }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Роль</label>
                <select name="role" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    <option value="user"    @selected(old('role') === 'user' || ! old('role'))>Пользователь</option>
                    <option value="manager" @selected(old('role') === 'manager')>Менеджер</option>
                    <option value="admin"   @selected(old('role') === 'admin')>Администратор</option>
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Менеджер имеет доступ к админ-панели с ограниченными правами (без управления ролями и удаления).
                </p>
            </div>

            <div>
                <label class="text-sm font-medium text-gray-700 block mb-1">Пароль</label>
                <div class="flex gap-2">
                    <input type="text" name="password" x-model="password" required
                           class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono">
                    <button type="button" @click="generate()"
                            class="bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-2 rounded-lg text-sm whitespace-nowrap">
                        🎲 Сгенерировать
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-1">
                    Скопируйте пароль и передайте пользователю — после сохранения он будет показан ещё раз во flash-сообщении, далее увидеть его будет нельзя.
                </p>
            </div>

            <div class="flex items-center gap-2 pt-2">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    Создать
                </button>
                <a href="{{ route('admin.users.index') }}"
                   class="text-sm text-gray-500 hover:text-gray-700 px-3 py-2">
                    Отмена
                </a>
            </div>
        </form>
    </x-card>
@endsection
