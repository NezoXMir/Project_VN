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
    <div class="flex items-center justify-between mb-6">
        <a href="{{ route('goals.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
            ← К списку
        </a>

        <div class="flex items-center gap-2">
            @if ($goal->status === 'active')
                <a href="{{ route('goals.edit', $goal->id) }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                    Редактировать
                </a>
                <form method="POST" action="{{ route('goals.complete', $goal->id) }}">
                    @csrf
                    <button type="submit"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg text-sm">
                        Завершить
                    </button>
                </form>
                <form method="POST" action="{{ route('goals.archive', $goal->id) }}">
                    @csrf
                    <button type="submit"
                            class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm">
                        В архив
                    </button>
                </form>
            @endif

            <form method="POST" action="{{ route('goals.destroy', $goal->id) }}"
                  onsubmit="return confirm('Удалить цель безвозвратно?');">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm">
                    Удалить
                </button>
            </form>
        </div>
    </div>

    <x-flash-message type="success" />

    <div x-data="goalView({{ $goal->id }}, {{ $progress }}, '{{ $catColor }}', @js($subtasksJson))" class="space-y-6">

        {{-- Toasts разблокированных достижений --}}
        <div class="fixed top-4 right-4 z-50 space-y-2 max-w-sm" style="pointer-events: none;">
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


        <x-card padding="p-6" :accent="$catColor">
            <div class="flex items-start gap-3 mb-4">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-2">
                        <x-badge :color="$catColor">{{ $catLabel }}</x-badge>
                        <x-badge :tone="$statusTone">{{ $statusLabel }}</x-badge>
                    </div>
                    <h1 class="text-2xl font-bold">{{ $goal->title }}</h1>
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
                                    class="text-xs text-red-600 hover:text-red-700 ml-2"
                                    title="Удалить подцель со всеми задачами">
                                удалить
                            </button>
                        </div>

                        <ul class="space-y-1 mb-3">
                            <template x-for="task in subtask.tasks" :key="task.id">
                                <li class="flex items-center gap-2 group">
                                    <input type="checkbox"
                                           :checked="task.is_done"
                                           @change="toggleTask(task)"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="flex-1 text-sm"
                                          :class="task.is_done ? 'line-through text-gray-400' : 'text-gray-700'"
                                          x-text="task.title"></span>
                                    <button @click="deleteTask(subtask, task)"
                                            class="opacity-0 group-hover:opacity-100 text-xs text-red-500 hover:text-red-700 transition">
                                        ×
                                    </button>
                                </li>
                            </template>
                        </ul>

                        <form @submit.prevent="addTask(subtask)" class="flex items-center gap-2">
                            <input type="text"
                                   x-model="subtask.newTaskTitle"
                                   placeholder="+ задача"
                                   maxlength="200"
                                   class="flex-1 rounded-lg border-gray-200 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-1 border text-sm">
                            <button type="submit"
                                    :disabled="!subtask.newTaskTitle?.trim()"
                                    class="text-xs bg-gray-100 hover:bg-gray-200 disabled:opacity-50 disabled:cursor-not-allowed text-gray-700 px-3 py-1 rounded-lg">
                                добавить
                            </button>
                        </form>
                    </div>
                </template>
            </div>

            <form @submit.prevent="addSubtask()" class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100">
                <input type="text"
                       x-model="newSubtaskTitle"
                       placeholder="+ подцель"
                       maxlength="200"
                       class="flex-1 rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border text-sm">
                <button type="submit"
                        :disabled="!newSubtaskTitle?.trim()"
                        class="text-sm bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed text-white px-4 py-2 rounded-lg">
                    Добавить
                </button>
            </form>

            <div x-show="error"
                 x-transition
                 class="mt-3 rounded-lg bg-red-50 border border-red-200 text-red-700 px-4 py-2 text-sm"
                 x-text="error"></div>
        </x-card>
    </div>
@endsection

@push('scripts')
<script>
function goalView(goalId, initialProgress, color, initialSubtasks) {
    return {
        goalId,
        color,
        progress: initialProgress,
        subtasks: initialSubtasks.map(s => ({ ...s, editing: false, editTitle: s.title, newTaskTitle: '' })),
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
                subtask.tasks.push({ id: data.id, title: data.title, is_done: data.is_done });
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
