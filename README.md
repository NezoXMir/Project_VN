<div align="center">

# Виртуальный наставник

**Веб-приложение для постановки целей, отслеживания прогресса и персональных рекомендаций**

[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?style=flat-square&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=flat-square&logo=php&logoColor=white)](https://www.php.net)
[![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?style=flat-square&logo=mysql&logoColor=white)](https://www.mysql.com)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-3-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-3-8BC0D0?style=flat-square&logo=alpine.js&logoColor=black)](https://alpinejs.dev)
[![License](https://img.shields.io/badge/license-Educational-blue?style=flat-square)](#-лицензия)

</div>

---

## Содержание

- [О проекте](#-о-проекте)
- [Возможности](#-возможности)
- [Стек технологий](#-стек-технологий)
- [Архитектура](#-архитектура)
- [Схема базы данных](#-схема-базы-данных)
- [Быстрый старт](#-быстрый-старт)
- [Тестовые аккаунты](#-тестовые-аккаунты)
- [Тесты](#-тесты)
- [Demo REST API](#-demo-rest-api)
- [Полезные команды](#-полезные-команды)
- [Безопасность](#-безопасность)
- [Дорожная карта](#-дорожная-карта)
- [Лицензия](#-лицензия)

---

## 📖 О проекте

Дипломный проект — веб-приложение, которое помогает пользователю **превращать размытые желания в конкретный план действий**. Цели разбиваются на подцели и задачи, прогресс визуализируется на личном дашборде, а встроенный движок рекомендаций мягко подталкивает не сбавлять темп.

Проект создан как **production-grade образец архитектуры**: тонкие контроллеры, бизнес-логика в сервисном слое, репозитории, политики авторизации, полноценный RBAC.

---

## ✨ Возможности

### Для пользователя

| Функция | Описание |
|---|---|
| Цели и категории | 3 системных категории (учёба, спорт, работа) + неограниченное число собственных с выбором цвета |
| Иерархия задач | Трёхуровневая структура: **Цель → Подцель → Задача** с AJAX-управлением |
| Дашборд | Статистика, прогресс-бары, график активности за 30 дней, ближайшие дедлайны |
| Рекомендации | Rule-based движок — 7 правил мониторинга: просроченные задачи, низкий прогресс, отсутствие активности и др. |
| Геймификация | 8 достижений-бейджей и счётчик серий (streaks) |
| Уведомления | In-app уведомления и ежедневные email-напоминания |
| Верификация email | 6-значный код (TTL 30 мин) или подписанная ссылка (TTL 24 ч) |
| Профиль | Аватар, имя, email, bio, смена пароля, удаление аккаунта |
| Demo API | REST-эндпоинты для интеграций |

### Для администратора и менеджера

| Функция | Admin | Manager |
|---|:---:|:---:|
| Дашборд с системными KPI и графиком | ✅ | ✅ |
| Просмотр и фильтрация пользователей | ✅ | ✅ |
| Создание / удаление пользователей | ✅ | ❌ |
| Блокировка пользователей | ✅ | ✅ (только user) |
| Управление целями (архив / восстановление) | ✅ | ✅ |
| Удаление целей | ✅ | ❌ |
| CRUD системных категорий | ✅ | ❌ |
| Управление уведомлениями | ✅ | ✅ |
| Очистка уведомлений | ✅ | ❌ |

---

## 🛠️ Стек технологий

| Слой | Технология | Примечание |
|---|---|---|
| Backend | **Laravel 11** (PHP 8.2+) | MVC + Service Layer + Repository |
| База данных | **MySQL 8** | UTF-8MB4, без SQLite |
| Frontend | **Blade + Alpine.js + Tailwind CSS** | Всё через CDN, без npm/vite |
| Графики | **Chart.js 4** | Bar-chart активности на дашборде |
| Captcha | **Yandex SmartCaptcha** | Серверная + клиентская валидация |
| Email | **Mailtrap / SMTP** | `log`-драйвер по умолчанию для dev |

> **Почему CDN, а не npm/vite?** Для дипломного проекта важно показать понимание Laravel и архитектуры, а не настройку bundler-конфигов. Деплой упрощается, сборка не нужна.

---

## 🏛️ Архитектура

```
HTTP-запрос
    ↓
Route → Middleware (auth / staff / user.only / not.blocked)
    ↓
Controller  ← тонкий, только HTTP-логика
    ↓
Service     ← бизнес-логика, вызывает Gate::authorize()
    ↓
Repository  ← все Eloquent-запросы
    ↓
Model → MySQL
```

**Middleware-цепочка:**

| Алиас | Назначение |
|---|---|
| `auth` | Проверяет аутентификацию |
| `staff` | Пропускает только admin и manager |
| `user.only` | Staff на user-маршрутах → редирект в `/admin/dashboard` |
| `not.blocked` | Заблокированный пользователь — сессия инвалидируется |

---

## 🗄️ Схема базы данных

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
| `users` | Пользователи; поля верификации email, роль ENUM, `blocked_at` |
| `categories` | Системные (`is_system = true`, `user_id = NULL`) и пользовательские |
| `goals` | Цели; статус ENUM (`active` / `completed` / `archived`), `archived_at` |
| `subtasks` | Подцели, привязаны к цели |
| `tasks` | Задачи, привязаны к подцели |
| `achievements` | Словарь 8 бейджей (seed-данные) |
| `user_achievements` | Связь пользователь ↔ полученный бейдж |
| `notifications` | Стандартная таблица Laravel (UUID, type, data, read_at) |

---

## 🚀 Быстрый старт

### Требования

- **PHP 8.2+** с расширениями: `pdo_mysql`, `mbstring`, `dom`, `xml`, `json`, `tokenizer`, `fileinfo`, `gd`
- **Composer 2.x**
- **MySQL 8.x**

### Установка

```bash
# 1. Клонировать репозиторий
git clone git@github.com:NezoXMir/Project_VN.git
cd Project_VN

# 2. PHP-зависимости (install — строго по composer.lock)
composer install

# 3. Права на запись
chmod -R 775 storage bootstrap/cache

# 4. Окружение
cp .env.example .env
```

Открыть `.env` и заполнить обязательные поля:

```env
APP_URL=http://localhost:8000       # точный URL — нужен для подписанных ссылок верификации

DB_USERNAME=root
DB_PASSWORD=secret
# DB_PORT=8889                      # раскомментировать для MAMP

YANDEX_CAPTCHA_SITEKEY=...
YANDEX_CAPTCHA_SECRET=...
```

```bash
# 5. Ключ приложения
php artisan key:generate

# 6. Создать БД
mysql -u root -p -e "CREATE DATABASE virtual_mentor CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 7. Миграции + тестовые данные
#    Создаёт все таблицы включая sessions, cache, jobs, notifications
php artisan migrate --seed

# 8. Симлинк хранилища (нужен для отображения аватаров профиля)
php artisan storage:link

# 9. Dev-сервер
php artisan serve
```

Открой [http://localhost:8000](http://localhost:8000) — приложение готово.

### При обновлении (после `git pull`)

```bash
composer install          # подтянет новые зависимости если изменился composer.lock
php artisan migrate       # применит новые миграции
php artisan config:clear  # сбросить кэш конфига если менялись файлы в config/
```

---

## 👤 Тестовые аккаунты

Создаются автоматически после `php artisan migrate --seed`:

| Email | Пароль | Роль | Стартовая страница |
|---|---|---|---|
| `admin@example.com` | `password` | admin | `/admin/dashboard` |
| `manager@example.com` | `password` | manager | `/admin/dashboard` |
| `user@example.com` | `password` | user | `/dashboard` |

```bash
# Healthcheck
curl http://localhost:8000/healthz
# {"status":"ok","app":"Виртуальный наставник"}
```

---

## 🧪 Тесты

Тесты используют **отдельную MySQL-базу** (не SQLite — в проекте есть MySQL-специфичные ENUM и `FIELD()`):

```bash
# Создать тестовую БД (один раз)
mysql -u root -p -e "CREATE DATABASE virtual_mentor_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Запустить тесты
php artisan test
```

`phpunit.xml` настроен на `DB_DATABASE=virtual_mentor_test`. Каждый тест использует `RefreshDatabase`.

| Файл | Что проверяет |
|---|---|
| `AuthTest.php` | Регистрация, вход, выход, редиректы |
| `GoalTest.php` | CRUD целей, Policy, архив/восстановление |
| `TaskTest.php` | AJAX-эндпоинты подцелей и задач, пересчёт прогресса |
| `AdminTest.php` | Доступ к панели, блокировка, CRUD пользователей |

---

## 📡 Demo REST API

Использует те же сессии, что и веб-интерфейс (без JWT). Требует авторизации через `/login`.

| Метод | Эндпоинт | Описание |
|---|---|---|
| `GET` | `/api/goals` | Список целей текущего пользователя |
| `POST` | `/api/goals` | Создать цель |
| `GET` | `/api/goals/{id}` | Детали цели с подцелями и задачами |
| `GET` | `/api/user/stats` | KPI, активность за 30 дней, дедлайны |

```bash
# Пример: создать цель
curl -X POST http://localhost:8000/api/goals \
  -H "Content-Type: application/json" \
  -b "laravel_session=..." \
  -d '{"title":"Моя цель","category_id":1,"deadline":"2026-12-31"}'
```

---

## 🧰 Полезные команды

```bash
php artisan migrate:fresh --seed                         # Пересоздать БД с нуля
php artisan reminders:send                               # Отправить напоминания вручную
php artisan reminders:send --user=email@example.com      # Напоминание одному пользователю
php artisan test                                         # Feature-тесты
php artisan route:list                                   # Все маршруты
php artisan tinker                                       # REPL
php artisan storage:link                                 # Пересоздать симлинк хранилища
```

---

## 🔐 Безопасность

- CSRF-токены на всех формах (Laravel default)
- **Yandex SmartCaptcha** на форме регистрации — серверная + клиентская валидация токена
- **Email-верификация** — 6-значный код (TTL 30 мин) или подписанный URL (TTL 24 ч); статус сбрасывается при смене адреса
- Middleware `not.blocked` — заблокированный пользователь теряет сессию на каждом запросе
- `user_id` никогда не принимается из формы — только из `auth()->id()`
- **Laravel Policy** на каждый ресурс: Goal, Category, User, Subtask, Task
- Авторизация на сервисном уровне — нельзя вызвать мутирующий метод в обход Policy
- Bcrypt для паролей, Form Request валидация на всех входящих данных
- Eloquent ORM — никаких сырых SQL (кроме `DB::table` для `notifications`, где нет Eloquent-модели)

---

## 🗺️ Дорожная карта

- [x] **Этап 01** — Bootstrap: каркас Laravel, MySQL, healthz, layout
- [x] **Этап 02** — Аутентификация: регистрация, вход, RBAC (user / admin)
- [x] **Этап 03** — Управление пользователями (Admin)
- [x] **Этап 04** — Цели (Goals CRUD) + пользовательские категории
- [x] **Этап 05** — Подцели и задачи с AJAX
- [x] **Этап 06** — Дашборд и мониторинг прогресса
- [x] **Этап 06+** — Админ-дашборд, личный кабинет пользователя
- [x] **Этап 07** — Rule-based система рекомендаций
- [x] **Этап 08** — Достижения и геймификация (8 бейджей)
- [x] **Этап 09** — Напоминания и in-app уведомления
- [x] **Этап 10** — UI Polish: дизайн-система и компоненты
- [x] **Этап 11** — Рефакторинг: репозитории, политики, хелперы
- [x] **Этап 12** — Demo API, тесты, финальная проверка

Подробные логи каждого этапа — в папке [`docs/`](./docs).

---

## 📜 Лицензия

Проект разработан в учебных целях как дипломная работа. Использование — на усмотрение автора.

---

<div align="center">

**NezoXMir** · [GitHub](https://github.com/NezoXMir)

</div>
