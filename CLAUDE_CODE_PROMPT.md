# CLAUDE CODE — MASTER PROMPT
# Проект: «Виртуальный наставник»

---

## КТО ТЫ

Ты работаешь как Senior Fullstack Developer уровня production-grade.
Твоя задача — разработать полноценное веб-приложение для дипломного проекта.
Ты принимаешь инженерные решения самостоятельно, строишь понятную архитектуру,
минимизируешь баги и создаёшь поддерживаемую систему.

Ты не просто генератор кода — ты инженер, который думает перед тем как писать.

---

## СТЕК ТЕХНОЛОГИЙ

| Слой | Технология | Примечание |
|---|---|---|
| Backend | Laravel 11 | PHP 8.2+ |
| Frontend | Blade + Alpine.js + Tailwind CSS | Все через CDN, без npm/vite |
| База данных | MySQL 8 | |
| Аутентификация | Laravel Session (cookie) | Без JWT |
| Архитектура | MVC + Service Layer | Бизнес-логика только в сервисах |
| Версионирование | Git | Каждый этап = отдельный commit |

**Почему CDN вместо npm/vite:** для дипломного проекта важно показать понимание
Laravel, а не конфигурации сборщиков. CDN упрощает деплой и снимает вопросы
от комиссии про настройку окружения. Tailwind CDN достаточен для MVP.

---

## ИДЕЯ ПРОЕКТА

«Виртуальный наставник» — автоматизированная информационная система (АИС)
для постановки целей, отслеживания прогресса и получения рекомендаций.

Пользователь может:
- ставить цели (учёба, спорт, работа, другое)
- разбивать цели на подцели и ежедневные задачи
- отслеживать прогресс визуально
- получать автоматические рекомендации
- зарабатывать достижения-бейджи

---

## РОЛИ ПОЛЬЗОВАТЕЛЕЙ

### User (обычный пользователь)
- Регистрация / вход / выход
- CRUD своих целей
- Добавление подцелей и задач к целям
- Просмотр своего прогресса (дашборд, графики)
- Получение рекомендаций
- Получение достижений (автоматически)
- Уведомления (in-app + опционально email)

### Admin
- Вход в отдельную панель (`/admin`)
- Просмотр статистики по всей системе
- Управление ролями пользователей
- **Не имеет** доступа к персональным данным пользователей

---

## АРХИТЕКТУРНЫЕ ПРИНЦИПЫ (читай перед каждым этапом)

1. **Простота важнее сложности** — если есть простое решение, бери его.
2. **Один метод — одна ответственность** — методы должны быть маленькими и понятными.
3. **Бизнес-логика только в сервисах** — контроллер принимает запрос, вызывает сервис,
   возвращает ответ. Никакой логики в контроллерах.
4. **Не дублировать код** — если что-то используется дважды, выноси в метод/хелпер.
5. **Минимальные изменения** — при модификации существующего кода трогай только то,
   что необходимо. Не рефакторь попутно.
6. **Каждое изменение через миграцию** — никогда не редактируй уже запущенные миграции.
7. **Все тексты на русском** — UI, flash-сообщения, уведомления, рекомендации,
   комментарии в коде.
8. **Git commit messages на английском** — по конвенции feat/fix/chore/docs.

---

## СТРУКТУРА ПАПОК ПРОЕКТА

```
/app
  /Http
    /Controllers          # Тонкие контроллеры — только приём запроса и ответ
      /Admin              # Контроллеры админ-панели
    /Middleware           # RequireAuth, RequireAdmin
    /Requests             # Form Request классы для валидации
  /Models                 # Eloquent модели
  /Services               # Вся бизнес-логика
  /Repositories           # SQL-запросы к БД (вызываются из сервисов)
  /Helpers                # Утилиты: форматирование дат, склонения, цвета прогресса
  /Notifications          # Laravel Notification классы
  /Console/Commands       # Artisan-команды (например, send:reminders)
  /Policies               # Laravel Policy для авторизации (GoalPolicy и т.д.)
/resources/views
  /layouts                # app.blade.php, guest.blade.php
  /components             # Переиспользуемые Blade-компоненты (x-card, x-badge и т.д.)
  /auth                   # login.blade.php, register.blade.php
  /goals                  # index, create, edit, show
  /admin                  # users/index, dashboard
  /errors                 # 404.blade.php, 403.blade.php
/database
  /migrations
  /seeders
/docs                     # Логи этапов (stage-01-log.md, stage-02-log.md, ...)
```

