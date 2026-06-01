# Виртуальный наставник — инструкция разработчика

## Стек

- **Laravel 11** (PHP 8.2+)
- **MySQL 8** (UTF-8MB4)
- **Blade + Alpine.js + Tailwind CSS** (всё через CDN, без npm/vite)
- **Chart.js** для графиков (CDN)
- **Mailtrap / SMTP** для email-напоминаний

## Требования

- PHP 8.2+ с расширениями: `pdo_mysql`, `mbstring`, `dom`, `xml`, `json`, `tokenizer`, `fileinfo`, `gd`
- Composer 2.x
- MySQL 8.x

## Быстрый старт

```bash
git clone git@github.com:NezoXMir/Project_VN.git
cd Project_VN

# 1. PHP-зависимости
# При первом клоне — install (точно по composer.lock, не обновляет пакеты):
composer install
# Если vendor/ уже есть и нужно подтянуть новые/обновлённые зависимости после git pull:
# composer install
# Если нужно именно поднять версии пакетов (обновить composer.lock):
# composer update

# 2. Права на запись (storage и кэш должны быть writable для веб-сервера)
chmod -R 775 storage bootstrap/cache

# 3. Окружение
cp .env.example .env
# Открыть .env, заполнить:
#   APP_URL=http://localhost:8000       ← обязательно (подписанные ссылки верификации email сломаются без точного URL)
#   DB_USERNAME, DB_PASSWORD (MySQL)
#   DB_PORT=8889                        ← для MAMP (стандартный MySQL: 3306)
#   MAIL_* (по умолчанию log-driver, письма идут в storage/logs/laravel.log)
#   YANDEX_CAPTCHA_SITEKEY, YANDEX_CAPTCHA_SECRET (для капчи на регистрации)

# 4. Ключ приложения
php artisan key:generate

# 5. Создать БД
mysql -u root -p -e "CREATE DATABASE virtual_mentor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
# или вручную (virtual_mentor → utf8mb4 → utf8mb4_unicode_ci)

# 6. Миграции и тестовые данные
# Создаёт все таблицы: users, goals, sessions, cache, jobs, notifications и др.
# SESSION_DRIVER=database по умолчанию — без migrate сессии работать не будут
php artisan migrate --seed

# 7. Симлинк для файлового хранилища
# Связывает public/storage → storage/app/public
# Без этого аватары профиля и любые загружаемые файлы не отображаются
php artisan storage:link

# 8. Dev-сервер
php artisan serve   # http://localhost:8000
```

### При обновлении (после `git pull`)

```bash
composer install          # подтянет новые зависимости если изменился composer.lock
php artisan migrate       # применит новые миграции
php artisan config:clear  # сбросит кэш конфига если менялись файлы в config/
```

## Тестовые аккаунты (после `db:seed`)

| Email                   | Пароль     | Роль      | Доступ                          |
| ----------------------- | ---------- | --------- | ------------------------------- |
| `admin@example.com`   | `password` | `admin`   | `/admin/*` — полный CRUD        |
| `manager@example.com` | `password` | `manager` | `/admin/*` — ограниченный CRUD  |
| `user@example.com`    | `password` | `user`    | `/dashboard`, `/goals/*` и т.д. |

## Переменные окружения

| Переменная | Описание                                                                                     | Обязательная                    |
| -------------------- | ---------------------------------------------------------------------------------------------------- | ------------------------------------------- |
| `APP_URL`          | Публичный URL приложения (важно для ссылок в email)                | Да (`http://localhost:8000` для dev) |
| `DB_DATABASE`      | Имя базы данных MySQL                                                                   | Да                                        |
| `DB_USERNAME`      | Пользователь MySQL                                                                       | Да                                        |
| `DB_PASSWORD`      | Пароль MySQL                                                                                   | Да                                        |
| `MAIL_MAILER`      | `log` для dev (письма в storage/logs), `smtp` для реальной отправки | Нет                                      |
| `MAIL_HOST`        | SMTP сервер (например `sandbox.smtp.mailtrap.io`)                                    | Только для smtp                    |
| `MAIL_PORT`        | SMTP порт (587 для tls, 465 для ssl, 2525 для Mailtrap)                                 | Только для smtp                    |
| `MAIL_USERNAME`    | SMTP логин                                                                                      | Только для smtp                    |
| `MAIL_PASSWORD`    | SMTP пароль                                                                                    | Только для smtp                    |
| `YANDEX_CAPTCHA_SITEKEY` | Клиентский ключ Yandex SmartCaptcha (для JS-виджета на странице регистрации) | Да (captcha не работает без него) |
| `YANDEX_CAPTCHA_SECRET`  | Серверный ключ для валидации токена на бэкенде (`/validate`)                   | Да (captcha не работает без него) |

## Artisan-команды

```bash
php artisan migrate:fresh --seed     # Пересоздать БД с тестовыми данными
php artisan reminders:send           # Отправить ежедневные напоминания вручную
php artisan reminders:send --user=email@example.com   # Только одному пользователю
php artisan test                     # Запустить feature-тесты
php artisan route:list               # Все маршруты
php artisan storage:link             # Симлинк public/storage → storage/app/public
php artisan tinker                   # REPL
```

## Архитектура

```
HTTP Request
    ↓
Route → Middleware (auth / staff / user.only / not.blocked) → Controller (тонкий)
                                                                     ↓
                                                                  Service (бизнес-логика + Policy)
                                                                     ↓
                                                                  Repository (Eloquent)
                                                                     ↓
                                                                  Model
                                                                     ↓
                                                                  MySQL
```

**Слои:**

- **Controllers** (`app/Http/Controllers/`) — принимают HTTP, делегируют в сервис, возвращают response
- **Services** (`app/Services/`) — бизнес-логика, оркестрация, авторизация через `Gate::authorize()`
- **Repositories** (`app/Repositories/`) — все Eloquent-запросы. Сервисы НЕ обращаются к моделям напрямую
- **Policies** (`app/Policies/`) — правила «кому что можно». Auto-discovered Laravel 11
- **Helpers** (`app/Helpers/`) — `DateHelper` (морфология дедлайнов), `StreakCalculator` (серия дней)
- **Support** (`app/Support/`) — `HomePath` (единая точка «куда редиректить после логина»)

