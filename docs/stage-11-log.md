# Этап 11 — Рефакторинг: репозитории, политики, хелперы

## Цель

Никакой новой функциональности — только улучшение архитектуры
накопившегося кода. После 10 этапов в проекте появились
паттерны-кандидаты на выделение:

- **Сервисы** мешали бизнес-логику с прямым `Eloquent` —
  сложно тестировать, неочевидны ответственности.
- **Авторизация** в каждом сервисе через `findOwned` →
  `abort(403)` — дублировалась, не использовала стандартный
  механизм Laravel.
- **Алгоритм streak** жил в трёх сервисах копи-пастом — риск
  рассинхрона при правках.

После этапа архитектура: **Controller → Service → Repository**
(трёхслойка), авторизация — через **Policy** + `Gate::authorize`,
расчёт streak — единственный класс `StreakCalculator`.

## Архитектурный сдвиг

```
ДО                                  ПОСЛЕ
═══════════════════════════════════════════════════════════
Controller                          Controller
  ↓                                   ↓ (Auth::user())
Service                             Service
  ↓ (Eloquent inline)                 ↓ (через DI)
Goal::query()->...->get()           Repository
                                      ↓
                                    Goal::query()->...->get()

Авторизация: abort(403) inline    Авторизация: Gate::authorize
                                  + Policy-классы
```

## Выполненные действия

### A. Repositories — выделение слоя данных

1. `app/Repositories/GoalRepository.php`:
   - `listForUser($userId)` — список с сортировкой по статусу
     и eager-load (`category`, `subtasks.tasks`)
   - `findById($id)` — без relations
   - `findWithRelations($id)` — с category + ordered subtasks +
     tasks (для `/goals/{id}` и любых мутаций)
   - `create($attrs)`, `save($model)`, `delete($model)`

2. `app/Repositories/CategoryRepository.php`:
   - `availableForUser($userId)` — системные + свои (через scope)
   - `ownedByUser($userId)`
   - `userColors($userId)` — для секции «Твои цвета»
   - `goalsCount($category)` — для проверки в `delete()`
   - стандартные `findById`, `create`, `save`, `delete`

3. `app/Repositories/SubtaskRepository.php` и
   `app/Repositories/TaskRepository.php`:
   - `findWithGoal($id)` / `findWithChain($id)` — для проверки
     ownership через цепочку
   - `nextPosition($parentId)` — для аппенда с `max+1`
   - стандартные `create`, `save`, `delete`

4. Все 4 сервиса (Goal/Category/Subtask/Task) переведены на
   репозитории — больше **никаких прямых обращений к моделям**
   через `::query()`, `::create()`, `::find()` в сервисах.

### B. Policies — централизация авторизации

5. Создано 4 policy-класса:
   - `app/Policies/GoalPolicy.php` — view/update/delete/archive/complete,
     все проверяют `user->id === goal->user_id`
   - `app/Policies/CategoryPolicy.php` — со спецслучаем
     `is_system → false` для update/delete
   - `app/Policies/SubtaskPolicy.php` — через цепочку
     `subtask->goal->user_id`
   - `app/Policies/TaskPolicy.php` — через
     `task->subtask->goal->user_id` + специальный `toggle`
6. Laravel 11 авто-регистрирует policy по naming convention
   (`App\Models\Goal` → `App\Policies\GoalPolicy`), `AuthServiceProvider`
   не нужен.
7. В сервисах `findOwned` заменено на `findAuthorized($id, $user, $ability)`,
   которое:
   - тянет модель через repo
   - 404 если нет
   - `Gate::forUser($user)->authorize($ability, $model)` для авторизации
   - возвращает модель
8. В контроллерах `(int) Auth::id()` заменено на `Auth::user()` там,
   где требуется передать в сервис для авторизации. Сигнатуры
   сервисных методов теперь принимают `User` вместо `int`.

### C. `@can` в Blade

9. `resources/views/categories/index.blade.php` — кнопки
   «Изменить» / «Удалить» обёрнуты в `@can('update', $cat)`.
   Раньше там был inline-условие `! $cat->is_system`, которое
   дублировало логику CategoryPolicy. Теперь правило живёт в
   одном месте — Policy решает.

### D. StreakCalculator — единый источник истины

10. Создан `app/Helpers/StreakCalculator.php` с одним публичным
    методом `compute(int $userId): int`. Алгоритм одинаковый
    с тем, что был в трёх сервисах: distinct DATE завершённых
    задач, walk-back от today (или yesterday если today пусто).