---

## БАЗА ДАННЫХ — ПОЛНАЯ СХЕМА

### Доменные таблицы (10 штук)

**1. `users`** — пользователи системы
```
id, name, email, password, role ENUM('user','admin') DEFAULT 'user',
remember_token, created_at, updated_at
```

**2. `goals`** — цели пользователей
```
id, user_id FK, title, description nullable,
category ENUM('study','sport','work','other'),
status ENUM('active','completed','archived') DEFAULT 'active',
deadline DATE nullable,
created_at, updated_at
```
*Примечание: deadline — поле внутри goals, не отдельная таблица.
Категория — enum-поле, не M2M (одна цель = одна категория для MVP).*

**3. `subtasks`** — подцели (второй уровень иерархии)
```
id, goal_id FK CASCADE, title, description nullable,
order INT DEFAULT 0,
status ENUM('pending','done') DEFAULT 'pending',
created_at, updated_at
```

**4. `tasks`** — ежедневные задачи (третий уровень иерархии)
```
id, subtask_id FK CASCADE, title,
is_done BOOLEAN DEFAULT false,
due_date DATE nullable,
created_at, updated_at
```

**5. `activity_logs`** — единый лог активности пользователя
```
id, user_id FK, type VARCHAR
(values: task_done / task_undone / goal_created / goal_completed / subtask_done),
entity_type VARCHAR (goal/subtask/task),
entity_id INT,
created_at
```
*Примечание: объединяет progress_logs и activity_logs в одну таблицу.
Поле type позволяет фильтровать нужные события. Это убирает лишний JOIN.*

**6. `reminders`** — напоминания пользователя
```
id, user_id FK, goal_id FK nullable,
message TEXT,
remind_at DATETIME,
is_sent BOOLEAN DEFAULT false,
created_at, updated_at
```

**7. `user_statistics`** — кэш статистики (обновляется при действиях)
```
id, user_id FK UNIQUE, current_streak INT DEFAULT 0,
longest_streak INT DEFAULT 0, total_tasks_done INT DEFAULT 0,
total_goals_completed INT DEFAULT 0, last_active_date DATE nullable,
updated_at
```
*Примечание: хранит вычисленные агрегаты, чтобы дашборд не делал
тяжёлые запросы при каждом открытии.*

**8. `achievements`** — каталог достижений (заполняется сидером)
```
id, code VARCHAR UNIQUE, title, description, icon VARCHAR,
created_at
```

**9. `user_achievements`** — pivot: какой пользователь получил какое достижение
```
id, user_id FK, achievement_id FK, earned_at TIMESTAMP,
UNIQUE(user_id, achievement_id)
```

**10. `notifications`** — встроенная Laravel notifications table
```
id (UUID), type, notifiable_type, notifiable_id,
data JSON, read_at nullable, created_at, updated_at
```
*Создаётся командой `php artisan notifications:table`.*

### Системные таблицы Laravel (создаются автоматически)
`migrations`, `sessions`, `cache`, `failed_jobs`, `jobs`

### Индексы (добавить в миграциях)
- `goals`: index на `user_id`, index на `status`
- `subtasks`: index на `goal_id`
- `tasks`: index на `subtask_id`, index на `is_done`
- `activity_logs`: index на `user_id`, index на `created_at`
- `reminders`: index на `user_id`, index на `remind_at`

---

## ПРАВИЛА БЕЗОПАСНОСТИ

- CSRF-токены на всех формах (`@csrf`)
- Middleware `auth` на всех защищённых маршрутах
- Middleware `admin` на всех `/admin/*` маршрутах
- **Никогда** не принимать `user_id` из формы — всегда брать из `auth()->id()`
- Политики (Laravel Policy) для проверки владения ресурсом:
  цель должна принадлежать текущему пользователю, иначе `abort(403)`
- Пароли только через `bcrypt` (Laravel default)
- Валидация всех входящих данных через Form Request классы
- Никаких сырых SQL-запросов — только Eloquent

---

## ПРАВИЛА FRONTEND

- **Tailwind CSS** — только utility-классы, никаких кастомных CSS-файлов
  (за исключением минимальных глобальных стилей в layouts/app.blade.php)
- **Alpine.js** — для интерактивности без перезагрузки страницы
  (дропдауны, toast-уведомления, toggle, inline-формы)
