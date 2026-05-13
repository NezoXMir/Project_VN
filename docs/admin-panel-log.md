# Рефакторинг админ-панели — полный лог (12 этапов)

Дата: 2026-05-13  
Ветка: `macbook/main`

---

## Цель рефакторинга

Исходная админ-панель содержала только дашборд со статистикой.
Рефакторинг добавил полноценный CRUD-интерфейс для управления всеми
сущностями системы, роль менеджера с ограниченными правами и изоляцию
staff-аккаунтов от пользовательского UI.

---

## Этап 1 — БД и доменная модель

**Файлы:**
- `database/migrations/2026_05_13_120000_add_manager_role_and_blocked_at_to_users_table.php`
- `database/migrations/2026_05_13_120100_add_archived_at_to_goals_table.php`
- `app/Models/User.php` (изменён)
- `app/Models/Goal.php` (изменён)

**Что сделано:**
- ENUM `role` расширен: `('user','manager','admin')`
- Добавлена колонка `users.blocked_at` (timestamp, nullable)
- Добавлена колонка `goals.archived_at` (timestamp, nullable)
- `User`: константы `ROLE_MANAGER`, методы `isManager()`, `isStaff()`, `isBlocked()`
- `Goal`: скоупы `scopeActive()` и `scopeArchived()`

---

## Этап 2 — Маршруты и middleware

**Файлы:**
- `app/Http/Middleware/RequireStaff.php` (новый)
- `app/Http/Middleware/ForbidStaffFromUserUi.php` (новый)
- `app/Http/Middleware/BlockBannedUsers.php` (новый)
- `app/Support/HomePath.php` (новый)
- `bootstrap/app.php` (изменён)
- `app/Http/Controllers/AuthController.php` (изменён)

**Что сделано:**
- `RequireStaff` — пропускает только `isStaff()` (admin + manager)
- `ForbidStaffFromUserUi` — staff на user-маршрутах → редирект на `/admin/dashboard`; JSON → 403
- `BlockBannedUsers` — при каждом запросе проверяет `blocked_at`; инвалидирует сессию заблокированного
- `HomePath::for($user)` — единая точка решения «куда отправить после логина»
- Алиасы: `staff`, `user.only`, `not.blocked` в `bootstrap/app.php`
- `AuthController::login()` проверяет `isBlocked()` и использует `HomePath`

---

## Этап 3 — Каркас админ-панели

**Файлы:**
- `resources/views/layouts/admin.blade.php` (новый)
- `resources/views/components/admin/sidebar.blade.php` (новый)
- `resources/views/components/admin/page-header.blade.php` (новый)

**Что сделано:**
- Layout `layouts/admin` — sticky top bar, фиксированный sidebar (md+), мобильный drawer на Alpine.js
- Сайдбар: 7 пунктов (`Дашборд`, `Пользователи`, `Цели`, `Категории`, `Архив`, `Уведомления`, `Мой профиль`). Использует `Route::has()` — безопасно рендерится пока маршруты ещё не зарегистрированы
- `x-admin.page-header` — переиспользуемый заголовок страницы с слотом `actions`

---

## Этап 4 — Расширенный админ-дашборд

**Файлы:**
- `app/Services/AdminStatsService.php` (переписан)
- `resources/views/admin/dashboard.blade.php` (изменён)

**Что сделано:**
- `AdminStatsService`: методы `userStats()`, `goalStats()`, `taskStats()`, `notificationStats()`, `activity(30)`
- Заполнение пустых дат в графиках через `fillDateSeries()`
- Дашборд: 4 блока KPI, bar-chart активности (Chart.js), таблица последних пользователей

---

## Этап 5 — Управление пользователями

**Файлы:**
- `app/Policies/UserPolicy.php` (новый)
- `app/Services/UserService.php` (переписан)
- `app/Http/Controllers/Admin/UserController.php` (переписан)
- `resources/views/admin/users/index.blade.php` (новый)
- `resources/views/admin/users/create.blade.php` (новый)
- `resources/views/admin/users/edit.blade.php` (новый)
- `resources/views/admin/users/show.blade.php` (новый)

**Что сделано:**
- `UserPolicy`: `create/update` → admin only; `block/unblock` → staff (manager только обычных); `delete` → admin, не себя
- `UserService`: `list(filters)`, `create`, `update`, `block`, `unblock`, `delete`, `generatePassword(8)`
- Защита от удаления последнего admin через `lockForUpdate` + транзакция
- Alpine.js генератор паролей (8 символов, алфавит без 0/O/1/l/I)
- `edit.blade.php`: disabled select роли + скрытый input — нельзя понизить себя
- Фильтры: поиск по имени/email, по роли, по статусу

---

## Этап 6 — Управление целями

**Файлы:**
- `app/Policies/GoalPolicy.php` (изменён)
- `app/Services/AdminGoalService.php` (новый)
- `app/Http/Controllers/Admin/GoalController.php` (новый)
- `resources/views/admin/goals/index.blade.php` (новый)
- `resources/views/admin/goals/show.blade.php` (новый)

**Что сделано:**
- `GoalPolicy`: staff-методы `staffView`, `staffArchive`, `staffRestore` (isStaff()), `staffDelete` (isAdmin())
- `AdminGoalService`: пагинация с фильтрами (поиск, статус, user_id, просроченные), staff CRUD
- `index.blade.php`: таблица с `@can`-кнопками, просроченные выделены красным фоном
- `show.blade.php`: read-only карточка + прогресс подцелей

---

## Этап 7 — Управление категориями