**Ключевой принцип:** контроллер тонкий, бизнес-логика в Services, данные в Repositories.

## Роли и middleware

Три роли: `user`, `manager`, `admin`. Разграничение через middleware-цепочку:

| Alias middleware | Класс | Назначение |
| --- | --- | --- |
| `auth` | Laravel built-in | Проверяет аутентификацию |
| `staff` | `RequireStaff` | Пропускает только `isStaff()` (admin + manager) |
| `user.only` | `ForbidStaffFromUserUi` | Staff на user-маршрутах → редирект `/admin/dashboard` |
| `not.blocked` | `BlockBannedUsers` | Инвалидирует сессию при `blocked_at IS NOT NULL` |

**Ограничения роли менеджера (vs admin):**

| Действие | Manager | Admin |
|---|---|---|
| Просмотр пользователей | ✅ | ✅ |
| Создание / удаление пользователей | ❌ | ✅ |
| Блокировка обычных пользователей | ✅ | ✅ |
| Блокировка staff | ❌ | ✅ |
| Создание / редактирование категорий | ❌ | ✅ |
| Архивирование / восстановление целей | ✅ | ✅ |
| Удаление целей | ❌ | ✅ |
| Очистка уведомлений | ❌ | ✅ |

## Тесты

Тесты используют **отдельную MySQL-базу** (sqlite не подходит — у нас MySQL-специфичные `enum` и `FIELD()`):

```bash
# Создать тестовую БД (один раз)
mysql -u root -p -e "CREATE DATABASE virtual_mentor_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Запустить
php artisan test
```

`phpunit.xml` уже настроен на `DB_DATABASE=virtual_mentor_test`. Каждый тест запускается с `RefreshDatabase` — миграции прогоняются с нуля.

**Требования к PHP CLI:** должны быть включены расширения `mbstring`, `dom`, `json`, `tokenizer`, `xml`. Если PHPUnit жалуется на отсутствие `mbstring` — раскомментируй `extension=mbstring` в `php.ini` (`php --ini` подскажет путь).

## Нюансы и подводные камни

- **`mb_strcut` deprecated в PHP 8.5** — Laravel-овский `MailMessage->line()` использует commonmark, который требует эту функцию. Поэтому email-шаблоны написаны через `view()` напрямую (`emails/daily-reminder.blade.php`), без markdown-цепочки. Не сломается на Windows-сборках PHP без полного mbstring.
- **`MAIL_MAILER=log` по умолчанию** — все письма пишутся в `storage/logs/laravel.log`. Удобно для разработки, не уходят наружу. Для реальной отправки переключить на `smtp` (см. таблицу env).
- **Mailtrap free-rate-limit** — на бесплатном тарифе ловит ~3 письма за burst. Команда `reminders:send` имеет `usleep(2_000_000)` между отправками. Для теста одного письма используй `--user=email@example.com`.
- **Системные категории** (Учёба / Спорт / Работа) вставляются в миграции, не в seeder — это часть схемы. Без них приложение не работает (FK на `goals.category_id`).
- **PDF-экспорт** в админке отложен (см. `docs/stage-06-admin-log.md`). Был пробным образом сделан через `dompdf` / `window.print()` / `html2canvas+jsPDF`, но не дошёл до production-качества из-за рендер-конфликтов. Дашборд показывает все агрегаты на странице.
- **Авторизация на сервис-уровне.** Сервисы вызывают `Gate::forUser($user)->authorize($ability, $model)` сами — контроллеру не нужно дополнительно `$this->authorize()`. Стандартная Laravel-практика — на уровне контроллера, но мы выбрали сервис-уровень для safer-by-default (нельзя вызвать мутирующий метод сервиса в обход проверки).
- **`@can` с class string vs instance** — `@can('staffDelete', Model::class)` передаёт в Policy только 1 аргумент вместо 2, что вызывает 500. Всегда передавай экземпляр модели: `@can('staffDelete', $model)`. Для проверок без модели (admin-only кнопки) используй `auth()->user()->isAdmin()` напрямую.
- **`DB::table('notifications')` в AdminNotificationService** — Laravel не предоставляет Eloquent-модель для таблицы уведомлений. Все запросы через QueryBuilder с LEFT JOIN на users.
- **`MessageBag::only()` не существует** — в этой версии Laravel метод `only()` у `MessageBag` отсутствует. В Blade-шаблонах для перебора ошибок конкретных полей используй вложенные `@foreach` с `$errors->get($field)`, а не `$errors->only([...])`.
- **Yandex SmartCaptcha — ValidationException вместо abort()** — для возврата ошибок капчи используй `throw ValidationException::withMessages(['captcha' => '...'])`. `abort()` принимает HTTP-статус, а не redirect-ответ. `ValidationException` корректно редиректит назад с ошибками через стандартный механизм Laravel.
- **SmartCaptcha — порядок инициализации** — скрипт виджета загружается с `?render=onload&onload=onSmartCaptchaLoad` в URL. Функция `onSmartCaptchaLoad` вызывается после загрузки SDK и вешает listener на кнопку. Флаг `captchaRendered` защищает от двойного рендера при повторном клике.
- **Ссылка верификации email — вне middleware auth** — маршрут `email.verify.link` намеренно вынесен за пределы группы `auth`, чтобы пользователь мог открыть письмо в другом браузере. `EmailVerificationController::verifyLink()` сам логинит пользователя если он не аутентифицирован.
- **Staff обходит верификацию email** — admin и manager создаются вручную через сидер/админку, поэтому `AuthController::login()` не требует от них верификации. Проверка `!$user->isStaff()` защищает от блокировки staff-аккаунтов в петле верификации.

## Структура проекта

Ниже приведены все файлы проекта, созданные или изменённые в ходе разработки.
Системные файлы Laravel (bootstrap, config, public, vendor) не включены.

---

### `app/Console/Commands/`

```
SendDailyReminders.php
    RU: Artisan-команда `reminders:send`. Получает пользователей с включёнными
        напоминаниями, для каждого вызывает ReminderService и отправляет
        уведомление DailyReminder. Поддерживает флаг --user= для одиночной
        отправки. Между письмами usleep(2_000_000) из-за rate-limit Mailtrap.
    EN: Artisan command — sends daily reminder emails via ReminderService.
```

