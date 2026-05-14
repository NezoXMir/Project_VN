<div align="center">

# 🎯 Виртуальный наставник

**Автоматизированная информационная система для постановки целей,
отслеживания прогресса и получения персональных рекомендаций**

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=flat-square&logo=alpine.js&logoColor=black)](https://alpinejs.dev)
[![License](https://img.shields.io/badge/license-Educational-blue?style=flat-square)](#-лицензия)

</div>

---

## 📖 О проекте

Дипломный проект — веб-приложение, помогающее пользователю **превращать
размытые желания в конкретный план действий**. Цели разбиваются на подцели
и ежедневные задачи, прогресс визуализируется на дашборде, а встроенный
движок рекомендаций мягко подталкивает не сбавлять темп.

Проект создаётся как production-grade образец архитектуры: тонкие
контроллеры, бизнес-логика в сервисном слое, политики авторизации,
полноценная RBAC, индексированная схема БД.

## ✨ Возможности

### Для пользователя

- Постановка целей в категориях с произвольным цветом: 3 системных
  (учёба, спорт, работа) + неограниченное число собственных
- Трёхуровневая иерархия: **Цель → Подцель → Задача**
- Личный дашборд: статистика, прогресс-бары, график активности за 30 дней
- Ближайшие дедлайны с цветовой индикацией
- **Rule-based рекомендательная система** — 7 правил мониторинга:
  просроченные задачи, приближающиеся дедлайны, низкий прогресс,
  серии подряд, отсутствие активности и т.д.
- **Геймификация:** 8 достижений-бейджей, счётчик серий (streaks)
- In-app уведомления и ежедневные email-напоминания
- **Верификация email** — 6-значный код (30 мин) или подписанная ссылка (24 ч);
  статус отображается в профиле
- Demo REST API для интеграций

### Для администратора и менеджера

- Отдельная панель `/admin/dashboard` с системными KPI и графиками активности
- Управление пользователями: список с фильтрами, создание, редактирование, блокировка/разблокировка, удаление
- Управление целями: просмотр, архивирование, восстановление, удаление любой цели
- Управление категориями: создание и редактирование системных категорий
- Архив целей: единая таблица всех архивных целей системы
- Управление уведомлениями: просмотр, фильтрация, очистка прочитанных
- Профиль staff: смена имени/email и пароля прямо в панели
- **Роль менеджера** — доступ к большинству разделов, без права удалять пользователей/цели и создавать категории
- Защита: нельзя удалить последнего администратора; staff не может зайти в user UI

## 🛠️ Стек технологий

| Слой                     | Технология                         | Зачем именно так                                                             |
| ---------------------------- | -------------------------------------------- | ------------------------------------------------------------------------------------------ |
| Backend                      | **Laravel 11** (PHP 8.2+)              | Зрелый MVC-фреймворк, отличная экосистема                 |
| База данных        | **MySQL 8**                            | Стандарт индустрии, надёжные индексы                       |
| Frontend                     | **Blade + Alpine.js + Tailwind CSS**   | Без сборщика — все библиотеки через CDN                      |
| Графики               | **Chart.js 4**                         | Лёгкая визуализация без громоздких зависимостей |
| Аутентификация | Laravel Session (cookie)                     | HttpOnly сессии, без JWT                                                          |
| Captcha             | **Yandex SmartCaptcha**                | Защита регистрации от ботов (серверная + клиентская валидация)  |
| Архитектура       | **MVC + Service Layer + Repositories** | Бизнес-логика отделена от HTTP-слоя                              |

> **Почему CDN, а не npm/vite:** для дипломного проекта важно показать
> понимание Laravel и архитектуры, а не настройку bundler-конфигов.
> Это упрощает деплой и снимает вопросы про сборку.

## 🏛️ Архитектура

```
HTTP-запрос
    ↓
Route → Middleware (auth / staff / user.only / not.blocked) → Controller
                                                                    ↓
                                                                 Service ← Policy
                                                                    ↓
                                                               Repository
                                                                    ↓
                                                              Model (Eloquent)
                                                                    ↓
                                                              MySQL
```

**Ключевой принцип:** контроллер принимает запрос, делегирует в сервис,
возвращает ответ. **Никакой бизнес-логики в контроллерах.**

## 🗄️ Схема базы данных

10 доменных таблиц + системные таблицы Laravel:

```
users ──┬── categories ──┐
        │                ↓
        ├── goals ──── subtasks ── tasks
        │
        ├── user_achievements ── achievements
        └── notifications    (Laravel default)
```

| Таблица | Назначение |
|---|---|
| `users` | Пользователи; поля верификации email (`email_verification_code`, `email_verification_expires_at`) |
| `categories` | Системные (`user_id = NULL`, `is_system = true`) и пользовательские |
| `goals` | Цели с полями `status` ENUM и `archived_at` |
| `subtasks` | Подцели, привязаны к цели |
| `tasks` | Задачи, привязаны к подцели |
| `achievements` | Словарь 8 бейджей (seed-данные) |
| `user_achievements` | Связь пользователь ↔ полученный бейдж |
| `notifications` | Стандартная таблица Laravel (UUID, type, data, read_at) |

## 🚀 Быстрый старт

### Требования

- PHP 8.2+ с расширениями `pdo_mysql`, `mbstring`, `openssl`
- Composer 2.x
- MySQL 8 (запущенный, с пустой БД `virtual_mentor`)

### Установка

```bash
# 1. Клонировать репозиторий
git clone git@github.com:NezoXMir/Project_VN.git
cd Project_VN

# 2. Установить PHP-зависимости
composer install

# 3. Настроить окружение
cp .env.example .env
# Затем открыть .env и заполнить:
#   DB_USERNAME, DB_PASSWORD
#   YANDEX_CAPTCHA_SITEKEY, YANDEX_CAPTCHA_SECRET (Yandex SmartCaptcha)

# 4. Сгенерировать ключ приложения
php artisan key:generate

# 5. Накатить миграции и заполнить тестовые данные
php artisan migrate --seed

# 6. Запустить dev-сервер
php artisan serve
```

Открой [http://localhost:8000](http://localhost:8000) — приложение работает.

### Тестовые аккаунты (после `db:seed`)

| Email                   | Пароль     | Роль      | Стартовая страница      |
| ----------------------- | ---------- | --------- | ----------------------- |
| `admin@example.com`   | `password` | `admin`   | `/admin/dashboard`      |
| `manager@example.com` | `password` | `manager` | `/admin/dashboard`      |
| `user@example.com`    | `password` | `user`    | `/dashboard`            |

### Healthcheck

```bash
curl http://localhost:8000/healthz
# {"status":"ok","app":"Виртуальный наставник"}
```

## 📂 Структура проекта

```
app/
├── Http/
│   ├── Controllers/
│   │   ├── Admin/              # DashboardController, UserController, GoalController,
│   │   │                       # CategoryController, ArchiveController,
│   │   │                       # NotificationController, ProfileController
│   │   ├── Api/                # GoalApiController (demo REST)
│   │   ├── EmailVerificationController.php  # Верификация кодом и ссылкой
│   │   └── ...                 # Auth, Goal, Category, Profile, Subtask, Task
│   ├── Middleware/             # RequireStaff (staff), ForbidStaffFromUserUi (user.only),
│   │                           # BlockBannedUsers (not.blocked), RequireAuth
│   └── Requests/               # Form Request классы (включая Admin/)
├── Mail/                       # EmailVerificationMail (код + подписанная ссылка)
├── Models/                     # User, Goal, Category, Subtask, Task, Achievement
├── Services/                   # Бизнес-логика: EmailVerificationService,
│                               # AdminStatsService, AdminGoalService,
│                               # AdminCategoryService, AdminNotificationService,
│                               # GoalService, UserService, ProfileService и др.
├── Repositories/               # GoalRepository, CategoryRepository, SubtaskRepository, TaskRepository
├── Policies/                   # GoalPolicy, CategoryPolicy, UserPolicy, SubtaskPolicy, TaskPolicy
├── Helpers/                    # DateHelper (дедлайны), StreakCalculator (серии дней)
├── Notifications/              # DailyReminder
├── Console/Commands/           # SendDailyReminders
└── Support/                    # HomePath (редирект после логина)

resources/views/
├── layouts/                    # app, guest, admin
├── components/                 # x-card, x-badge, x-progress-bar, x-flash-message
│   └── admin/                  # sidebar, page-header
├── auth/                       # login, register, verify-email
├── goals/                      # index, create, edit, show, archive
├── categories/                 # index, create, edit, _form
├── admin/
│   ├── dashboard.blade.php
│   ├── users/                  # index, create, edit, show
│   ├── goals/                  # index, show
│   ├── categories/             # index, create, edit
│   ├── archive/                # index
│   ├── notifications/          # index
│   └── profile/                # edit
├── achievements/               # index
├── profile/                    # index (карточка статуса верификации)
├── emails/                     # daily-reminder (HTML + text), verify-email (HTML + text)
└── errors/                     # 404, 403

database/migrations/            # Только append, без редактирования старых
docs/                           # Логи каждого этапа разработки (admin-panel-log.md и др.)
tests/Feature/                  # AuthTest, GoalTest, TaskTest, AdminTest
```

## 🗺️ Дорожная карта (12 этапов)

- [X] **Этап 01** — Bootstrap: каркас Laravel, MySQL, healthz, базовый layout
- [X] **Этап 02** — Аутентификация: регистрация, вход, RBAC (user / admin)
- [X] **Этап 03** — Управление пользователями (Admin)
- [X] **Этап 04** — Цели (Goals CRUD) + пользовательские категории
- [X] **Этап 05** — Подцели и задачи (Subtasks & Tasks) с AJAX
- [X] **Этап 06** — Дашборд и мониторинг прогресса
- [X] **Этап 06 (доп. — админ)** — Админ-дашборд с системными агрегатами (PDF-экспорт отложен)
- [X] **Этап 06 (доп. — профиль)** — Личный кабинет: аватар, имя, email, bio, смена пароля, удаление аккаунта. Расширения (тема, default-категория, тайм-зона) подъезжают вместе с Этапами 09/10
- [X] **Этап 07** — Rule-based система рекомендаций
- [X] **Этап 08** — Достижения и геймификация
- [X] **Этап 09** — Напоминания и in-app уведомления
- [X] **Этап 10** — UI Polish: дизайн-система и компоненты
- [X] **Этап 11** — Рефакторинг: репозитории, политики, хелперы
- [X] **Этап 12** — Demo API, тесты, финальная проверка

Подробные логи каждого этапа — в папке [`docs/`](./docs).

## 🔐 Безопасность

- CSRF-токены на всех формах
- **Yandex SmartCaptcha** на форме регистрации — серверная + клиентская валидация токена
- **Email верификация** — 6-значный код (TTL 30 мин) или подписанный URL (TTL 24 ч);
  статус сбрасывается при смене адреса в профиле
- Middleware `auth` на всех защищённых маршрутах
- Middleware `staff` на `/admin/*` — пропускает только admin и manager
- Middleware `user.only` на user UI — staff автоматически перенаправляется в панель
- Middleware `not.blocked` — заблокированные пользователи теряют сессию на каждом запросе
- `user_id` **никогда** не принимается из формы — всегда из `auth()->id()`
- Laravel Policy на каждый ресурс: Goal, Category, User, Subtask, Task
- Авторизация на сервисном уровне — нельзя вызвать мутирующий метод в обход Policy
- Bcrypt для паролей (Laravel default)
- Form Request валидация на всех входящих данных
- Eloquent ORM — никаких сырых SQL (кроме `DB::table` для notifications, где нет модели)

## 📡 Demo REST API

Для демонстрации интеграционных сценариев. Использует те же сессии,
что и веб-интерфейс (без JWT).

| Метод | Эндпоинт    | Описание                                                  |
| ---------- | ------------------- | ----------------------------------------------------------------- |
| `GET`    | `/api/goals`      | Список целей текущего пользователя |
| `POST`   | `/api/goals`      | Создать цель                                           |
| `GET`    | `/api/goals/{id}` | Детали цели с подцелями                       |
| `GET`    | `/api/user/stats` | Статистика пользователя                     |

## 🧰 Полезные Artisan-команды

```bash
php artisan migrate:fresh --seed     # Пересоздать БД с нуля + сидеры
php artisan reminders:send           # Отправить напоминания вручную
php artisan test                     # Запустить feature-тесты
php artisan route:list               # Все маршруты приложения
```

## 📜 Лицензия

Проект разработан в учебных целях как дипломная работа.
Использование — на усмотрение автора.

## 👤 Автор

**NezoXMir** · [GitHub](https://github.com/NezoXMir)