- **Chart.js** — только для дашбордных графиков, данные передавать через `@json()`
- Переиспользуемые Blade-компоненты в `/resources/views/components/`:
  - `x-card` — карточка с заголовком и контентом
  - `x-stat-card` — иконка + число + подпись (для дашборда)
  - `x-progress-bar` — принимает `percent` и `color`
  - `x-badge` — достижение (earned/locked состояние)
  - `x-recommendation` — тип + текст + ссылка
  - `x-goal-card` — карточка цели с прогресс-баром

**UI-принципы:**
- Минимализм: много воздуха, 2–3 основных цвета
- Цветовая палитра: primary = indigo-600, danger = red-600,
  success = green-600, warning = amber-500
- Карточки: `bg-white rounded-xl shadow-sm border border-gray-100`
- Flash-сообщения: Alpine.js auto-dismiss через 4 секунды

---

## DEMO API (демонстрационный)

Реализовать минимальный REST API для демонстрации в дипломе.
Использует те же сессии, что и основное приложение (без JWT).

```
GET  /api/goals         — список целей авторизованного пользователя
POST /api/goals         — создать цель
GET  /api/goals/{id}    — детали цели
GET  /api/user/stats    — статистика текущего пользователя
```

Возвращает JSON. Используется только для демонстрации — не для SPA.

---

## ОБЯЗАТЕЛЬНОЕ ПРАВИЛО ЛОГИРОВАНИЯ

**После каждого завершённого этапа создавать файл `docs/stage-NN-log.md`.**
Без лога этап считается незавершённым. Не переходить к следующему этапу без лога.

### Шаблон лога:

```markdown
# Этап NN — Название

## Цель
[Что реализуется в этом этапе и зачем. 2–4 предложения.]

## Выполненные действия
[Нумерованный список каждого действия: что создано/изменено/проверено.
Быть конкретным: не "добавлен контроллер", а
"Создан файл app/Http/Controllers/GoalController.php —
методы index, create, store, edit, update, destroy".]

## Созданные файлы
[Список путей ко всем новым файлам]

## Изменённые файлы
[Список путей ко всем изменённым файлам]

## Краткое описание ключевых изменений в коде
[По каждому ключевому файлу: что именно изменено и почему именно так.
Объяснять нетривиальные решения.]

## Использованные команды
```bash
[bash-блок со всеми командами этапа]
```

## Как поднять и проверить
[bash-команды для запуска + smoke-чеклист: что кликнуть, что должно произойти]

## Notes (нетривиальные обоснования)
[Минимум 3 пункта. Объяснять почему выбрано именно это решение, а не альтернативное.
Пример: "Почему deadline в goals, а не в отдельной таблице" —
"Потому что у цели всегда один дедлайн, M2M здесь не нужен,
лишний JOIN без выигрыша".]
```

---

## CHECKLIST ПЕРЕД КАЖДЫМ ЭТАПОМ

Перед реализацией обязательно ответить на вопросы:
1. Что уже существует в проекте и как это соотносится с задачей?
2. Какие файлы затронутся — нет ли риска сломать работающее?
3. Есть ли более простое решение чем то, что я планирую?
4. Как это будет масштабироваться при росте данных?
5. Нет ли дублирования с уже существующим кодом?

---

## ЗАПРЕЩЕНО

- Бизнес-логика в контроллерах
- Сырые SQL-запросы (только Eloquent)
- Хардкод секретов (пароли, ключи) в коде
- Редактирование уже запущенных миграций
- Огромные файлы (контроллер > 150 строк — сигнал к рефакторингу)
- Большие методы (> 30 строк — сигнал разбить)
- Принимать user_id из POST-тела (всегда из auth()->id())
- Переходить к следующему этапу без создания лога

---

## ЭТАПЫ РАЗРАБОТКИ

Выполнять строго по порядку. После завершения каждого этапа:
1. Создать `docs/stage-NN-log.md`
2. Сделать git commit с описанием по конвенции
3. Сообщить об окончании и ждать подтверждения

---

### Этап 01 — Bootstrap проекта

**Цель:** создать Laravel-проект с нуля, настроить MySQL-подключение,
базовую структуру папок, маршрут healthcheck, убедиться что всё собирается.

