# Виртуальный наставник — инструкция разработчика

## Стек

- **Laravel 11** (PHP 8.2+)
- **MySQL 8** (UTF-8MB4)
- **Blade + Alpine.js + Tailwind CSS** (всё через CDN, без npm/vite)
- **Chart.js** для графиков (CDN)
- **Mailtrap / SMTP** для email-напоминаний

## Быстрый старт

```bash
git clone git@github.com:NezoXMir/Project_VN.git
cd Project_VN

# 1. PHP-зависимости
composer update

# 2. Окружение
cp .env.example .env
# Открыть .env, заполнить:
#   DB_USERNAME, DB_PASSWORD (MySQL)
#   MAIL_* (по умолчанию log-driver, см. ниже про SMTP)

# 3. Ключ приложения
php artisan key:generate

# 4. Создать БД
mysql -u root -p -e "CREATE DATABASE virtual_mentor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
либо вручную (virtual_mentor -> utf8 -> utf8_general_ci)

# 5. Миграции и тестовые данные
php artisan migrate --seed

# 6. Симлинк для аватаров
php artisan storage:link

# 7. Dev-сервер
php artisan serve   # http://localhost:8000
```

## Тестовые аккаунты (после `db:seed`)

| Email                 | Пароль | Роль  |
| --------------------- | ------------ | --------- |
| `admin@example.com` | `password` | `admin` |
| `user@example.com`  | `password` | `user`  |

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
Route → Middleware (auth/admin) → Controller (тонкий)
                                       ↓
                                    Service (бизнес-логика)
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

**Ключевой принцип:** контроллер тонкий, бизнес-логика в Services, данные в Repositories.

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

## Структура проекта

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/                  # Админ-панель
│   │   ├── Api/                    # Demo REST API
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── GoalController.php
│   │   ├── ProfileController.php
│   │   └── ...
│   ├── Middleware/                 # RequireAuth, RequireAdmin
│   └── Requests/                   # Form Request классы
├── Models/                         # Eloquent
├── Services/                       # Бизнес-логика
├── Repositories/                   # SQL-запросы
├── Policies/                       # Авторизация
├── Helpers/                        # DateHelper, StreakCalculator
├── Notifications/                  # DailyReminder
├── Console/Commands/               # SendDailyReminders

resources/views/
├── layouts/                        # app, guest
├── components/                     # x-card, x-badge, x-progress-bar, x-flash-message, x-section-header
├── auth/                           # login, register
├── goals/                          # index, create, edit, show
├── admin/                          # users/index, dashboard
├── achievements/                   # index
├── profile/                        # index
├── categories/                     # index, create, edit, _form
├── emails/                         # daily-reminder + .text
└── errors/                         # 404, 403

database/migrations/                # Только append, без editing старых
docs/                               # Логи каждого этапа разработки (stage-01 ... stage-12)
tests/
├── Feature/                        # AuthTest, GoalTest, TaskTest, AdminTest
└── Unit/
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
- Админ: `/admin/dashboard` (только для роли admin)
- Healthcheck: `/healthz`

## Лицензия

Учебный проект (дипломная работа), использование на усмотрение автора.