11. `StatsService`, `AchievementService`, `RecommendationService`
    теперь инжектят `StreakCalculator` и зовут `$this->streak->compute(...)`.
    **Удалены три копии алгоритма** (было ~15 строк × 3 = 45,
    стало одна реализация × 3 однострочника).

## Созданные файлы

- `app/Helpers/StreakCalculator.php`
- `app/Policies/GoalPolicy.php`
- `app/Policies/CategoryPolicy.php`
- `app/Policies/SubtaskPolicy.php`
- `app/Policies/TaskPolicy.php`
- `app/Repositories/GoalRepository.php`
- `app/Repositories/CategoryRepository.php`
- `app/Repositories/SubtaskRepository.php`
- `app/Repositories/TaskRepository.php`
- `docs/stage-11-log.md` (этот файл)

## Изменённые файлы

- `app/Services/GoalService.php` — инжект `GoalRepository`,
  методы принимают `User`, авторизация через `Gate`
- `app/Services/CategoryService.php` — то же + сохранён
  спец-кейс для системных категорий
- `app/Services/SubtaskService.php` — инжект двух репо
  (Subtask + Goal для проверки родителя)
- `app/Services/TaskService.php` — инжект двух репо
  (Task + Subtask для проверки родителя)
- `app/Services/StatsService.php` — инжект `StreakCalculator`,
  локальный `currentStreak()` удалён
- `app/Services/AchievementService.php` — то же
- `app/Services/RecommendationService.php` — то же
- `app/Http/Controllers/GoalController.php` — `Auth::user()`
  вместо `(int) Auth::id()` где нужно для авторизации
- `app/Http/Controllers/CategoryController.php` — то же
- `app/Http/Controllers/SubtaskController.php` — то же
- `app/Http/Controllers/TaskController.php` — то же
- `resources/views/categories/index.blade.php` —
  `@if (! $cat->is_system)` → `@can('update', $cat)`
- `README.md` — Этап 11 отмечен как выполненный

## Краткое описание ключевых решений

**Repository-слой инжектится через DI, не статические вызовы.**
Альтернатива — `GoalRepository::listForUser(...)` как статический
метод. Минусы: невозможно подменить в тестах, нет полиморфизма.
DI через `__construct` стандартен для Laravel и совместим
с auto-resolution контейнера.

**Сервисы НЕ обращаются к моделям напрямую.** Жёсткое правило:
если в сервисе видишь `Goal::find(...)`, `User::where(...)` —
это ошибка слоистости. Вся работа с БД только через repository.

**Авторизация в сервисе через `Gate::forUser($user)->authorize(...)`,
а не в контроллере через `$this->authorize()`.**

Мастер-промпт Этапа 11 предлагает контроллер-уровень
(`$this->authorize()`), но мы пошли service-уровнем по двум
причинам:
1. Сервис закрытый по умолчанию — нельзя случайно вызвать
   мутирующий метод без проверки. С контроллер-уровнем
   разработчик может забыть `authorize()` перед `service->update()`.
2. Контроллер часто не имеет модели на руках — приходится
   её сначала найти, потом проверить, потом передать в сервис
   — двойная работа.

   ```php
   // contoller-level (master prompt)
   public function show(int $id) {
       $goal = Goal::findOrFail($id);
       $this->authorize('view', $goal);
       return view('...', ['goal' => $goal->load([...])]);
   }

   // service-level (наш выбор)
   public function show(int $id) {
       return view('...', ['goal' => $this->goals->get($id, Auth::user())]);
   }
   ```

   Trade-off: контроллер-уровень более идиоматичен Laravel,
   service-уровень безопаснее по умолчанию.

**Policy-классы без `before()` и `before` для admin.** Иногда
в Laravel-приложениях `before(User $user) { return $user->isAdmin() ?: null; }`
позволяет админу обходить любые проверки. У нас этого **намеренно
нет**: README заявляет «Без доступа к персональным данным
пользователей». Админ не имеет доступ к чужим целям через бэкдор.
Если когда-нибудь появится legitimate-кейс (поддержка),
добавим явно.