---

### `app/Helpers/`

```
DateHelper.php
    RU: Хелпер для форматирования дедлайнов на русском языке с правильной
        морфологией («1 день», «2 дня», «5 дней»). Используется в шаблонах
        для отображения времени до истечения срока.
    EN: Deadline formatting helper with Russian morphology.

StreakCalculator.php
    RU: Вычисляет текущую серию подряд идущих дней активности пользователя
        (streak). Принимает массив дат событий и возвращает длину цепочки
        вплоть до сегодня или вчера.
    EN: Calculates the user's current consecutive-day activity streak.
```

---

### `app/Http/Controllers/`

```
AuthController.php
    RU: Регистрация, вход и выход. При регистрации валидирует токен Yandex
        SmartCaptcha через verifyCaptcha() (POST на smartcaptcha API) и
        отправляет письмо верификации. При логине проверяет isBlocked(),
        редиректит неверифицированных user на /email/verify, для staff
        верификация не требуется. Через HomePath определяет целевую страницу.
    EN: Registration (captcha + email verification), login, logout. HomePath
        for post-login redirect; unverified non-staff redirected to verify page.

EmailVerificationController.php
    RU: Верификация email двумя способами: по 6-значному коду (verifyCode)
        и по подписанной ссылке (verifyLink). show() показывает страницу
        верификации или редиректит если уже подтверждено. resend() повторно
        отправляет письмо (throttle 3/мин). Маршрут verifyLink вынесен за
        пределы auth middleware — ссылка открывается в любом браузере.
    EN: Email verification — code submission, signed link, resend (throttled).

DashboardController.php
    RU: Главная страница пользователя. Собирает KPI (активные цели, задачи,
        streak), список рекомендаций (RecommendationService) и ближайшие
        дедлайны для отображения на /dashboard.
    EN: User dashboard — KPIs, recommendations, upcoming deadlines.

GoalController.php
    RU: Полный CRUD целей пользователя + действия: archive, restore, complete.
        index() фильтрует архивные цели, archiveIndex() показывает только их.
        Авторизация через GoalPolicy.
    EN: User goal CRUD + archive/restore/complete actions.

CategoryController.php
    RU: CRUD пользовательских категорий. Создание, редактирование и удаление
        категорий, принадлежащих текущему пользователю. Системные категории
        (is_system=true) не затрагиваются.
    EN: User category CRUD (own categories only, not system ones).

ProfileController.php
    RU: Личный кабинет: обновление имени/email/bio, загрузка аватара,
        смена пароля, удаление аккаунта. Делегирует в ProfileService.
        При смене email фиксирует изменение до сохранения, затем отправляет
        письмо верификации через EmailVerificationService и редиректит на
        страницу подтверждения.
    EN: User profile — update info, avatar upload, password change, delete.
        Detects email change and sends verification email.

NotificationController.php
    RU: In-app уведомления пользователя: пометить одно или все как прочитанные.
    EN: Mark user notifications as read (single or all).

AchievementController.php
    RU: Страница достижений пользователя. Отдаёт полный список бейджей с
        признаком earned/locked через AchievementService.
    EN: User achievements page — earned and locked badges.

SubtaskController.php
    RU: JSON-эндпоинты для AJAX-управления подцелями (store, update, destroy).
        Все ответы — JSON, используются на странице goals/show.
    EN: JSON endpoints for subtask AJAX (create, update, delete).

TaskController.php
    RU: JSON-эндпоинты для AJAX-управления задачами (store, update, toggle,
        destroy). toggle меняет статус выполнения и пересчитывает прогресс.
    EN: JSON endpoints for task AJAX (create, update, toggle, delete).
```

#### `app/Http/Controllers/Admin/`

```
DashboardController.php
    RU: Страница /admin/dashboard. Запрашивает AdminStatsService для получения
        KPI (пользователи, цели, задачи, уведомления) и 30-дневной
        активности для Chart.js bar-chart.
    EN: Admin dashboard — system KPIs and 30-day activity chart.

UserController.php
    RU: Полный CRUD пользователей в админке: список с фильтрами, создание,
        просмотр, редактирование, блокировка/разблокировка, удаление.
        Использует UserPolicy (admin-only: create/delete; staff: block).
    EN: Admin user management — full CRUD with block/unblock actions.

GoalController.php
    RU: Просмотр, архивирование, восстановление и удаление целей любого
        пользователя из админки. Авторизация через GoalPolicy (staff-методы).
    EN: Admin goal management — view, archive, restore, delete any goal.

CategoryController.php
    RU: CRUD системных категорий из админки. Создание только с is_system=true,
        удаление запрещено если есть привязанные цели. Только для admin.
    EN: Admin category CRUD — system categories only, admin-only.

ArchiveController.php
    RU: Страница /admin/archive — таблица всех архивных целей системы с
        возможностью восстановить или окончательно удалить. Переиспользует
        AdminGoalService.
    EN: Admin archive — list/restore/delete all archived goals system-wide.

NotificationController.php
    RU: Страница /admin/notifications — список уведомлений с фильтрами,
        4 stat-карточки, удаление одиночной записи и очистка прочитанных
        старше N дней (только admin). Работает через DB::table().
    EN: Admin notification management — list, delete, bulk purge (admin only).

ProfileController.php
    RU: Страница /admin/profile/edit — обновление имени/email и смена пароля
        для staff-пользователя. Переиспользует ProfileService.
    EN: Staff profile page — update name/email and change password.
```

#### `app/Http/Controllers/Api/`

```
GoalApiController.php
    RU: Demo REST API: GET /api/goals, POST /api/goals, GET /api/goals/{id},
        GET /api/user/stats. Использует те же сессии что и веб-интерфейс.
        Ответы — JSON.
    EN: Demo REST API endpoints for goals and user stats (session auth).
```

---

### `app/Http/Middleware/`

