# Этап 05 — Подцели и задачи (трёхуровневая иерархия) с AJAX

## Цель

Реализовать средний и нижний уровень иерархии «Цель → Подцель →
Задача». Подцели разбивают крупную цель на управляемые блоки,
задачи — конкретные шаги, которые отмечаются как выполненные
чекбоксом. Прогресс цели считается по доле выполненных задач:
`round(done / total * 100)`. Все мутации (создание / переименование /
удаление подцелей и задач, переключение чекбокса) выполняются
без перезагрузки страницы — через JSON-эндпоинты + Alpine.js
для управления локальным состоянием.

## Выполненные действия

### A. Схема БД

1. Миграция
   `database/migrations/2026_05_08_161618_create_subtasks_table.php`:
   `id`, `goal_id` FK с `cascadeOnDelete`, `title (string 200)`,
   `position (unsignedInteger default 0)`, `timestamps()`.
   Индексы: `goal_id`, `(goal_id, position)`.
2. Миграция
   `database/migrations/2026_05_08_161619_create_tasks_table.php`:
   `id`, `subtask_id` FK с `cascadeOnDelete`,
   `title (string 200)`, `is_done (bool default false)`,
   `completed_at (timestamp nullable)`,
   `position (unsignedInteger default 0)`, `timestamps()`.
   Индексы: `subtask_id`, `(subtask_id, position)`,
   `(subtask_id, is_done)`. Применены через `php artisan migrate`.

### B. Модели

3. Создан `app/Models/Subtask.php`:
   - `$fillable`: `goal_id`, `title`, `position`.
   - Связи: `goal(): BelongsTo`,
     `tasks(): HasMany` с `orderBy('position')`.
4. Создан `app/Models/Task.php`:
   - `$fillable`: `subtask_id`, `title`, `is_done`,
     `completed_at`, `position`.
   - Касты: `is_done → bool`, `completed_at → datetime`.
   - Связь: `subtask(): BelongsTo`.
   - Скоуп: `done()` — фильтр по `is_done = true`.
