@extends('layouts.app')

@section('title', 'Новая цель — ' . config('app.name'))

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold">Новая цель</h1>
        <a href="{{ route('goals.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            ← К списку
        </a>
    </div>

    <x-card padding="p-6" class="max-w-2xl">
        @if ($errors->any())
            <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('goals.store') }}" class="space-y-4">
            @csrf

            <div>
                <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Название</label>
                <input id="title" name="title" type="text" required maxlength="200"
                       value="{{ old('title') }}" autofocus
                       class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            </div>

            <div>
                <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Категория</label>
                <select id="category_id" name="category_id" required
                        class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border bg-white">
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->id }}" @selected((int) old('category_id') === $cat->id)>
                            {{ $cat->label }}{{ $cat->is_system ? '' : ' (моя)' }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-gray-500 mt-1">
                    Управление списком — на странице
                    <a href="{{ route('categories.index') }}" class="text-indigo-600 hover:underline">«Категории»</a>.
                </p>
            </div>

            <div>
                <label for="deadline" class="block text-sm font-medium text-gray-700 mb-1">Дедлайн (необязательно)</label>
                <input id="deadline" name="deadline" type="date"
                       value="{{ old('deadline') }}"
                       min="{{ now()->addDay()->format('Y-m-d') }}"
                       class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            </div>

            <div>
                <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Описание (необязательно)</label>
                <textarea id="description" name="description" rows="4" maxlength="5000"
                          class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">{{ old('description') }}</textarea>
            </div>

            <div class="flex items-center gap-3 pt-2">
                <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
                    Создать цель
                </button>
                <a href="{{ route('goals.index') }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    Отмена
                </a>
            </div>
        </form>
    </x-card>
@endsection