```
RequireAuth.php
    RU: Базовый middleware аутентификации. Перенаправляет неаутентифицированных
        пользователей на /login.
    EN: Redirects unauthenticated users to /login.

RequireAdmin.php
    RU: Устаревший middleware для старой системы ролей (только admin).
        Оставлен для обратной совместимости — новый код использует RequireStaff.
    EN: Legacy admin-only middleware (superseded by RequireStaff).

RequireStaff.php
    RU: Пропускает только пользователей, для которых isStaff() = true
        (роли admin и manager). Остальных редиректит на /dashboard или
        возвращает 403 для JSON-запросов. Алиас: staff.
    EN: Allows only staff (admin+manager); redirects others. Alias: staff.

ForbidStaffFromUserUi.php
    RU: Блокирует staff-пользователей от доступа к user-маршрутам.
        Обычный запрос → редирект /admin/dashboard, JSON → 403.
        Алиас: user.only.
    EN: Prevents staff from accessing user UI routes. Alias: user.only.

BlockBannedUsers.php
    RU: При каждом запросе проверяет blocked_at у аутентифицированного
        пользователя. Если заблокирован — инвалидирует сессию и редиректит
        на /login. Алиас: not.blocked.
    EN: Checks blocked_at on every request; invalidates session if blocked.
```

---

### `app/Http/Requests/`

```
LoginRequest.php
    RU: Валидация формы входа (email, password required).
    EN: Login form validation.

RegisterRequest.php
    RU: Валидация регистрации (name, email unique, password confirmed, min:8).
    EN: Registration form validation.

GoalRequest.php
    RU: Валидация создания и редактирования цели (title, category_id,
        description, deadline nullable date).
    EN: Goal create/update validation.

CategoryRequest.php
    RU: Валидация пользовательской категории (label, color hex).
    EN: Category create/update validation.

ProfileUpdateRequest.php
    RU: Валидация обновления профиля (name, email unique ignore self,
        bio nullable, avatar file max:2048).
    EN: Profile update validation (name, email, bio, avatar).

PasswordChangeRequest.php
    RU: Валидация смены пароля (current_password, password confirmed min:8).
    EN: Password change validation.

SubtaskRequest.php
    RU: Валидация создания подцели (title required max:255).
    EN: Subtask create/update validation.

TaskRequest.php
    RU: Валидация задачи (title required max:255).
    EN: Task create/update validation.
```

#### `app/Http/Requests/Admin/`

```
AdminUserStoreRequest.php
    RU: Валидация создания пользователя из админки (name, email unique,
        password confirmed, role из допустимого списка).
    EN: Admin user creation validation.

AdminUserUpdateRequest.php
    RU: Валидация обновления пользователя из админки (name, email unique
        ignore self, role). Пароль — необязательный.
    EN: Admin user update validation.
```

---

### `app/Models/`

```
User.php
    RU: Eloquent-модель пользователя. Содержит константы ролей
        (ROLE_USER, ROLE_MANAGER, ROLE_ADMIN), методы isAdmin(), isManager(),
        isStaff(), isBlocked(), isEmailVerified(). Fillable: name, email,
        password, role, blocked_at, avatar, bio, email_reminders_enabled,
        email_verified_at, email_verification_code,
        email_verification_expires_at. Cast: email_verification_expires_at
        → datetime.
    EN: User model with role constants and helper methods (isAdmin, isStaff,
        isEmailVerified…); includes email verification fields.

Goal.php
    RU: Модель цели. Статусы: active, completed, archived. Скоупы:
        scopeActive(), scopeArchived(). Связи: belongsTo User и Category,
        hasMany Subtask. Поле archived_at (timestamp nullable).
    EN: Goal model — statuses, scopes (active/archived), relations.

Category.php
    RU: Модель категории. Поля: label, color (hex), is_system (системные
        создаются в миграциях), user_id (NULL для системных). Связи:
        belongsTo User, hasMany Goal.
    EN: Category model — system vs user categories, color, label.

Subtask.php
    RU: Подцель в рамках Goal. Поля: title, is_completed. Связи:
        belongsTo Goal, hasMany Task.
    EN: Subtask model — belongs to Goal, has many Tasks.

Task.php
    RU: Задача в рамках Subtask. Поля: title, is_completed.
        Связь: belongsTo Subtask.
    EN: Task model — atomic action item, belongs to Subtask.

Achievement.php
    RU: Словарная модель достижения (seed-данные). Поля: key, title,
        description, icon. Связь: belongsToMany User через user_achievements.
    EN: Achievement badge definition (seeded data).
```

---

### `app/Mail/`

```
EmailVerificationMail.php
    RU: Laravel Mailable для отправки письма верификации email. Содержит
        6-значный код (срок действия 30 мин) и подписанную ссылку (24 ч).
        HTML-шаблон emails/verify-email.blade.php, текстовый фолбэк
        emails/verify-email-text.blade.php. Поля public readonly:
        $user, $code, $verifyLink.
    EN: Mailable for email verification — 6-digit code + signed link,
        HTML + text templates.
```

---

### `app/Notifications/`

```
DailyReminder.php
    RU: Laravel Notification для ежедневных напоминаний. Канал mail.
        Шаблон рендерится через view('emails.daily-reminder') напрямую
        (без markdown-цепочки) из-за deprecated mb_strcut в PHP 8.5.
    EN: Daily reminder notification — renders email via view(), not Markdown.
```

---

### `app/Policies/`

```
GoalPolicy.php
    RU: Правила доступа к целям. Методы владельца: view, create, update,
        delete, archive, restore (только свои цели). Staff-методы:
        staffView/Archive/Restore → isStaff(), staffDelete → isAdmin().
    EN: Goal authorization — owner methods + staff-level methods.

CategoryPolicy.php
    RU: Правила доступа к категориям. Пользовательский CRUD — только своих.
        Admin-методы: staffCreate/Update/Delete → isAdmin().
    EN: Category authorization — user owns, admin manages system categories.

SubtaskPolicy.php
    RU: Проверка владения подцелью через родительскую цель
        (subtask->goal->user_id === auth user).
    EN: Subtask ownership check via parent goal.

TaskPolicy.php
    RU: Проверка владения задачей через цепочку task→subtask→goal→user_id.
    EN: Task ownership check via subtask→goal→user chain.

UserPolicy.php
    RU: Правила управления пользователями в админке. create/update/delete →
        admin only. block/unblock → staff, но manager не может трогать staff.
        Защита от удаления последнего admin.
    EN: Admin user management policy — admin-only CRUD, staff can block users.
```