**StreakCalculator — класс, не глобальная функция.** Можно было
бы написать `streak_for(int $userId): int` и положить в
`autoload.files`. Минус: глобальные функции тестировать сложнее
(невозможно мокнуть), они скрыты в namespace, IDE их не
автокомплитит как методы класса. Класс с одним публичным
методом — мини-оверхед, но единый стандарт работает в DI-контейнере.

**Не делали `Action`-классов и `DTO`.** Эти паттерны добавили бы
ещё один слой между Controller и Service, а Service у нас и так
тонкий. Action-pattern особенно полезен когда у вас 50+ use-cases —
у нас десяток, плюс группировка по сущности (`GoalService`,
`TaskService`) логичная.

## Использованные команды

```bash
# Все файлы созданы вручную через Write
# (артизан-генератор для policies/repos создаёт boilerplate)

# Smoke
php artisan serve
# далее curl-сценарии (см. ниже)
```

## Smoke-проверки (выполнены)

### Регрессия — все ключевые страницы 200

- [x] `/dashboard`, `/goals`, `/goals/{id}`, `/goals/{id}/edit`,
      `/goals/create`, `/achievements`, `/profile`,
      `/categories`, `/categories/create` — user
- [x] `/admin/dashboard`, `/admin/users` — admin

### Авторизация через Policy работает

- [x] User открывает свою цель → 200
- [x] User открывает чужую цель → 403 (GoalPolicy::view блокирует)
- [x] Admin удаляет чужую категорию → 403 (CategoryPolicy::delete
      блокирует — у нас НЕТ admin-override)
- [x] Создание категории через сервис → repo->create + auto unlock
      «Свой стиль» через AchievementService

### StreakCalculator работает

- [x] `app(StreakCalculator::class)->compute($userId)` возвращает
      число для существующего пользователя

## Notes (нетривиальные обоснования)

1. **Почему не используем route-model binding в контроллерах
   (`Goal $goal` вместо `int $goal`).** Route-model binding
   автоматически делает `Goal::findOrFail($id)`. Минус для нас —
   `findOrFail` не делает eager-load relations, которые нужны
   почти везде (для рендера progress, category badge, и т.д.).
   Через сервис мы контролируем что именно загружается.

2. **Почему `findAuthorized` приватный, а не публичный API
   репозитория.** Это бизнес-логика «найти своё/чужое + 403/404»,
   а не данные. Repository занимается только данными, без
   решений «можно/нельзя». Когда Service делает финальный шаг
   `Gate::authorize` — это бизнес-правило, оно не должно жить
   в репозитории.

3. **Почему `Gate::forUser($user)->authorize(...)` вместо
   `$user->can(...) ?: throw`.** Первый вариант кидает
   `AuthorizationException`, который Laravel автоматически
   конвертирует в HTTP 403 с правильным контекстом. Второй —
   возвращает bool, нужно вручную бросать exception. Gate API
   стандартный, лучше использовать его.

4. **Почему в `StatsService::dashboard` cначала был
   `currentStreak`, а сейчас вытащен в `StreakCalculator`.**
   До Этапа 11 мы считали что 15 строк дубликата ради
   независимости — приемлемая цена. Когда правил в Etapе 11
   было больше, дубликат увеличился (Recommendation, Achievement
   тоже считают streak). Три копии — точно повод выносить.
   До этого момента — нет, по правилу «преждевременная
   абстракция».

5. **Почему `@can('update', $cat)` именно в `categories/index`,
   а не везде где есть «своя» проверка.** На большинстве
   страниц `@can` всегда вернёт true (ты на своей странице
   со своими целями). Только в categories/index есть СМЕШАННЫЙ
   список — системные не редактируются, свои редактируются.
   `@can` там действительно решает.

6. **Что НЕ сделано из мастер-промпта (для honesty).**
   - `DateHelper::formatRu`, `daysLeft`, `streakLabel` —
     не добавлены. У нас уже есть `timeLeftLabel` (Этап 04 доп.),
     остальное опционально.
   - `ProgressHelper::progressColor()` — не сделан, цвет прогресса
     определяется по категории (через inline-style), не по
     процентам. Согласовано с UX-логикой проекта.
   - Регистрация policies в `AppServiceProvider` — не нужна
     в Laravel 11 (auto-discover).
   - `composer.json autoload.files` — не нужен, у нас
     namespace-helpers, не глобальные функции.
   - `migrate:fresh --seed` — не запускалось, чтобы не снести
     реальные данные пользователя. Миграции тестировались по
     отдельности при их добавлении.