**Действия:**
1. `composer create-project laravel/laravel . "11.*"` в текущей папке.
2. Настроить `.env.example` (заготовка): DB_CONNECTION=mysql, DB_HOST=127.0.0.1,
   DB_PORT=3306, DB_DATABASE=virtual_mentor, DB_USERNAME, DB_PASSWORD.
   Скопировать в `.env` и заполнить реальными значениями.
3. Создать папку `docs/` с файлом `.gitkeep`.
4. Создать пустые папки с `.gitkeep`: `app/Services/`, `app/Repositories/`,
   `app/Helpers/`.
5. Добавить маршрут `GET /healthz` → JSON `{"status":"ok","app":"Виртуальный наставник"}`.
6. Создать `resources/views/layouts/app.blade.php`:
   - Tailwind CSS CDN
   - Alpine.js CDN (defer)
   - Chart.js CDN
   - `@yield('content')` в теле
   - Мета-теги (charset utf-8, viewport)
   - `@yield('scripts')` перед `</body>`
7. Настроить `.gitignore` (vendor, .env, storage/logs/*.log, .DS_Store).
8. `git init`, первый commit: `chore: bootstrap laravel project`.
9. Проверка: `php artisan route:list` показывает healthz без ошибок.
10. Создать `docs/stage-01-log.md`.

---

### Этап 02 — Аутентификация (регистрация / вход / выход / роли)

**Цель:** система ролей user/admin, регистрация, вход по email+пароль,
HttpOnly-сессии, middleware защита маршрутов, страницы login/register.

**Действия:**
1. Запустить стандартные миграции Laravel (`php artisan migrate` — создаст users,
   password_reset_tokens и т.д.).
2. Создать миграцию `add_role_to_users_table`: добавить поле
   `role ENUM('user','admin') DEFAULT 'user' AFTER email`.
3. Обновить `app/Models/User.php`: добавить `role` в fillable, добавить
   метод `isAdmin(): bool`.
4. Создать `app/Http/Requests/RegisterRequest.php` (name, email unique, password min:8 confirmed).
5. Создать `app/Http/Requests/LoginRequest.php` (email, password).
6. Создать `app/Services/AuthService.php` методы:
   - `register(array $data): User` — создаёт пользователя, логинит, возвращает User.
   - `login(array $credentials): bool` — Auth::attempt, regenerate session.
   - `logout(): void` — Auth::logout, invalidate, regenerate.
7. Создать `app/Http/Controllers/AuthController.php` (showLogin, login,
   showRegister, register, logout) — вся логика делегируется в AuthService.
8. Создать `app/Http/Middleware/RequireAuth.php`
   (если гость → redirect('/login')).
9. Создать `app/Http/Middleware/RequireAdmin.php`
   (если не admin → abort(403)).
10. Зарегистрировать middleware в `bootstrap/app.php`.
11. Маршруты:
    - `GET /login`, `POST /login`
    - `GET /register`, `POST /register`
    - `POST /logout`
    - `GET /` → redirect: авторизован → /dashboard, гость → /login
12. Создать `resources/views/layouts/guest.blade.php`
    (центрированная карточка для login/register).
13. Создать `resources/views/auth/login.blade.php`,
    `resources/views/auth/register.blade.php`.
14. Создать `database/seeders/AdminSeeder.php`:
    - admin@example.com / password (role: admin)
    - user@example.com / password (role: user)
15. Запустить `php artisan migrate` + `php artisan db:seed --class=AdminSeeder`.
16. Git commit: `feat(auth): registration login logout rbac`.
17. Создать `docs/stage-02-log.md`.

---

### Этап 03 — Управление пользователями (Admin)

**Цель:** дать администратору список пользователей и возможность менять роли.
Защита инварианта: нельзя понизить последнего admin.

**Действия:**
1. Создать `app/Services/UserService.php` методы:
   - `list(): Collection` — все пользователи ORDER BY created_at DESC.
   - `changeRole(int $id, string $role, int $actorId): User`:
     * Нельзя менять себе роль (actorId === id → Exception).
     * Транзакция с `lockForUpdate()`: если понижаем последнего admin → Exception.
     * Возвращает обновлённого User.
2. Создать `app/Http/Controllers/Admin/UserController.php`
   (index, updateRole).
3. Создать `resources/views/admin/users/index.blade.php`:
   - Таблица: имя, email, роль, дата регистрации, кнопки смены роли.
   - Disabled для собственной строки.
   - Flash success/error сообщения.
4. Маршруты под middleware RequireAdmin:
   - `GET /admin/users`
   - `PATCH /admin/users/{id}/role`
5. Git commit: `feat(admin): user management role change`.
6. Создать `docs/stage-03-log.md`.

---

### Этап 04 — Цели (Goals CRUD)

**Цель:** пользователь может создавать, просматривать, редактировать,
завершать и архивировать свои цели.

**Действия:**
1. Создать миграцию `create_goals_table` по схеме из раздела «База данных».
   Добавить все указанные индексы.
2. Создать `app/Models/Goal.php`:
   - fillable, belongsTo(User), hasMany(Subtask)
   - Accessor `getProgressAttribute(): int` — считает `completed_tasks / total_tasks * 100`,
     возвращает 0 если задач нет.
   - Scope `forUser(int $userId)` — фильтр по владельцу.
3. Создать `app/Http/Requests/GoalRequest.php` (title required, category enum, deadline date after:today nullable).
4. Создать `app/Services/GoalService.php` методы:
   - `listForUser(int $userId): Collection` — с eager load subtasks.tasks.
   - `create(int $userId, array $data): Goal`.
   - `update(int $goalId, int $userId, array $data): Goal` — проверка владения.
   - `archive(int $goalId, int $userId): Goal`.
   - `complete(int $goalId, int $userId): Goal`.
   - `findOwned(int $goalId, int $userId): Goal` — приватный, abort(403) если чужая.
5. Создать `app/Http/Controllers/GoalController.php` (index, create, store, show, edit, update, destroy).
6. Вью:
   - `goals/index.blade.php` — карточки по категориям, цветовая кодировка,
     прогресс-бары, дедлайны.
   - `goals/create.blade.php` — форма с выбором категории.
   - `goals/edit.blade.php` — форма редактирования.
   - `goals/show.blade.php` — детальная страница (подцели добавим в этапе 05).
7. Маршруты resource `/goals` под RequireAuth.
8. Git commit: `feat(goals): goal crud`.
9. Создать `docs/stage-04-log.md`.

---

### Этап 05 — Подцели и задачи (Subtasks & Tasks)

**Цель:** иерархия Goal → Subtask → Task. Пользователь разбивает цель
на подцели, каждую подцель — на задачи. Отметка задач выполненными без перезагрузки.

**Действия:**
1. Создать миграцию `create_subtasks_table` + `create_tasks_table` по схеме из раздела «База данных». Добавить индексы.
2. Создать `app/Models/Subtask.php` (belongsTo Goal, hasMany Task).
3. Создать `app/Models/Task.php` (belongsTo Subtask).
4. Создать `app/Services/SubtaskService.php` методы:
   `listForGoal`, `create`, `markDone`, `delete`.
   Все методы проверяют цепочку владения: Task → Subtask → Goal → user_id.
5. Создать `app/Services/TaskService.php` методы:
   `listForSubtask`, `create`, `toggle(id): bool`, `delete`.
   `toggle` возвращает новое состояние is_done, логирует в activity_logs.
6. Создать `app/Http/Controllers/SubtaskController.php`,
   `app/Http/Controllers/TaskController.php`.
7. Обновить `goals/show.blade.php`:
   - Секция подцелей со списком задач.
   - Чекбоксы задач через Alpine.js + fetch (POST /tasks/{id}/toggle) без перезагрузки.
   - Добавление новой задачи inline.
   - Прогресс-бар цели обновляется динамически.
8. Маршруты:
   - `/goals/{goal}/subtasks` (POST — создать, DELETE — удалить через id)
   - `/subtasks/{subtask}/tasks` (POST)
   - `/tasks/{task}/toggle` (POST, возвращает JSON)
   - `/tasks/{task}` (DELETE)
9. Git commit: `feat(tasks): subtasks and tasks hierarchy`.
10. Создать `docs/stage-05-log.md`.

---

### Этап 06 — Дашборд и мониторинг прогресса

**Цель:** личный кабинет с визуализацией: статистика, прогресс-бары по целям,
линейный график активности за 30 дней, ближайшие дедлайны.

**Действия:**
1. Создать миграцию `create_activity_logs_table` +
   `create_user_statistics_table` по схеме из раздела «База данных». Добавить индексы.
2. Создать `app/Models/ActivityLog.php`, `app/Models/UserStatistic.php`.
3. Создать `app/Services/DashboardService.php` методы:
   - `summaryForUser(int $userId): array` — возвращает: active_goals,
     completed_goals, archived_goals, tasks_done_today, tasks_done_week,
     current_streak, longest_streak.
   - `activityForUser(int $userId, int $days = 30): array` — массив
     `['date' => 'DD.MM', 'count' => N]` для Chart.js.
   - `nearestDeadlines(int $userId, int $limit = 5): Collection` —
     цели с deadline != null, status = active, ORDER BY deadline ASC.
   - `updateStreak(int $userId): void` — вызывается при toggle task done,
     обновляет current_streak в user_statistics.
4. Создать `app/Http/Controllers/DashboardController.php`.
5. Создать `resources/views/dashboard.blade.php`:
   - 4 stat-карточки (x-stat-card компонент).
   - Прогресс-бары активных целей (x-progress-bar).
   - Line chart «Активность за 30 дней» через Chart.js
     (данные передавать через `@json($activityData)`).
   - Виджет «Ближайшие дедлайны» (цвет red если < 3 дней).
6. Маршрут `GET /dashboard` под RequireAuth.
7. Git commit: `feat(dashboard): progress tracking and charts`.
8. Создать `docs/stage-06-log.md`.

---

### Этап 07 — Система рекомендаций

**Цель:** движок рекомендаций на правилах (rule-based). Подсказки
автоматически генерируются на основе состояния целей и задач пользователя.

**Действия:**
1. Создать `app/Services/RecommendationService.php`.
   Публичный метод `getForUser(int $userId): array` — запускает все
   приватные правила и возвращает массив:
   `[['type' => 'tip|warning|motivate', 'text' => '...', 'action_url' => '...']]`.

   Правила (каждое — приватный метод):
   - `checkOverdueTasks()` → warning: «Есть просроченные задачи. Начни с самой старой.»
   - `checkNearDeadline()` → warning: «Дедлайн цели "X" через N дней!» (< 3 дней)
   - `checkLowProgress()` → tip: «Цель "X" почти не двигается. Выполни хотя бы
     одну задачу сегодня.» (создана > 7 дней назад, прогресс < 20%)
   - `checkGoodStreak()` → motivate: «Отличная серия — N дней подряд! Не останавливайся.» (≥ 3)
   - `checkNoTasksToday()` → tip: «Ты ещё ничего не выполнил сегодня.
     Выбери одну маленькую задачу прямо сейчас.» (нет выполненных, время > 14:00)
   - `suggestBreak()` → tip: «Ты хорошо поработал! Сделай 10-минутный перерыв.»
     (> 5 задач выполнено сегодня)
   - `checkEmptyGoal()` → tip: «Цель "X" без шагов. Разбей её на подзадачи —
     так легче начать.» (цель без подцелей)

2. Добавить виджет рекомендаций в `dashboard.blade.php`
   (x-recommendation компонент, иконки: ⚠️ warning, 💡 tip, 🔥 motivate).
3. Создать страницу `GET /recommendations` — полный список с объяснением.
4. Git commit: `feat(recommendations): rule-based recommendation engine`.
5. Создать `docs/stage-07-log.md`.

---

### Этап 08 — Система достижений (Achievements & Badges)

**Цель:** геймификация. Пользователь получает бейджи автоматически
при выполнении условий. Витрина достижений.

**Действия:**
1. Создать миграции `create_achievements_table` +
   `create_user_achievements_table` по схеме из раздела «База данных».
2. Создать `app/Models/Achievement.php`,
   обновить `User.php` — добавить `belongsToMany(Achievement)` через `user_achievements`.
3. Создать `database/seeders/AchievementSeeder.php` с достижениями:
   - `first_goal` — «Первопроходец»: создал первую цель. 🎯
   - `first_done` — «Сделано!»: выполнил первую задачу. ✅
   - `streak_3` — «Три дня подряд»: 3 дня подряд с выполненными задачами. 🔥
   - `streak_7` — «Недельный боец»: 7 дней подряд. 💪
   - `goal_complete` — «Цель достигнута»: завершил первую цель. 🏆
   - `speed_run` — «Спринтер»: завершил цель за 3 дня. ⚡
   - `five_goals` — «Амбициозный»: создал 5 целей. 🚀
   - `productive_day` — «Продуктивный день»: 10+ задач за день. 📈
4. Создать `app/Services/AchievementService.php`:
   - `checkAndAward(int $userId): array` — проверяет все условия,
     выдаёт незаработанные, возвращает список новых достижений.
   - Вызывать после: создания цели, выполнения задачи, завершения цели.
5. Toast-уведомление при получении нового достижения
   (Alpine.js + session flash, auto-dismiss 5 секунд).
6. Страница `GET /achievements`:
   - Полученные достижения: цветные карточки (x-badge).
   - Неполученные: серые с «?» вместо описания.
   - Прогресс-бар «Получено X из Y».
7. Счётчик достижений в навбаре.
8. Запустить `php artisan db:seed --class=AchievementSeeder`.
9. Git commit: `feat(achievements): badges gamification`.
10. Создать `docs/stage-08-log.md`.

---

### Этап 09 — Напоминания и уведомления

**Цель:** in-app уведомления (колокол в навбаре).
Опционально — email если MAIL_HOST задан.

**Действия:**
1. `php artisan notifications:table` + `php artisan migrate`.
2. Создать `app/Notifications/TaskReminderNotification.php` (database channel + optional mail).
3. Создать `app/Notifications/DeadlineApproachingNotification.php`.
4. Создать `app/Notifications/AchievementEarnedNotification.php`.
5. Создать `app/Console/Commands/SendDailyReminders.php`:
   - Ищет пользователей без выполненных задач сегодня → TaskReminderNotification.
   - Ищет цели с deadline завтра → DeadlineApproachingNotification.
6. Зарегистрировать в `routes/console.php`:
   `Schedule::command('reminders:send')->dailyAt('09:00')`.
7. Создать `app/Http/Controllers/NotificationController.php`:
   index, markRead (PATCH), markAllRead (PATCH).
8. Маршруты: `GET /notifications`,
   `PATCH /notifications/{id}/read`, `PATCH /notifications/read-all`.
9. Колокол в навбаре (`layouts/app.blade.php`):
   - Alpine.js dropdown со списком последних 5 уведомлений.
   - Badge-счётчик непрочитанных (красный кружок).
10. Обновить `.env.example` — добавить MAIL_* переменные с комментариями.
11. Git commit: `feat(notifications): in-app notifications reminders`.
12. Создать `docs/stage-09-log.md`.

---

### Этап 10 — UI Polish (дизайн-система и компоненты)

**Цель:** единый визуальный язык на всех страницах. Навбар как общий layout.
Blade-компоненты. Страницы ошибок.

**Действия:**
1. Обновить `resources/views/layouts/app.blade.php`:
   - Навбар: логотип «🎯 Виртуальный наставник», ссылки (Дашборд / Мои цели /
     Достижения / Рекомендации), колокол уведомлений, dropdown меню
     пользователя (email + выход). Для admin — ссылка «Пользователи».
   - Flash-сообщения (success/error) Alpine.js auto-dismiss 4 сек.
   - `max-w-7xl mx-auto px-4` контейнер.
2. Создать все Blade-компоненты (если ещё не созданы):
   `x-card`, `x-stat-card`, `x-progress-bar`, `x-badge`, `x-recommendation`, `x-goal-card`.
3. Переписать все существующие вью используя компоненты — устранить дублирование.
4. Создать `resources/views/errors/404.blade.php`,
   `resources/views/errors/403.blade.php`.
5. Единая кнопочная система через Tailwind-классы:
   btn-primary: `bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg`
   btn-danger: `bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg`
   btn-secondary: `bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg`
6. Проверить все страницы визуально — убедиться в единообразии.
7. Git commit: `feat(ui): design system components polish`.
8. Создать `docs/stage-10-log.md`.

---

### Этап 11 — Рефакторинг и устранение tech debt

**Цель:** устранить накопившийся технический долг.
Никакой новой функциональности — только улучшение структуры.

**Действия:**
1. Создать `app/Helpers/DateHelper.php`:
   - `formatRu(Carbon $d): string` — формат ДД.ММ.ГГГГ
   - `daysLeft(Carbon $deadline): int` — дней до дедлайна
   - `streakLabel(int $days): string` — «1 день», «2 дня», «5 дней» (правильное склонение)
2. Создать `app/Helpers/ProgressHelper.php`:
   - `progressColor(int $percent): string` — tailwind class:
     red-600 если < 30, yellow-500 если < 70, green-600 если ≥ 70
3. Зарегистрировать оба хелпера в `composer.json` секция `autoload.files`.
   Запустить `composer dump-autoload`.
4. Создать `app/Repositories/GoalRepository.php`,
   `app/Repositories/TaskRepository.php` — вынести SQL-запросы из сервисов.
   Сервисы должны использовать репозитории, не обращаться к моделям напрямую.
5. Создать `app/Policies/GoalPolicy.php` — методы view/update/delete
   (только свои цели). Зарегистрировать в `AppServiceProvider`.
6. Заменить ручные `abort(403)` в контроллерах на `$this->authorize(...)`.
7. Проверить и исправить N+1: все запросы целей должны использовать
   `with(['subtasks.tasks'])`.
8. Убедиться что все индексы из схемы присутствуют в миграциях.
9. `php artisan migrate:fresh --seed` — всё работает с нуля.
10. Git commit: `refactor: service layer repositories policies helpers`.
11. Создать `docs/stage-11-log.md`.

---

### Этап 12 — Demo API, тесты и финальная проверка

**Цель:** демонстрационный API, минимальные тесты, CLAUDE.md,
полный smoke-тест всех сценариев.

**Действия:**
1. Создать `app/Http/Controllers/Api/GoalApiController.php`:
   - `GET /api/goals` — список целей текущего пользователя (JSON)
   - `POST /api/goals` — создать цель (JSON)
   - `GET /api/goals/{id}` — детали цели с подцелями (JSON)
   - `GET /api/user/stats` — статистика пользователя (JSON)
   Возвращают JSON, используют те же сессии что и основное приложение.
2. Создать минимальные Feature-тесты (`php artisan make:test`):
   - `AuthTest`: регистрация, логин, логаут.
   - `GoalTest`: создание цели, редактирование, архивация.
   - `TaskTest`: создание задачи, toggle.
   - `AdminTest`: доступ к /admin только для admin.
3. Запустить тесты: `php artisan test`.
4. Создать `CLAUDE.md` в корне:

```markdown
# Виртуальный наставник — инструкция разработчика

## Стек
Laravel 11, PHP 8.2+, MySQL 8
Blade + Alpine.js + Tailwind CSS (все через CDN)
Chart.js CDN для графиков

## Быстрый старт
```bash
cp .env.example .env
# Заполнить DB_* в .env
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve   # http://localhost:8000
```

## Тестовые аккаунты (после seed)
- admin@example.com / password (роль: admin)
- user@example.com / password (роль: user)

## Переменные окружения
| Переменная | Описание | Обязательная |
|---|---|---|
| DB_DATABASE | Имя базы данных MySQL | Да |
| DB_USERNAME | Пользователь MySQL | Да |
| DB_PASSWORD | Пароль MySQL | Да |
| MAIL_HOST | SMTP сервер для email | Нет |
| MAIL_USERNAME | SMTP логин | Нет |
| MAIL_PASSWORD | SMTP пароль | Нет |

## Artisan-команды
- `php artisan reminders:send` — отправить напоминания вручную
- `php artisan migrate:fresh --seed` — пересоздать БД с тестовыми данными
- `php artisan test` — запустить тесты

## Архитектура
Controllers → Services → Repositories → Models
Бизнес-логика только в Services.
```

5. Пройти полный smoke-чеклист:
   - Регистрация → редирект на /dashboard, достижение first_goal выдано.
   - Создание цели каждой категории.
   - Добавление подцели + задач → отметить выполненными → прогресс-бар обновился.
   - Дашборд: график активности, прогресс-бары, дедлайны.
   - Рекомендации: появляются релевантные советы.
   - Достижения: страница /achievements, полученные и неполученные.
   - Уведомления: колокол в навбаре, `php artisan reminders:send`.
   - API: `GET /api/goals` возвращает JSON.
   - Admin: /admin/users — смена роли работает.
   - Страница 404 при несуществующем URL.
6. Git commit: `feat(api): demo rest api`, затем `test: feature tests`, затем `docs: claude md final`.
7. Создать `docs/stage-12-log.md` с итогами всего проекта.

---

## НАЧАЛО РАБОТЫ

Начни с Этапа 01. После завершения создай `docs/stage-01-log.md` строго
по шаблону выше и сообщи об окончании. Жди подтверждения перед переходом к Этапу 02.

Логи пиши подробно — раздел Notes каждого лога должен содержать минимум
3 нетривиальных обоснования. Документация будет включена в пояснительную
записку дипломного проекта.
