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
- Demo REST API для интеграций

### Для администратора

- Отдельная панель `/admin/dashboard` с системными KPI и графиками
- Управление ролями пользователей (защита: нельзя понизить последнего admin)
- **Без доступа** к персональным данным пользователей — только агрегаты

## 🛠️ Стек технологий

| Слой                     | Технология                         | Зачем именно так                                                             |
| ---------------------------- | -------------------------------------------- | ------------------------------------------------------------------------------------------ |
| Backend                      | **Laravel 11** (PHP 8.2+)              | Зрелый MVC-фреймворк, отличная экосистема                 |
| База данных        | **MySQL 8**                            | Стандарт индустрии, надёжные индексы                       |
| Frontend                     | **Blade + Alpine.js + Tailwind CSS**   | Без сборщика — все библиотеки через CDN                      |
| Графики               | **Chart.js 4**                         | Лёгкая визуализация без громоздких зависимостей |
| Аутентификация | Laravel Session (cookie)                     | HttpOnly сессии, без JWT                                                          |
| Архитектура       | **MVC + Service Layer + Repositories** | Бизнес-логика отделена от HTTP-слоя                              |

> **Почему CDN, а не npm/vite:** для дипломного проекта важно показать
> понимание Laravel и архитектуры, а не настройку bundler-конфигов.
> Это упрощает деплой и снимает вопросы про сборку.

## 🏛️ Архитектура

```
HTTP-запрос
    ↓
Route → Middleware (auth / admin) → Controller
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

11 доменных таблиц + системные таблицы Laravel:

```
users ──┬── categories ──┐
        │                ↓
        ├── goals ──┬── subtasks ── tasks
        │           │
        │           └── (deadline как поле)
        │
        ├── activity_logs    (единый лог событий)
        ├── reminders
        ├── user_statistics  (кэш агрегатов: streak, totals)
        ├── user_achievements ── achievements
        └── notifications    (Laravel default)
```

> `categories` хранит и системные (`user_id = NULL`, `is_system = true`),
> и пользовательские записи. Цели ссылаются через `category_id`.

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
# Затем открыть .env и заполнить DB_USERNAME, DB_PASSWORD

# 4. Сгенерировать ключ приложения
php artisan key:generate

# 5. Накатить миграции и заполнить тестовые данные
php artisan migrate --seed

# 6. Запустить dev-сервер
php artisan serve
```

Открой [http://localhost:8000](http://localhost:8000) — приложение работает.

### Тестовые аккаунты (после `db:seed`)

| Email                 | Пароль | Роль  |
| --------------------- | ------------ | --------- |
| `admin@example.com` | `password` | `admin` |
| `user@example.com`  | `password` | `user`  |

### Healthcheck

```bash
curl http://localhost:8000/healthz
# {"status":"ok","app":"Виртуальный наставник"}
```

## 📂 Структура проекта

```
app/
├── Http/
│   ├── Controllers/        # Тонкие контроллеры (≤ 150 строк)
│   │   └── Admin/          # Контроллеры админ-панели
│   ├── Middleware/         # RequireAuth, RequireAdmin
│   └── Requests/           # Form Request классы
├── Models/                 # Eloquent модели
├── Services/               # 🎯 Вся бизнес-логика
├── Repositories/           # SQL-запросы
├── Helpers/                # DateHelper, ProgressHelper
├── Notifications/          # Laravel Notifications
├── Console/Commands/       # Artisan-команды
└── Policies/               # Авторизация ресурсов

resources/views/
├── layouts/                # app, guest
├── components/             # x-card, x-badge, x-progress-bar...
├── auth/                   # login, register
├── goals/                  # index, create, edit, show
├── admin/                  # users/index, dashboard
└── errors/                 # 404, 403

database/migrations/        # Только append, без редактирования старых
docs/                       # Логи каждого этапа разработки
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
- [ ] **Этап 09** — Напоминания и in-app уведомления
- [ ] **Этап 10** — UI Polish: дизайн-система и компоненты
- [ ] **Этап 11** — Рефакторинг: репозитории, политики, хелперы
- [ ] **Этап 12** — Demo API, тесты, финальная проверка

Подробные логи каждого этапа — в папке [`docs/`](./docs).

## 🔐 Безопасность

- CSRF-токены на всех формах
- Middleware `auth` на всех защищённых маршрутах
- Middleware `admin` на `/admin/*`
- `user_id` **никогда** не принимается из формы — всегда из `auth()->id()`
- Laravel Policy для проверки владения ресурсами
- Bcrypt для паролей (Laravel default)
- Form Request валидация на всех входящих данных
- Eloquent ORM — никаких сырых SQL

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