---

### `app/Providers/`

```
AppServiceProvider.php
    RU: Регистрация глобальных биндингов и boot-логики. Используется для
        конфигурации пагинатора (Paginator::useBootstrapFive()) и других
        boot-хуков.
    EN: App service provider — pagination config and global boot hooks.
```

---

### `app/Repositories/`

```
GoalRepository.php
    RU: Все Eloquent-запросы по целям: список с фильтрами и пагинацией,
        find, create, update, delete. Сервис не обращается к Goal напрямую.
    EN: All Eloquent queries for goals — list, find, create, update, delete.

CategoryRepository.php
    RU: Запросы по категориям: системные + пользовательские, CRUD.
    EN: Eloquent queries for categories.

SubtaskRepository.php
    RU: Запросы по подцелям: create, update, delete, список для цели.
    EN: Eloquent queries for subtasks.

TaskRepository.php
    RU: Запросы по задачам: create, update, delete, toggle is_completed,
        пересчёт прогресса родительской подцели и цели.
    EN: Eloquent queries for tasks, including progress recalculation.
```

---

### `app/Services/`

```
AuthService.php
    RU: Регистрация нового пользователя (хэширование пароля, сохранение),
        логин через Auth::attempt, выход.
    EN: User registration, login via Auth::attempt, logout.

EmailVerificationService.php
    RU: Сервис верификации email. sendVerificationEmail() генерирует 6-значный
        код (хранится в БД с TTL 30 мин) и подписанную ссылку (URL::temporarySignedRoute,
        24 ч), отправляет EmailVerificationMail. verifyByCode() проверяет код и
        его срок, verifyByLink() подтверждает по подписанному URL. markVerified()
        сохраняет email_verified_at и очищает код.
    EN: Email verification service — generates code + signed link, verifies both
        methods, marks email as verified.

GoalService.php
    RU: Бизнес-логика целей: create, update, delete, archive, restore,
        complete. Авторизует через GoalPolicy перед каждым изменением.
    EN: Goal business logic — CRUD, archive, restore, complete with policy.

CategoryService.php
    RU: Бизнес-логика пользовательских категорий. Создание, обновление,
        удаление с проверкой что категория принадлежит пользователю.
    EN: User category business logic with ownership checks.

ProfileService.php
    RU: Обновление профиля (name, email, bio, avatar), смена пароля
        с проверкой текущего через Hash::check(). При смене email обнуляет
        email_verified_at, email_verification_code и
        email_verification_expires_at — статус верификации сбрасывается.
        Используется и в пользовательском ProfileController, и в
        AdminProfileController.
    EN: Profile update and password change — shared by user and admin flows.
        Resets email verification state when email changes.

StatsService.php
    RU: Агрегаты для пользовательского дашборда: количество активных целей,
        выполненных задач, streak, активность за 30 дней.
    EN: User dashboard statistics — active goals, tasks done, streak.

RecommendationService.php
    RU: Rule-based движок рекомендаций. 7 правил: просроченные цели,
        приближающиеся дедлайны, низкий прогресс, серии, отсутствие
        активности и др. Возвращает список текстовых рекомендаций.
    EN: Rule-based recommendation engine — 7 monitoring rules.

AchievementService.php
    RU: Проверка и выдача достижений. После каждого значимого события
        (создание цели, завершение, серия и т.д.) проверяет условия
        8 бейджей и записывает earned в user_achievements.
    EN: Checks and grants achievement badges after key user events.

ReminderService.php
    RU: Собирает данные для ежедневного напоминания конкретного пользователя:
        активные цели с дедлайнами, без дедлайнов, просроченные.
        Передаёт данные в Notification::send().
    EN: Builds daily reminder payload and dispatches DailyReminder notification.

SubtaskService.php
    RU: Бизнес-логика подцелей: create, update, delete с проверкой прав
        через SubtaskPolicy.
    EN: Subtask CRUD with SubtaskPolicy authorization.

TaskService.php
    RU: Бизнес-логика задач: create, update, delete, toggle. При toggle
        пересчитывает прогресс подцели и цели через TaskRepository.
    EN: Task CRUD and toggle — recalculates subtask and goal progress.

UserService.php
    RU: Управление пользователями в админке: list с фильтрами, create,
        update, block/unblock, delete. Защита от удаления последнего
        admin через lockForUpdate + транзакцию. generatePassword(8).
    EN: Admin user management — CRUD, block, generate password, last-admin guard.

AdminStatsService.php
    RU: Системные KPI для /admin/dashboard: userStats(), goalStats(),
        taskStats(), notificationStats(), activity(30). Заполняет пустые
        даты в рядах активности через fillDateSeries().
    EN: System-wide KPIs and 30-day activity series for admin dashboard.

AdminGoalService.php
    RU: Staff CRUD целей: paginate(filters), staffArchive, staffRestore,
        staffDelete. Авторизует через GoalPolicy staff-методы.
    EN: Staff goal management — paginated list with filters, archive/restore/delete.

AdminCategoryService.php
    RU: Admin CRUD системных категорий: paginate с withCount('goals'),
        create (всегда is_system=true), update, delete (проверка что нет
        привязанных целей).
    EN: Admin category management — system categories CRUD with goals count check.

AdminNotificationService.php
    RU: Управление уведомлениями через DB::table('notifications') с
        LEFT JOIN на users (Eloquent-модели нет). Методы: paginate(filters),
        stats() → [total, unread, sent30d, readRate], delete(id),
        purgeRead(days).
    EN: Notification management via QueryBuilder (no Eloquent model available).
```

---

### `app/Support/`

```
HomePath.php
    RU: Единая точка принятия решения «куда отправить пользователя».
        HomePath::for($user) возвращает '/admin/dashboard' для staff
        и '/dashboard' для обычного пользователя. Используется в
        AuthController и корневом маршруте /.
    EN: Single source of truth for post-login redirect URL.
```

---

### `database/migrations/` (проектные, не системные)