**Файлы:**
- `app/Policies/CategoryPolicy.php` (изменён)
- `app/Services/AdminCategoryService.php` (новый)
- `app/Http/Controllers/Admin/CategoryController.php` (новый)
- `resources/views/admin/categories/index.blade.php` (новый)
- `resources/views/admin/categories/create.blade.php` (новый)
- `resources/views/admin/categories/edit.blade.php` (новый)

**Что сделано:**
- `CategoryPolicy`: `staffCreate/Update/Delete` — только admin
- `AdminCategoryService`: создание только системных категорий (`is_system=true`), редактирование любых, удаление с проверкой «нет целей»
- Форма с color picker: пресеты + `<input type="color">` + live preview через Alpine.js
- Удаление заблокировано на уровне UI если `goals_count > 0`

---

## Этап 8 — Архив целей

**Файлы:**
- `app/Http/Controllers/GoalController.php` (изменён)
- `app/Http/Controllers/Admin/ArchiveController.php` (новый)
- `resources/views/goals/archive.blade.php` (новый)
- `resources/views/goals/index.blade.php` (изменён)
- `resources/views/goals/show.blade.php` (изменён)
- `resources/views/admin/archive/index.blade.php` (новый)

**Что сделано:**
- Архивные цели убраны из `/goals` — показываются только на `/goals/archive`
- `goals/archive.blade.php`: card-grid с `h-full flex flex-col` — кнопки всегда внизу
- Кнопка «Восстановить» добавлена на `goals/show.blade.php` для статуса `archived`
- Admin `/admin/archive`: пагинированная таблица всех архивных целей системы

---

## Этап 9 — Управление уведомлениями

**Файлы:**
- `app/Services/AdminNotificationService.php` (новый)
- `app/Http/Controllers/Admin/NotificationController.php` (новый)
- `resources/views/admin/notifications/index.blade.php` (новый)

**Что сделано:**
- `AdminNotificationService`: работа через `DB::table('notifications')` (Laravel не предоставляет Eloquent-модель)
- Stats: total / unread / sent_30d / readRate (int)
- Фильтры: user_id, статус (read/unread)
- 4 stat-карточки вверху страницы
- Кнопка «Очистить прочитанные» с настраиваемым кол-вом дней — только для admin (`@if(auth()->user()->isAdmin())`)
- **Bugfix:** `@can('staffDelete', Model::class)` вызывает policy с 1 аргументом вместо 2 → 500. Исправлено на прямую проверку `isAdmin()`

---

## Этап 10 — Роль Менеджер

**Файлы:**
- `app/Http/Controllers/Admin/ProfileController.php` (новый)
- `resources/views/admin/profile/edit.blade.php` (новый)

**Что сделано:**
- Страница профиля для staff: обновление имени/email + смена пароля
- Реиспользует `ProfileService::updateProfile()` и `ProfileService::changePassword()`
- Policy-аудит: 11/11 проверок для роли `manager` — все ограничения корректны

**Итог ограничений менеджера:**
| Действие | Manager | Admin |
|---|---|---|
| Просмотр пользователей | ✅ | ✅ |
| Создание/удаление пользователей | ❌ | ✅ |
| Блокировка обычных пользователей | ✅ | ✅ |
| Блокировка staff | ❌ | ✅ |
| Создание/редактирование категорий | ❌ | ✅ |
| Архивирование/восстановление целей | ✅ | ✅ |
| Удаление целей | ❌ | ✅ |
| Очистка уведомлений | ❌ | ✅ |

---

## Этап 11 — Изоляция staff от user UI

**Файлы:**
- `routes/web.php` (изменён)

**Что сделано:**
- Все user-маршруты обёрнуты в под-группу `middleware('user.only')`
- `/logout` намеренно оставлен вне группы — доступен всем ролям
- API-маршруты (`/api/*`) включены в группу: JSON-запрос staff получает 403

**Smoke-тест:**
- `Admin → GET /dashboard` → редирект на `/admin/dashboard` ✓
- `User → GET /dashboard` → проходит ✓

---

## Итог: новые файлы

### Controllers (Admin/)
- `DashboardController.php` — переписан (AdminStatsService)
- `UserController.php` — переписан (полный CRUD)
- `GoalController.php` — новый
- `CategoryController.php` — новый
- `ArchiveController.php` — новый
- `NotificationController.php` — новый
- `ProfileController.php` — новый

### Services
- `AdminStatsService.php` — переписан
- `UserService.php` — переписан
- `AdminGoalService.php` — новый
- `AdminCategoryService.php` — новый
- `AdminNotificationService.php` — новый

### Middleware
- `RequireStaff.php` — новый
- `ForbidStaffFromUserUi.php` — новый
- `BlockBannedUsers.php` — новый

### Support
- `HomePath.php` — новый

### Policies (изменены)
- `UserPolicy.php` — новый
- `GoalPolicy.php` — добавлены staff-методы
- `CategoryPolicy.php` — добавлены staff-методы

### Views (admin/)
- `layouts/admin.blade.php`
- `components/admin/sidebar.blade.php`
- `components/admin/page-header.blade.php`
- `admin/dashboard.blade.php`
- `admin/users/` — index, create, edit, show
- `admin/goals/` — index, show
- `admin/categories/` — index, create, edit
- `admin/archive/` — index
- `admin/notifications/` — index
- `admin/profile/` — edit

### Views (user, изменены)
- `goals/index.blade.php` — убраны архивные цели
- `goals/show.blade.php` — добавлена кнопка «Восстановить»
- `goals/archive.blade.php` — новый

### Migrations
- `2026_05_13_120000_add_manager_role_and_blocked_at_to_users_table.php`
- `2026_05_13_120100_add_archived_at_to_goals_table.php`