5. Снят временный гард в `app/Models/Goal.php::progress()` —
   проверка `Schema::hasTable('tasks')` больше не нужна
   (см. Notes #1). Аксессор теперь:
   `$this->subtasks->flatMap->tasks` → подсчёт total и done →
   процент. На пустой иерархии возвращает 0.

### C. Сервисы

6. Создан `app/Services/SubtaskService.php`:
   - `create(int $goalId, int $userId, array $data): Subtask` —
     находит цель через приватный `findOwnedGoal()` (404/403),
     вычисляет `position = max(position) + 1` для аппенда в конец.
   - `update(int $subtaskId, int $userId, array $data): Subtask` —
     меняет только `title`.
   - `delete(int $subtaskId, int $userId): void` — каскад на
     задачи через FK CASCADE.
   - Публичный `findOwned()` — eager-load `goal`, проверка
     `goal->user_id` (используется и снаружи, и внутри).
7. Создан `app/Services/TaskService.php`:
   - `create(int $subtaskId, int $userId, array $data): Task` —
     через `findOwnedSubtask()`, position = max + 1.
   - `update(int $taskId, int $userId, array $data): Task` —
     меняет `title`.
   - `toggle(int $taskId, int $userId): Task` — флипает `is_done`,
     синхронно проставляет/обнуляет `completed_at`. Возвращает
     обновлённую модель.
   - `delete(int $taskId, int $userId): void`.
   - Публичный `findOwned()` — eager-load `subtask.goal` и
     проверка `subtask->goal->user_id`. Цепочка ownership
     `Task → Subtask → Goal → User` через одну загрузку.

### D. Form Requests

8. Создан `app/Http/Requests/SubtaskRequest.php` —
   `title required string max:200`, сообщения и атрибуты на
   русском.
9. Создан `app/Http/Requests/TaskRequest.php` — аналогично, с
   формулировкой про задачу.

### E. Контроллеры (JSON-only)

10. Создан `app/Http/Controllers/SubtaskController.php`:
    `store(SubtaskRequest, int $goal)`,
    `update(SubtaskRequest, int $subtask)`,
    `destroy(int $subtask)`. Все возвращают
    `Illuminate\Http\JsonResponse`. `store` отдаёт полный
    объект подцели с пустым массивом `tasks` для удобной
    интеграции в Alpine state.
11. Создан `app/Http/Controllers/TaskController.php`:
    `store`, `update`, `toggle`, `destroy`. Toggle —
    отдельный `POST /tasks/{task}/toggle` (это «действие»,
    а не идемпотентный PATCH). В ответе `toggle` — кроме
    обновлённого состояния задачи, ещё и `goal_progress` —
    свежий процент цели, чтобы фронт мог обновить
    прогресс-бар без второго запроса.

### F. Маршруты

12. Обновлён `routes/web.php`: 7 новых маршрутов под
    `middleware('auth')`:
    - `POST   /goals/{goal}/subtasks` → `subtasks.store`
    - `PATCH  /subtasks/{subtask}` → `subtasks.update`
    - `DELETE /subtasks/{subtask}` → `subtasks.destroy`
    - `POST   /subtasks/{subtask}/tasks` → `tasks.store`
    - `PATCH  /tasks/{task}` → `tasks.update`
    - `POST   /tasks/{task}/toggle` → `tasks.toggle`
    - `DELETE /tasks/{task}` → `tasks.destroy`
    Все с `whereNumber()` на параметрах.

### G. Eager loading

13. `app/Services/GoalService.php::listForUser()` —
    восстановлен `with(['category', 'subtasks.tasks'])` (был
    отложен до Этапа 05 коммитом `bd47aaf`). Без этого на
    `/goals` каждая карточка триггерила бы N+1 при чтении
    `progress`.
14. `app/Services/GoalService.php::findOwned()` — расширен:
    подцели грузятся отсортированные по `position`, у каждой
    подгружаются задачи. Обеспечивает корректный начальный
    `progress` и серверный snapshot для Alpine.

### H. Фронтенд (`goals/show.blade.php`)

15. Полностью переписан: заглушка «Подцели и задачи появятся
    в Этапе 05» удалена, на её месте Alpine-компонент
    `goalView(goalId, progress, color, subtasks)`.
16. Серверный JSON-snapshot собирается в `@php` блоке наверху
    (`$subtasksJson` — массив с `id`, `title`, массивом `tasks`),
    затем передаётся в Alpine через `@js($subtasksJson)`.
    На каждый item Alpine добавляет UI-поля `editing`,
    `editTitle`, `newTaskTitle` (см. Notes #3).
17. Реализованы UI-сценарии:
    - **Добавить подцель** — форма внизу, `+ подцель` →
      POST → пуш в `subtasks[]`.
    - **Переименовать подцель** — двойной клик по заголовку,
      инлайн-input с `Esc` для отмены, PATCH на submit.
    - **Удалить подцель** — кнопка «удалить» с `confirm()`,
      DELETE → фильтр массива, пересчёт прогресса.
    - **Добавить задачу** — форма внутри подцели, кнопка
      `disabled` при пустом `title`, POST → пуш в
      `subtask.tasks[]`.
    - **Toggle задачи** — нативный `<input type="checkbox">`
      с `@change`, POST → обновление `is_done` локально +
      `progress` из ответа сервера.
    - **Удалить задачу** — кнопка `×` с `opacity-0
      group-hover:opacity-100` (видна по hover), DELETE →
      фильтр массива.
18. Прогресс-бар цели в верхней карточке привязан реактивно:
    `:style="\`width: ${progress}%; background-color: ${color}\`"`
    + transition на 300ms — плавная анимация при toggle.
19. Геттеры `totalTasks` / `totalDone` — для подзаголовка
    «N из M задач», обновляются автоматически при изменении
    массива.
20. Хелпер `req(url, options)` — единая обёртка над `fetch`:
    добавляет CSRF (из `<meta>`), `Accept: application/json`,
    `X-Requested-With`, `credentials: same-origin`. На non-2xx
    вытаскивает `errors[*][0]` или `message` из JSON и бросает
    `Error`. Все методы ловят `e.message` в `this.error` —
    показывается под формой подцели.
21. Подключение скрипта — через `@push('scripts')`, который
    layout уже поддерживает (`@stack('scripts')` в
    `layouts/app.blade.php`).

## Созданные файлы

- `app/Http/Controllers/SubtaskController.php`
- `app/Http/Controllers/TaskController.php`
- `app/Http/Requests/SubtaskRequest.php`
- `app/Http/Requests/TaskRequest.php`
- `app/Models/Subtask.php`
- `app/Models/Task.php`
- `app/Services/SubtaskService.php`
- `app/Services/TaskService.php`
- `database/migrations/2026_05_08_161618_create_subtasks_table.php`
- `database/migrations/2026_05_08_161619_create_tasks_table.php`
- `docs/stage-05-log.md` (этот файл)

## Изменённые файлы

- `app/Models/Goal.php` — снят гард `Schema::hasTable('tasks')`
  в `progress()`, аксессор работает напрямую через subtasks.tasks.
- `app/Services/GoalService.php` — `listForUser()` и `findOwned()`
  теперь делают eager `with(['subtasks.tasks'])`; в `findOwned`
  подцели сортируются по `position`.
- `resources/views/goals/show.blade.php` — полностью переписан
  блок подцелей/задач (Alpine + AJAX вместо заглушки).
- `routes/web.php` — добавлены 7 маршрутов для subtasks/tasks,
  импорты контроллеров.
- `README.md` — отмечен Этап 05 как выполненный.

## Краткое описание ключевых решений

**JSON-эндпоинты, не HTML-фрагменты.** Альтернатива была —
возвращать с сервера готовый Blade-кусок и вставлять через
`innerHTML`. Минусы: серверный шаблон фрагмента живёт отдельно
от основного, дублирование разметки, ручная сборка строк.
JSON + Alpine-template более «реактивен»: бэкенд один раз
описал контракт, фронт сам решает, как рендерить. Меньше
файлов и меньше синхронизации.

**Toggle — POST, не PATCH.** Семантически toggle — действие
с побочным эффектом, а не «обновить ресурс до известного
состояния». PATCH должен быть идемпотентным («task.is_done
= true»), а наш toggle — флип, два вызова дают разный
результат. POST на отдельный action-endpoint точнее по
семантике + уменьшает риск, что прокси/CDN решат
«я уже делал PATCH с тем же телом, второй раз не пойду».

**`completed_at` — отдельное поле, а не дериват от `is_done`.**
На вид избыточно (если `is_done = true`, то «когда-то
выставили в true»). Но без него теряется информация о
**времени** завершения — критичная для дашборда Этапа 06
(серии подряд, активность за 30 дней) и для рекомендательной
системы Этапа 07 («давно не закрывал задачи»). Дешевле
писать сразу, чем потом ретроспективно восстанавливать из
`updated_at` (который меняется при любом UPDATE).

**`position` — `unsignedInteger` с дефолтом 0, аппенд через
`max(position) + 1`.** Простейшая стратегия: при создании
смотрим максимум, прибавляем единицу. На больших списках
это два запроса (max + insert), но для подцелей цели и задач
подцели реальные размеры — десятки максимум. Альтернативы
(GUID-time-based, lexorank) — оверинжиниринг для дипломного
проекта. DnD-сортировка добавится в Этап 10, тогда же будет
endpoint для batch-update `position`.

**Прогресс возвращается прямо из toggle-ответа.** Простейший
путь: фронт мог бы пересчитать сам, как делает
`recomputeProgress()` для add/delete. Но toggle — самая
частая операция, и хочется иметь для неё **серверную истину**:
если будут гонки (два таба, два устройства), сервер всегда
покажет правильный процент, и UI синхронизуется при первом
же toggle. Для add/delete этого нет — там фронт пересчитывает
локально, потому что сервер всё равно не знает «сколько
было до».

**Eager-load `subtasks.tasks` в `listForUser()`.** Если этого
не сделать, на `/goals` для каждой карточки `progress`
триггерит lazy-load → N+1 при списке из десятка целей. С
eager-load — один запрос на subtasks (`WHERE goal_id IN (...)`)
+ один на tasks (`WHERE subtask_id IN (...)`). Стоимость —
один JOIN-paragraph в коде сервиса, выгода — линейный
рост вместо квадратичного.

**`@push('scripts')` для inline-скрипта вместо отдельного
`/public/js/goals-show.js`.** У нас всего один компонент,
inline-скрипт виден прямо рядом с разметкой и читается
вместе с шаблоном. Когда логики на странице станет 200+
строк или скрипт начнёт переиспользоваться — выделим
в отдельный файл (`/public/js/goal-view.js`). Сейчас один
файл, одна ответственность, никаких build-шагов.

**`completed_at` → `toIso8601String()` в JSON.** Стандартный
формат, парсится `new Date(...)` в JS из коробки и
сравнивается лексикографически (важно для будущей сортировки
«последние выполненные» в дашборде).

## Использованные команды

```bash
# Миграции
php artisan make:migration create_subtasks_table
php artisan make:migration create_tasks_table
# (тела миграций — вручную через Write, Laravel-генератор
#  создаёт пустые up()/down())

php artisan migrate
php artisan route:list --path=subtask    # проверка
php artisan route:list --path=task

# Smoke
php artisan serve --port=8000
# далее — curl-сценарии (см. ниже) + ручное тестирование в браузере
```

## Smoke-проверки (выполнены через curl)

```bash
# 1. POST /goals/{id}/subtasks с пустым title → 422 + RU-сообщение
# 2. POST с валидным title → 201 + JSON {id, goal_id, title, position, tasks: []}
# 3. POST /subtasks/{id}/tasks ×3 → 201 каждая, position 1..3
# 4. POST /tasks/{id}/toggle → 200, is_done=true, completed_at, goal_progress=33%
# 5. POST на несуществующий task → 404 (404, не 403 — utilisate enumeration)
# 6. GET /goals/{id} с подцелями/задачами → 200, рендерится без ошибок
# 7. Прямая БД-проверка: progress на 1/3 done = 33% (соответствует JSON-ответу)
```

## Как поднять и проверить

```bash
php artisan migrate    # применит миграции subtasks + tasks
php artisan serve      # http://localhost:8000
# Логин под user@example.com / password
# Создать цель → открыть её → попробовать сценарии ниже
```

Smoke-чеклист в браузере:

- [ ] На `/goals/{id}` блок «Подцели и задачи» отображается
      пустым, кнопка «Добавить» в форме `+ подцель` disabled
      пока поле пустое.
- [ ] Добавить подцель «Подготовка» → появляется в списке,
      ниже неё форма `+ задача`.
- [ ] Двойной клик по заголовку подцели → инлайн-редактор
      с фокусом, Esc отменяет, OK сохраняет.
- [ ] Добавить 3 задачи → появляются под чекбоксами,
      `0 из 3 задач` в подзаголовке.
- [ ] Поставить чекбокс на одной → текст зачёркивается,
      `1 из 3 задач`, прогресс-бар цели плавно становится
      33%.
- [ ] Снять чекбокс → текст возвращается, прогресс 0%.
- [ ] Hover на задаче → справа появляется `×`, клик удаляет.
- [ ] Кнопка «удалить» рядом с подцелью → confirm → подцель
      исчезает со всеми задачами, прогресс пересчитывается.
- [ ] DevTools → попробовать `fetch('/tasks/99999/toggle',
      { method: 'POST', headers: { 'X-CSRF-TOKEN': ...,
      'X-Requested-With': 'XMLHttpRequest' } })` → 404.
- [ ] Открыть `/goals` → у целей с задачами прогресс-бар
      ненулевой (был всегда 0% до Этапа 05).

## Notes (нетривиальные обоснования)

1. **Почему гард `Schema::hasTable('tasks')` снят, а не
   просто оставлен «работать вхолостую».** Гард был
   временным костылём из Этапа 04 (Notes #1 в `stage-04-log.md`):
   модели `Subtask`/`Task` ещё не существовали, а relation
   `hasMany(Subtask::class)` была объявлена заранее. Без
   гарда `progress()` бы упал на `class not found` при любом
   обращении. Теперь модели есть, таблицы есть — гард не
   просто бесполезен, а *вреден*: лишний `Schema::hasTable`
   запрос на каждый рендер цели. Снятие — правильное
   завершение «лесов» из предыдущего этапа.

2. **Почему ownership проверяется через цепочку
   `Task → Subtask → Goal → User`, а не дублирующим
   `user_id` на `tasks`.** Денормализация `user_id` на
   tasks/subtasks ускорила бы проверку (один WHERE вместо
   JOIN), но создала бы инвариант, который надо поддерживать
   везде: при `Goal::transferToUser` (в нашем проекте такого
   нет, но), при импорте, при ручном UPDATE. Один источник
   истины (`goals.user_id`) проще и безошибочнее. Стоимость
   одного JOIN — наносекунды на индексированной FK.

3. **Почему UI-поля (`editing`, `editTitle`, `newTaskTitle`)
   живут на самом subtask-объекте в Alpine state, а не в
   отдельном `uiState[subtaskId]` мапе.** Альтернатива
   (мапа) — «чище» с точки зрения разделения данных и UI,
   но требует ручной синхронизации: добавил подцель — добавь
   её в `uiState`, удалил — удали оттуда же. С полями прямо
   на объекте всё автоматически: при добавлении в массив
   уже есть `editing: false`, при удалении вместе с объектом
   уходит и его UI-состояние. Минимум boilerplate, минимум
   способов ошибиться. Серверный JSON эти поля игнорирует
   (бэкенд их и не присылает), при ре-серверном рендере
   они инициализируются заново.

4. **Почему `await this.req()` в обработчиках, а не Promise
   chain или isolated `.catch()`.** `try/catch` вокруг
   `await` даёт линейный поток управления: поставил
   ошибку в `this.error`, прервал. С `.then().catch()`
   обработчик ошибки уезжает на 5-10 строк ниже, и
   читателю нужно соединять связь визуально. На таких
   небольших методах разницы в производительности нет,
   читаемость — единственный критерий.

5. **Почему toggle возвращает `goal_progress` из сервера,
   а add/delete — нет (фронт пересчитывает сам).**
   Toggle — самое частое действие, и «истина прогресса»
   там критична: при гонке двух табов или потери пакета
   локальный пересчёт может разъехаться с реальностью.
   Add/delete делаются реже, и фронт там точно знает
   изменение (добавил один объект → +1 к total, удалил
   завершённый → -1 к total и -1 к done). Запрашивать
   сервер ради того же ответа — лишний round-trip.
   Если будут гонки — toggle всё «починит».

6. **Почему «Подцели пока нет» — `<template x-if="...">`,
   а не `@if (count($subtasksJson) === 0)` в Blade.**
   Серверный `@if` отрисует empty-state на момент загрузки,
   но после первого AJAX-добавления подцели Blade-кусок
   останется висеть рядом. `x-if` реактивно — сам прячется
   при `subtasks.length > 0` и появляется обратно при
   удалении последней подцели.

7. **Почему `position` есть в схеме, но интерфейса
   сортировки нет (DnD).** Поле — это инвестиция в
   Этап 10 (UI Polish), где будет drag-and-drop. Сейчас
   `position` обеспечивает стабильную сортировку
   (`orderBy('position')` в relation `Subtask::tasks`):
   без него Eloquent вернул бы «как лежит в БД» — порядок
   непредсказуем. Аппенд через `max + 1` гарантирует, что
   новые элементы появляются в конце списка, что и ожидает
   пользователь.

8. **Почему `cascadeOnDelete` на обоих FK
   (`goal_id → goals`, `subtask_id → subtasks`).** Иерархия
   композитная: задача без подцели бессмысленна, подцель
   без цели — тоже. RESTRICT блокировал бы удаление цели
   с подцелями (плохой UX, заставляет пользователя
   зачищать снизу вверх). CASCADE даёт ожидаемое
   поведение: удалил цель — пропали все её подцели и
   задачи. Архивацию (вместо удаления) пользователь
   делает явно через «В архив» на цели, см. Этап 04.