```
2026_05_06_162336_add_role_to_users_table.php
    RU: Добавляет ENUM role ('user','admin') с default 'user' в таблицу users.
    EN: Adds role ENUM column to users.

2026_05_07_004046_create_goals_table.php
    RU: Создаёт таблицу goals: title, description, status ENUM
        (active/completed), deadline nullable, user_id FK.
    EN: Creates goals table with status ENUM and user FK.

2026_05_07_011023_create_categories_table.php
    RU: Создаёт таблицу categories. Вставляет 3 системные категории
        (Учёба, Спорт, Работа) прямо в миграции — они часть схемы.
    EN: Creates categories table; seeds 3 system categories inline.

2026_05_07_011147_add_category_id_to_goals_table.php
    RU: Добавляет FK category_id в таблицу goals.
    EN: Adds category_id FK to goals.

2026_05_08_161618_create_subtasks_table.php
    RU: Создаёт таблицу subtasks (title, is_completed, goal_id FK).
    EN: Creates subtasks table.

2026_05_08_161619_create_tasks_table.php
    RU: Создаёт таблицу tasks (title, is_completed, subtask_id FK).
    EN: Creates tasks table.

2026_05_08_230959_add_avatar_and_bio_to_users_table.php
    RU: Добавляет поля avatar (string nullable) и bio (text nullable)
        в таблицу users.
    EN: Adds avatar and bio columns to users.

2026_05_08_235505_create_achievements_table.php
    RU: Создаёт таблицу achievements (key, title, description, icon).
        Вставляет 8 предопределённых достижений.
    EN: Creates achievements table; seeds 8 badge definitions.

2026_05_08_235505_create_user_achievements_table.php
    RU: Создаёт сводную таблицу user_achievements (user_id, achievement_id,
        earned_at).
    EN: Creates user_achievements pivot table.

2026_05_09_150024_create_notifications_table.php
    RU: Создаёт стандартную таблицу Laravel notifications
        (id uuid, type, notifiable, data, read_at).
    EN: Creates Laravel default notifications table.

2026_05_09_150025_add_email_reminders_enabled_to_users_table.php
    RU: Добавляет boolean email_reminders_enabled (default true) в users.
    EN: Adds email_reminders_enabled flag to users.

2026_05_13_120000_add_manager_role_and_blocked_at_to_users_table.php
    RU: Расширяет ENUM role до ('user','manager','admin') и добавляет
        поле blocked_at (timestamp nullable) для блокировки аккаунтов.
    EN: Extends role ENUM with 'manager'; adds blocked_at column.

2026_05_13_120100_add_archived_at_to_goals_table.php
    RU: Добавляет поле archived_at (timestamp nullable) в goals и расширяет
        ENUM status до ('active','completed','archived').
    EN: Adds archived_at and 'archived' status to goals.

2026_05_13_130000_add_email_verification_code_to_users_table.php
    RU: Добавляет два поля в таблицу users: email_verification_code (string(6)
        nullable) — 6-значный числовой код, и email_verification_expires_at
        (timestamp nullable) — время истечения кода (30 мин от отправки).
    EN: Adds email_verification_code and email_verification_expires_at to users.
```

---

### `database/seeders/`

```
AdminSeeder.php
    RU: Создаёт (или обновляет) трёх тестовых пользователей: admin, manager,
        user. Все три создаются с email_verified_at = now() чтобы не блокироваться
        петлёй верификации при демонстрации. Использует updateOrCreate — безопасен
        для повторного запуска.
    EN: Seeds three test accounts (admin, manager, user) with updateOrCreate;
        all pre-verified so demo logins work immediately.

DatabaseSeeder.php
    RU: Главный сидер — вызывается через migrate --seed. Делегирует в
        AdminSeeder. User::factory закомментирован (не нужен для демо).
    EN: Root seeder — delegates to AdminSeeder.
```

---

### `resources/views/layouts/`

```
app.blade.php
    RU: Основной layout для пользовательского UI. Содержит навигацию с
        показом непрочитанных уведомлений, Alpine.js dropdown профиля,
        CDN-подключения Tailwind/Alpine/Chart.js.
    EN: Main user layout — navbar, notifications badge, CDN imports.

guest.blade.php
    RU: Минималистичный layout для страниц входа и регистрации.
        Центрированная карточка без навигации.
    EN: Minimal centered layout for auth pages.

admin.blade.php
    RU: Layout для всей админ-панели. Sticky top bar, фиксированный sidebar
        на десктопе, мобильный drawer на Alpine.js. Оборачивает все
        admin/* страницы.
    EN: Admin panel layout — sticky bar, fixed sidebar, mobile drawer.
```

---

### `resources/views/components/`

```
card.blade.php
    RU: Переиспользуемая карточка x-card. Поддерживает prop :accent (цветная
        полоска сверху), padding, class. Базовый строительный блок интерфейса.
    EN: Reusable card component with optional color accent and padding.

badge.blade.php
    RU: Цветной бейдж x-badge. Tone-prop управляет цветом: green, red, amber,
        gray, blue и т.д.
    EN: Color badge component — accepts tone prop for color variants.

progress-bar.blade.php
    RU: Прогресс-бар x-progress-bar. Принимает value (0–100), отображает
        заполненную полосу с числовым процентом.
    EN: Progress bar — shows percentage fill.

flash-message.blade.php
    RU: Компонент x-flash-message. Показывает session('success') и
        session('error') в стилизованных блоках уведомлений вверху страницы.
    EN: Flash message display for session success/error.

section-header.blade.php
    RU: Заголовок секции внутри карточки x-section-header. Принимает title
        и необязательный subtitle.
    EN: Section title inside a card — title + optional subtitle.
```

#### `resources/views/components/admin/`

```
sidebar.blade.php
    RU: Боковое меню админ-панели. 7 пунктов (Дашборд, Пользователи, Цели,
        Категории, Архив, Уведомления, Мой профиль). Активный пункт
        подсвечивается через Route::currentRouteName(). Использует
        Route::has() для безопасного рендеринга.
    EN: Admin sidebar — 7 nav items, active route highlighting.

page-header.blade.php
    RU: Шапка страницы x-admin.page-header. Props: title, subtitle.
        Слот :actions — для кнопок справа (например, «Создать пользователя»).
    EN: Admin page header — title, subtitle, right-side actions slot.
```

