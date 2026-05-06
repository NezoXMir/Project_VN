{{-- Параметры:
     $action  — URL для action атрибута формы
     $method  — 'POST' или 'PATCH'
     $palette — массив hex-цветов для пресетов
     $category — опционально, для edit
     $submitLabel — текст кнопки сохранения --}}

@php
    $current = old('color', $category->color ?? $palette[0]);
    $label = old('label', $category->label ?? '');
@endphp

@if ($errors->any())
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-3 text-sm">
        <ul class="list-disc list-inside space-y-0.5">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="space-y-4"
      x-data="{ color: '{{ $current }}' }">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="label" class="block text-sm font-medium text-gray-700 mb-1">Название</label>
        <input id="label" name="label" type="text" required maxlength="60" autofocus
               value="{{ $label }}"
               class="w-full rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Цвет</label>

        <div class="flex flex-wrap items-center gap-2 mb-3">
            @foreach ($palette as $hex)
                <button type="button"
                        @click="color = '{{ $hex }}'"
                        :class="color === '{{ $hex }}' ? 'ring-2 ring-offset-2 ring-gray-700' : ''"
                        class="w-8 h-8 rounded-full border border-gray-200 hover:scale-110 transition"
                        style="background-color: '{{ $hex }}'"
                        title="{{ $hex }}"></button>
            @endforeach
        </div>

        <div class="flex items-center gap-3">
            <label for="color-picker" class="text-sm text-gray-600">Свой цвет:</label>
            <input id="color-picker"
                   type="color"
                   x-model="color"
                   class="w-12 h-8 rounded border border-gray-200 cursor-pointer">
            <span class="text-sm font-mono text-gray-500" x-text="color"></span>
        </div>

        {{-- Реальное поле, отправляемое на сервер --}}
        <input type="hidden" name="color" :value="color">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">Превью</label>
        <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-sm font-medium"
              :style="`background-color: ${color}1A; color: ${color}`">
            <span class="w-2 h-2 rounded-full" :style="`background-color: ${color}`"></span>
            <span x-text="document.getElementById('label').value || 'Название'"></span>
        </span>
    </div>

    <div class="flex items-center gap-3 pt-2">
        <button type="submit"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm">
            {{ $submitLabel }}
        </button>
        <a href="{{ route('categories.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            Отмена
        </a>
    </div>
</form>
