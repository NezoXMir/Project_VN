@extends('layouts.app')

@section('title', $goal->title . ' — ' . config('app.name'))

@php
    $statusTone = match ($goal->status) {
        'active'    => 'blue',
        'completed' => 'green',
        'archived'  => 'gray',
    };
    $statusLabel = match ($goal->status) {
        'active'    => 'активная',
        'completed' => 'завершена',
        'archived'  => 'в архиве',
    };
    $progress = $goal->progress;
    $catColor = $goal->category?->color ?? '#64748B';
    $catLabel = $goal->category?->label ?? 'без категории';

    // JSON для Alpine: подцели с задачами в нужной форме.
    $subtasksJson = $goal->subtasks->map(fn ($s) => [
        'id' => $s->id,
        'title' => $s->title,
        'tasks' => $s->tasks->map(fn ($t) => [
            'id' => $t->id,
            'title' => $t->title,
            'is_done' => (bool) $t->is_done,
        ])->values()->all(),
    ])->values()->all();
@endphp

@section('content')
    <x-flash-message type="success" />

    <a href="{{ route('goals.index') }}"
       class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-800 transition mb-4">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
        </svg>
        <span class="hidden sm:inline">Мои цели</span>
        <span class="sm:hidden">Назад</span>
    </a>

    <div x-data="goalView({{ $goal->id }}, {{ $progress }}, '{{ $catColor }}', @js($subtasksJson))">

        {{-- Toasts разблокированных достижений (fixed, вне потока — не влияет на spacing карточек) --}}
        <div class="fixed top-4 right-4 z-50 space-y-2 w-[calc(100vw-2rem)] sm:w-auto sm:max-w-sm" style="pointer-events: none;">
            <template x-for="t in toasts" :key="t.id">
                <div x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="bg-white rounded-xl shadow-lg border border-indigo-200 p-4 flex items-start gap-3"
                     style="pointer-events: auto;">
                    <div class="text-3xl" x-text="t.icon"></div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wide">Достижение</div>
                        <div class="font-semibold text-gray-900" x-text="t.name"></div>
                    </div>
                    <button @click="dismissToast(t.id)" class="text-gray-400 hover:text-gray-600 text-lg leading-none">×</button>
                </div>
            </template>
        </div>

        <div class="space-y-6">

        <x-card padding="p-6" :accent="$catColor">
            <div class="flex items-start justify-between gap-3 mb-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                        <x-badge :color="$catColor">{{ $catLabel }}</x-badge>
                        <x-badge :tone="$statusTone">{{ $statusLabel }}</x-badge>
                    </div>
                    <h1 class="text-xl sm:text-2xl font-bold break-words">{{ $goal->title }}</h1>
                </div>

                {{-- Action buttons --}}
                <div class="shrink-0" x-data="{ open: false }">

                    {{-- Desktop: all buttons visible --}}
                    <div class="hidden sm:flex items-center gap-2">
                        @if ($goal->status === 'active')
                            <a href="{{ route('goals.edit', $goal->id) }}"
                               class="flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L7.5 19.213l-4 1 1-4L16.862 3.487z"/>
                                </svg>
                                Редактировать
                            </a>
                            <form method="POST" action="{{ route('goals.complete', $goal->id) }}">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                    </svg>
                                    Завершить
                                </button>
                            </form>
                            <form method="POST" action="{{ route('goals.do-archive', $goal->id) }}">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 px-3 py-2 rounded-lg text-sm font-medium transition">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a1 1 0 00-1 1v1a1 1 0 001 1h16a1 1 0 001-1V8a1 1 0 00-1-1zM5 12v7a1 1 0 001 1h12a1 1 0 001-1v-7"/>
                                    </svg>
                                    В архив
                                </button>
                            </form>
                        @endif
                        @if ($goal->status === 'archived')
                            <form method="POST" action="{{ route('goals.restore', $goal->id) }}">
                                @csrf
                                <button type="submit"
                                        class="flex items-center gap-1.5 bg-indigo-100 hover:bg-indigo-200 text-indigo-700 px-3 py-2 rounded-lg text-sm font-medium transition">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M4.582 9A8 8 0 0120 15M19.419 15A8 8 0 014 9"/>
                                    </svg>
                                    Восстановить
                                </button>
                            </form>
                        @endif
                        <form method="POST" action="{{ route('goals.destroy', $goal->id) }}"
                              onsubmit="return confirm('Удалить цель безвозвратно?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit"
                                    class="flex items-center gap-1.5 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-lg text-sm font-medium transition">
                                <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4"/>
                                </svg>
                                Удалить
                            </button>
                        </form>
                    </div>

                    {{-- Mobile: 3-dot dropdown --}}
                    <div class="sm:hidden relative">
                        <button @click="open = !open"
                                class="w-9 h-9 flex items-center justify-center rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-700 transition"
                                aria-label="Действия">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <circle cx="12" cy="5" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="12" cy="19" r="1.5"/>
                            </svg>
                        </button>

                        <div x-show="open"
                             x-cloak
                             @click.outside="open = false"
                             x-transition:enter="transition ease-out duration-100"
                             x-transition:enter-start="opacity-0 scale-95"
                             x-transition:enter-end="opacity-100 scale-100"
                             x-transition:leave="transition ease-in duration-75"
                             x-transition:leave-start="opacity-100 scale-100"
                             x-transition:leave-end="opacity-0 scale-95"
                             class="absolute right-0 top-full mt-1 w-52 bg-white border border-gray-100 rounded-xl shadow-lg z-30 py-1 origin-top-right">

                            @if ($goal->status === 'active')
                                <a href="{{ route('goals.edit', $goal->id) }}"
                                   class="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                                    <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 113.182 3.182L7.5 19.213l-4 1 1-4L16.862 3.487z"/>
                                    </svg>
                                    Редактировать
                                </a>
                                <form method="POST" action="{{ route('goals.complete', $goal->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-green-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Завершить
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('goals.do-archive', $goal->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 text-gray-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20 7H4a1 1 0 00-1 1v1a1 1 0 001 1h16a1 1 0 001-1V8a1 1 0 00-1-1zM5 12v7a1 1 0 001 1h12a1 1 0 001-1v-7"/>
                                        </svg>
                                        В архив
                                    </button>
                                </form>
                            @endif
                            @if ($goal->status === 'archived')
                                <form method="POST" action="{{ route('goals.restore', $goal->id) }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-indigo-700 hover:bg-gray-50">
                                        <svg class="w-4 h-4 text-indigo-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582M20 20v-5h-.581M4.582 9A8 8 0 0120 15M19.419 15A8 8 0 014 9"/>
                                        </svg>
                                        Восстановить
                                    </button>
                                </form>
                            @endif
                            <div class="border-t border-gray-100 my-1"></div>
                            <form method="POST" action="{{ route('goals.destroy', $goal->id) }}"
                                  onsubmit="return confirm('Удалить цель безвозвратно?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                        class="w-full flex items-center gap-3 px-4 py-2.5 text-sm text-red-600 hover:bg-red-50">
                                    <svg class="w-4 h-4 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4"/>
                                    </svg>
                                    Удалить
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </div>

            @if ($goal->description)
                <p class="text-gray-600 whitespace-pre-line mb-4">{{ $goal->description }}</p>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                <div>
                    <div class="text-gray-500">Создана</div>
                    <div class="font-medium">{{ $goal->created_at->format('d.m.Y') }}</div>
                </div>

                <div>
                    <div class="text-gray-500">Дедлайн</div>
                    <div class="font-medium">
                        {{ $goal->deadline ? $goal->deadline->format('d.m.Y') : '—' }}
                    </div>
                </div>

                <div>
                    <div class="text-gray-500">Прогресс</div>
                    <div class="font-medium" x-text="progress + '%'"></div>
                </div>
            </div>

            <div class="mt-4 w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                <div class="h-full transition-all duration-300"
                     :style="`width: ${progress}%; background-color: ${color}`"></div>
            </div>
        </x-card>

        <x-card padding="p-6">
            <x-section-header title="Подцели и задачи">
                <x-slot:action>
                    <span class="text-xs text-gray-500"
                          x-text="totalDone + ' из ' + totalTasks + ' задач'"></span>
                </x-slot:action>
            </x-section-header>

            <template x-if="subtasks.length === 0">
                <p class="text-sm text-gray-500 mb-4">
                    Подцелей пока нет. Добавьте первую — это разбивает крупную цель на
                    управляемые шаги.
                </p>
            </template>

            <div class="space-y-4">
                <template x-for="subtask in subtasks" :key="subtask.id">
                    <div class="border border-gray-100 rounded-lg p-4">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-2 flex-1">
                                <h3 x-show="!subtask.editing"
                                    @dblclick="startEditSubtask(subtask)"
                                    class="font-medium text-gray-900 cursor-pointer"
                                    x-text="subtask.title"
                                    title="Двойной клик — изменить"></h3>

                                <form x-show="subtask.editing"
                                      @submit.prevent="saveSubtask(subtask)"
                                      class="flex items-center gap-2 flex-1">
                                    <input type="text"
                                           x-model="subtask.editTitle"
                                           x-init="$nextTick(() => $el.focus())"
                                           @keydown.escape="subtask.editing = false"
                                           maxlength="200"
                                           required
                                           class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-1 border text-sm">
                                    <button type="submit"
                                            class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-lg">
                                        OK
                                    </button>
                                    <button type="button"
                                            @click="subtask.editing = false"
                                            class="text-xs text-gray-500 hover:text-gray-700">
                                        ×
                                    </button>
                                </form>
                            </div>

                            <button x-show="!subtask.editing"
                                    @click="deleteSubtask(subtask)"
                                    class="flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 transition flex-shrink-0"
                                    title="Удалить подцель со всеми задачами">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4"/>
                                </svg>
                            </button>
                        </div>

                        <ul class="space-y-1 mb-3">
                            <template x-for="task in subtask.tasks" :key="task.id">
                                <li class="flex items-center gap-2 group">
                                    <input type="checkbox"
                                           :checked="task.is_done"
                                           x-show="!task.editing"
                                           @change="toggleTask(task)"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span x-show="!task.editing"
                                          class="flex-1 text-sm cursor-pointer"
                                          :class="task.is_done ? 'line-through text-gray-400' : 'text-gray-700'"
                                          @dblclick="startEditTask(task)"
                                          x-text="task.title"
                                          title="Двойной клик — изменить"></span>
                                    <form x-show="task.editing"
                                          @submit.prevent="saveTask(task)"
                                          class="flex items-center gap-2 flex-1">
                                        <input type="text"
                                               x-model="task.editTitle"
                                               x-effect="task.editing && $nextTick(() => $el.focus())"
                                               @keydown.escape="task.editing = false"
                                               maxlength="200"
                                               required
                                               class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-1 border text-sm">
                                        <button type="submit"
                                                class="text-xs bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1 rounded-lg">
                                            OK
                                        </button>
                                        <button type="button"
                                                @click="task.editing = false"
                                                class="text-xs text-gray-500 hover:text-gray-700">
                                            ×
                                        </button>
                                    </form>
                                    <button x-show="!task.editing"
                                            @click="deleteTask(subtask, task)"
                                            class="flex items-center justify-center w-7 h-7 rounded-md text-gray-300 hover:text-red-500 hover:bg-red-50 transition flex-shrink-0"
                                            title="Удалить задачу">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5-4h4m-4 0a1 1 0 00-1 1v1h6V4a1 1 0 00-1-1m-4 0h4"/>
                                        </svg>
                                    </button>
                                </li>
                            </template>
                        </ul>

                        <form @submit.prevent="addTask(subtask)" class="flex flex-wrap items-center gap-2">
                            <input type="text"
                                   x-model="subtask.newTaskTitle"
                                   placeholder="Напишите задачу"
                                   maxlength="200"
                                   class="flex-1 min-w-0 rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-1.5 border text-sm">
                            <button type="submit"
                                    :disabled="!subtask.newTaskTitle?.trim()"
                                    class="flex items-center gap-1.5 text-xs bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 px-3 py-1.5 rounded-lg whitespace-nowrap">
                                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                                </svg>
                                <!-- Добавить задачу -->
                            </button>
                        </form>
                    </div>
                </template>
            </div>

            <form @submit.prevent="addSubtask()" class="flex flex-wrap items-center gap-2 mt-4 pt-4 border-t border-gray-100">
                <input type="text"
                       x-model="newSubtaskTitle"
                       placeholder="Добавьте подцель"
                       maxlength="200"
                       class="flex-1 min-w-0 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border text-sm">
                <button type="submit"
                        :disabled="!newSubtaskTitle?.trim()"
                        class="flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg whitespace-nowrap">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    <!-- Добавить подцель -->
                </button>
            </form>

            <div x-show="error"
                 x-transition
                 class="mt-3 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm"
                 x-text="error"></div>
        </x-card>

        </div>{{-- /space-y-6 cards wrapper --}}
    </div>
@endsection

@push('scripts')
<script>
function goalView(goalId, initialProgress, color, initialSubtasks) {
    return {
        goalId,
        color,
        progress: initialProgress,
        subtasks: initialSubtasks.map(s => ({
            ...s,
            editing: false,
            editTitle: s.title,
            newTaskTitle: '',
            tasks: s.tasks.map(t => ({ ...t, editing: false, editTitle: t.title })),
        })),
        newSubtaskTitle: '',
        error: '',
        toasts: [],
        nextToastId: 1,

        get totalTasks() {
            return this.subtasks.reduce((sum, s) => sum + s.tasks.length, 0);
        },
        get totalDone() {
            return this.subtasks.reduce((sum, s) => sum + s.tasks.filter(t => t.is_done).length, 0);
        },

        async req(url, options = {}) {
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const res = await fetch(url, {
                ...options,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                    'X-Requested-With': 'XMLHttpRequest',
                    ...(options.headers || {}),
                },
                credentials: 'same-origin',
            });
            if (!res.ok) {
                let msg = 'Ошибка запроса.';
                try {
                    const data = await res.json();
                    msg = data.message || (data.errors && Object.values(data.errors).flat().join(' ')) || msg;
                } catch (e) { /* ignore */ }
                throw new Error(msg);
            }
            return res.json();
        },

        recomputeProgress() {
            const total = this.totalTasks;
            this.progress = total === 0 ? 0 : Math.round(this.totalDone / total * 100);
        },

        async addSubtask() {
            const title = this.newSubtaskTitle.trim();
            if (!title) return;
            try {
                const data = await this.req(`/goals/${this.goalId}/subtasks`, {
                    method: 'POST',
                    body: JSON.stringify({ title }),
                });
                this.subtasks.push({
                    id: data.id,
                    title: data.title,
                    tasks: [],
                    editing: false,
                    editTitle: data.title,
                    newTaskTitle: '',
                });
                this.newSubtaskTitle = '';
                this.error = '';
            } catch (e) { this.error = e.message; }
        },

        startEditSubtask(subtask) {
            subtask.editTitle = subtask.title;
            subtask.editing = true;
        },

        async saveSubtask(subtask) {
            const title = subtask.editTitle.trim();
            if (!title) return;
            try {
                const data = await this.req(`/subtasks/${subtask.id}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ title }),
                });
                subtask.title = data.title;
                subtask.editing = false;
                this.error = '';
            } catch (e) { this.error = e.message; }
        },

        async deleteSubtask(subtask) {
            if (!confirm(`Удалить подцель «${subtask.title}» со всеми задачами?`)) return;
            try {
                await this.req(`/subtasks/${subtask.id}`, { method: 'DELETE' });
                this.subtasks = this.subtasks.filter(s => s.id !== subtask.id);
                this.recomputeProgress();
                this.error = '';
            } catch (e) { this.error = e.message; }
        },

        async addTask(subtask) {
            const title = subtask.newTaskTitle.trim();
            if (!title) return;
            try {
                const data = await this.req(`/subtasks/${subtask.id}/tasks`, {
                    method: 'POST',
                    body: JSON.stringify({ title }),
                });
                subtask.tasks.push({ id: data.id, title: data.title, is_done: data.is_done, editing: false, editTitle: data.title });
                subtask.newTaskTitle = '';
                this.recomputeProgress();
                this.error = '';
            } catch (e) { this.error = e.message; }
        },

        async toggleTask(task) {
            try {
                const data = await this.req(`/tasks/${task.id}/toggle`, { method: 'POST' });
                task.is_done = data.is_done;
                this.progress = data.goal_progress;
                this.error = '';

                if (Array.isArray(data.unlocked_achievements) && data.unlocked_achievements.length > 0) {
                    data.unlocked_achievements.forEach(a => this.showAchievementToast(a));
                }
            } catch (e) { this.error = e.message; }
        },

        showAchievementToast(achievement) {
            const id = this.nextToastId++;
            this.toasts.push({ id, name: achievement.name, icon: achievement.icon });
            setTimeout(() => this.dismissToast(id), 6000);
        },

        dismissToast(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },

        startEditTask(task) {
            task.editTitle = task.title;
            task.editing = true;
        },

        async saveTask(task) {
            const title = task.editTitle.trim();
            if (!title) return;
            try {
                const data = await this.req(`/tasks/${task.id}`, {
                    method: 'PATCH',
                    body: JSON.stringify({ title }),
                });
                task.title = data.title;
                task.editing = false;
                this.error = '';
            } catch (e) { this.error = e.message; }
        },

        async deleteTask(subtask, task) {
            try {
                await this.req(`/tasks/${task.id}`, { method: 'DELETE' });
                subtask.tasks = subtask.tasks.filter(t => t.id !== task.id);
                this.recomputeProgress();
                this.error = '';
            } catch (e) { this.error = e.message; }
        },
    };
}
</script>
@endpush