---

### `resources/views/auth/`

```
login.blade.php
    RU: Страница входа. Форма email + password, ссылка на регистрацию,
        flash-сообщение об ошибках.
    EN: Login page with email/password form.

register.blade.php
    RU: Страница регистрации с Yandex SmartCaptcha. Кнопка «Зарегистрироваться»
        имеет type=button и id=register-btn. По клику отображается контейнер
        виджета SmartCaptcha (smartCaptcha.render()). После прохождения капчи
        callback записывает токен в hidden input smart-token и отправляет форму.
        Флаг captchaRendered предотвращает двойной рендер. SDK подключается через
        @push('head'), JS — через @push('scripts') в layouts/guest.blade.php.
    EN: Registration page with Yandex SmartCaptcha — renders widget on button
        click, submits form only after successful captcha.

verify-email.blade.php
    RU: Страница верификации email /email/verify. Показывает адрес почты, поле
        для 6-значного кода (inputmode=numeric, tracking шрифт), кнопку
        «Подтвердить», разделитель, форму повторной отправки письма (POST
        email.verification.resend) и ссылку «Продолжить без подтверждения».
    EN: Email verification page — code input, resend form, skip link.
```

---

### `resources/views/goals/`

```
index.blade.php
    RU: Список активных и выполненных целей пользователя. Архивные цели
        не показываются (отфильтрованы в контроллере). Карточки с прогресс-
        барами, категорией, дедлайном. Ссылка на /goals/archive.
    EN: User goals list — active/completed only, link to archive page.

create.blade.php
    RU: Форма создания новой цели: title, category, description, deadline.
    EN: New goal creation form.

edit.blade.php
    RU: Форма редактирования существующей цели. Предзаполнена текущими
        данными.
    EN: Goal edit form with pre-filled values.

show.blade.php
    RU: Детальная карточка цели. AJAX-управление подцелями и задачами
        (Alpine.js + fetch). Прогресс-бар, кнопки архивирования/восстановления/
        завершения в зависимости от статуса цели.
    EN: Goal detail — subtask/task AJAX management, status-aware action buttons.

archive.blade.php
    RU: Страница архива пользователя /goals/archive. Card-grid архивных целей.
        Карточки с h-full flex flex-col — кнопки «Восстановить» всегда внизу
        независимо от длины описания.
    EN: User archive page — archived goals grid, restore button always at bottom.
```

---

### `resources/views/categories/`

```
index.blade.php
    RU: Список категорий пользователя: системные (только просмотр) и
        собственные (с кнопками редактирования/удаления).
    EN: Category list — system (view only) and user-owned categories.

create.blade.php
    RU: Форма создания пользовательской категории с color picker.
    EN: New category form with color picker.

edit.blade.php
    RU: Форма редактирования категории с предзаполненным цветом.
    EN: Category edit form with pre-filled color.

_form.blade.php
    RU: Переиспользуемая частичная форма для create и edit категорий.
        Alpine.js live-preview выбранного цвета.
    EN: Shared category form partial — Alpine.js color live preview.
```

---

### `resources/views/admin/`

```
dashboard.blade.php
    RU: Главная страница /admin/dashboard. 4 KPI-блока, bar-chart активности
        за 30 дней (Chart.js), таблица последних зарегистрированных
        пользователей.
    EN: Admin dashboard — 4 KPI cards, 30-day activity chart, latest users table.
```

#### `resources/views/admin/users/`

```
index.blade.php
    RU: Таблица всех пользователей с фильтрами (имя/email, роль, статус).
        @can-кнопки: редактировать, заблокировать/разблокировать, удалить.
    EN: User list table with filters and policy-gated action buttons.

create.blade.php
    RU: Форма создания нового пользователя: name, email, role, password.
        Alpine.js генератор пароля (8 символов, алфавит без 0/O/1/l/I).
    EN: Create user form with Alpine.js password generator.

edit.blade.php
    RU: Форма редактирования пользователя. Поле роли disabled + скрытый input
        для текущего пользователя (нельзя понизить себя). Пароль необязателен.
    EN: Edit user form — role field disabled for self (cannot demote yourself).

show.blade.php
    RU: Карточка пользователя: роль, статус, дата регистрации, статистика
        целей. Кнопки блокировки и удаления с политиками.
    EN: User detail card — role, stats, block/delete policy-gated buttons.
```

#### `resources/views/admin/goals/`

```
index.blade.php
    RU: Таблица всех целей системы с фильтрами (поиск, статус, user_id,
        просроченные). Просроченные строки выделены bg-red-50/30.
    EN: All goals table with filters; overdue rows highlighted red.

show.blade.php
    RU: Read-only карточка цели: владелец, категория, прогресс подцелей.
        Кнопки архивировать/восстановить/удалить по политике.
    EN: Read-only goal detail with subtask progress and staff action buttons.
```

#### `resources/views/admin/categories/`

```
index.blade.php
    RU: Таблица категорий с количеством привязанных целей (goals_count).
        Кнопка удаления деактивирована если goals_count > 0.
    EN: Category list with goals count; delete disabled if goals are attached.

create.blade.php
    RU: Форма создания системной категории: label, color picker (пресеты +
        input[type=color] + Alpine.js live preview).
    EN: System category creation form with color picker and live preview.

edit.blade.php
    RU: Форма редактирования категории с предзаполненным цветом и preview.
    EN: Category edit form with pre-filled color preview.
```

#### `resources/views/admin/archive/`

```
index.blade.php
    RU: Страница /admin/archive — пагинированная таблица всех архивных
        целей системы. Кнопки «Восстановить» (staff) и «Удалить» (admin).
    EN: All archived goals table with restore (staff) and delete (admin) actions.
```

#### `resources/views/admin/notifications/`

```
index.blade.php
    RU: Страница /admin/notifications. 4 stat-карточки (всего, непрочитанных,
        за 30 дней, % прочитанных), фильтры, таблица уведомлений. Кнопка
        «Очистить прочитанные» — только для isAdmin().
    EN: Notifications list with 4 stats cards and purge button (admin only).
```

#### `resources/views/admin/profile/`

```
edit.blade.php
    RU: Страница /admin/profile/edit. Два блока: основные данные (name/email)
        и смена пароля. Бейдж роли (Администратор/Менеджер).
    EN: Staff profile edit — name/email form and password change form.
```

---

### `resources/views/achievements/`

```
index.blade.php
    RU: Страница достижений /achievements. Grid бейджей: полученные с датой,
        заблокированные с условием получения.
    EN: Achievements page — earned badges with dates, locked badges with hints.
```

---

### `resources/views/profile/`

```
index.blade.php
    RU: Личный кабинет пользователя /profile. В верхней части карточка статуса
        верификации email: зелёная с датой если подтверждено, янтарная с кнопкой
        «Подтвердить email» (POST email.verification.resend) если нет. Далее:
        основные данные, аватар, смена пароля, опции email-напоминаний, удаление
        аккаунта.
    EN: User profile page — verification status card, info, avatar, password,
        reminders, delete account.
```

---

### `resources/views/emails/`

```
daily-reminder.blade.php
    RU: HTML-шаблон ежедневного email-напоминания. Показывает количество
        активных целей с дедлайнами, без дедлайнов и просроченных.
        Рендерится через view() напрямую (не через MailMessage::markdown).
    EN: Daily reminder HTML email template — goal counts by deadline status.

daily-reminder-text.blade.php
    RU: Plain-text версия того же письма для почтовых клиентов без HTML.
    EN: Plain-text fallback for daily reminder email.

verify-email.blade.php
    RU: HTML-шаблон письма верификации. Стилизованный блок с 6-значным кодом
        (dashed border, крупный шрифт, letter-spacing) и кнопка-ссылка для
        подтверждения одним кликом. Показывает оба способа верификации.
    EN: Email verification HTML template — styled code block + one-click link.

verify-email-text.blade.php
    RU: Plain-text фолбэк письма верификации. Содержит код и URL ссылки
        в текстовом виде для почтовых клиентов без HTML.
    EN: Plain-text fallback for email verification mail.
```

---

### `resources/views/dashboard.blade.php`

```
dashboard.blade.php
    RU: Главная страница пользователя /dashboard. KPI-блоки, прогресс-бары
        целей, график активности за 30 дней (Chart.js), список рекомендаций,
        ближайшие дедлайны.
    EN: User dashboard — KPIs, goals progress, activity chart, recommendations.
```

---

### `routes/`

```
web.php
    RU: Все HTTP-маршруты приложения. Структура: guest-группа (login/register),
        подписанный маршрут email.verify.link вне auth (чтобы ссылка работала
        в любом браузере), auth-группа → logout + маршруты email-верификации
        (show/code/resend) + user.only-подгруппа (весь user UI + API) +
        staff-подгруппа prefix='admin' (вся админ-панель).
    EN: All app routes — guest, signed email verify link (outside auth), auth
        group with email verification routes, user.only and admin/staff groups.
```

---

### `tests/Feature/`

```
AuthTest.php
    RU: Feature-тесты аутентификации: регистрация, вход с верными/неверными
        данными, выход, редирект неаутентифицированных.
    EN: Auth feature tests — register, login, logout, redirect guards.

GoalTest.php
    RU: Feature-тесты CRUD целей: создание, редактирование, удаление,
        архивирование, восстановление, проверка Policy (чужие цели
        недоступны).
    EN: Goal CRUD feature tests including policy and archive/restore flows.

TaskTest.php
    RU: Feature-тесты задач и подцелей: создание через AJAX-эндпоинты,
        toggle, удаление, пересчёт прогресса.
    EN: Subtask and task AJAX endpoint tests — create, toggle, delete.

AdminTest.php
    RU: Feature-тесты админ-панели: доступ только для staff, блокировка
        пользователей, CRUD пользователей, политики ролей.
    EN: Admin panel feature tests — access control, user CRUD, role policies.
```

## Дорожная карта (12 этапов)

- ✅ Этап 01 — Bootstrap (Laravel + MySQL + healthz + layout)
- ✅ Этап 02 — Аутентификация (регистрация / вход / RBAC)
- ✅ Этап 03 — Управление пользователями (Admin)
- ✅ Этап 04 — Цели (Goals CRUD) + пользовательские категории
- ✅ Этап 05 — Подцели и задачи (3-уровневая иерархия) с AJAX
- ✅ Этап 06 — Дашборд + админ-дашборд
- ✅ Этап 06 (доп.) — Профиль пользователя
- ✅ Этап 07 — Rule-based система рекомендаций
- ✅ Этап 08 — Достижения и геймификация (8 бейджей)
- ✅ Этап 09 — Напоминания и in-app уведомления
- ✅ Этап 10 — UI Polish: дизайн-система и компоненты
- ✅ Этап 11 — Рефакторинг: репозитории, политики, хелперы
- ✅ Этап 12 — Demo API, тесты, финальная проверка

Каждый этап подробно задокументирован в `docs/stage-NN-log.md`.

## Demo REST API

Использует те же сессии что и веб-интерфейс (без JWT). Работает только если пользователь залогинен через `/login`.

```bash
# После логина в браузере, получи cookie и используй в curl:

# Список целей текущего пользователя
GET /api/goals

# Создать цель
POST /api/goals
Content-Type: application/json
{
    "title": "Цель",
    "category_id": 1,
    "description": "...",
    "deadline": "2026-12-31"
}

# Детали цели с подцелями и задачами
GET /api/goals/{id}

# Статистика (KPI, активность за 30 дней, дедлайны)
GET /api/user/stats
```

## Полезные ссылки

- Главная: `http://localhost:8000`
- Дашборд: `/dashboard`
- Профиль: `/profile`
- Достижения: `/achievements`
- Админ-дашборд: `/admin/dashboard` (роли admin и manager)
- Управление пользователями: `/admin/users`
- Управление целями: `/admin/goals`
- Категории: `/admin/categories`
- Архив: `/admin/archive`
- Уведомления: `/admin/notifications`
- Профиль staff: `/admin/profile/edit`
- Healthcheck: `/healthz`

## Лицензия

Учебный проект (дипломная работа), использование на усмотрение автора.
