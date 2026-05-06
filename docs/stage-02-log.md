# Этап 02 — Аутентификация (регистрация / вход / выход / роли)

## Цель

Реализовать полноценную систему аутентификации с двумя ролями (`user` /
`admin`): регистрация по email+пароль, вход с защитой сессии, выход с
инвалидацией сессии и CSRF-токена, два кастомных middleware (`auth` и
`admin`) для защиты маршрутов, страницы login/register на чистом
Tailwind, начальный seeder с двумя тестовыми аккаунтами.
На выходе — рабочий каркас доступа, готовый к Этапу 03 (управление
пользователями) и Этапу 04 (CRUD целей).

## Выполненные действия

1. Запущены базовые миграции Laravel 11 (`php artisan migrate`):
   создались таблицы `users`, `password_reset_tokens`, `sessions`,
   `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`,
   `migrations`. До этого этапа БД была пустая — Этап 01 намеренно
   не делал миграций (см. Notes).
2. Создана и заполнена миграция
   `database/migrations/2026_05_06_162336_add_role_to_users_table.php`:
   добавляет колонку `role ENUM('user','admin') DEFAULT 'user' AFTER email`.
   Миграция применена.
3. Обновлён `app/Models/User.php`: в `$fillable` добавлен `role`,
   объявлены константы `ROLE_USER`/`ROLE_ADMIN`, добавлен метод
   `isAdmin(): bool` (сравнивает `$this->role === self::ROLE_ADMIN`).
4. Создан `app/Http/Requests/RegisterRequest.php` — валидация
   `name (required, string, max:120)`, `email (required, email, max:160, unique:users,email)`, `password (required, min:8, confirmed)`.
   Сообщения валидации на русском.
5. Создан `app/Http/Requests/LoginRequest.php` — `email`/`password`
   обязательные, формат email проверяется. Сообщения на русском.
6. Создан `app/Services/AuthService.php` с методами:
   - `register(array $data): User` — `User::create` с
     bcrypt-паролем (`Hash::make`), default `role = ROLE_USER`,
     далее `Auth::login` + `session()->regenerate()` (защита от
     session fixation сразу после регистрации).
   - `login(array $credentials, bool $remember = false): bool` —
     `Auth::attempt`, при успехе `regenerate()`, возвращает результат.
   - `logout(): void` — `Auth::logout()` + `invalidate()` +
     `regenerateToken()` (по гайду Laravel — три шага вместе).
7. Создан `app/Http/Controllers/AuthController.php` (тонкий, вся
   логика делегируется в `AuthService`): методы `showLogin`,
   `login`, `showRegister`, `register`, `logout`. При неудачном
   логине ошибка показывается в общем поле `email` (не уточняем,
   что именно — email или пароль — это базовая защита от
   user-enumeration).
8. Создан `app/Http/Middleware/RequireAuth.php` — если `Auth::check()`
   ложно, для JSON-запроса `abort(401)`, иначе redirect на `/login`.
9. Создан `app/Http/Middleware/RequireAdmin.php` — если пользователь
   не админ, `abort(403)` с русским сообщением.
10. В `bootstrap/app.php` (Laravel 11 style) middleware зарегистрированы
    через `$middleware->alias([...])`: алиасы `auth` →
    `RequireAuth`, `admin` → `RequireAdmin`. Алиас `auth`
    переопределяет встроенный Laravel — это сознательно
    (см. Notes #2).
11. Маршруты в `routes/web.php`:
    - `GET /` → если авторизован, редирект на `/dashboard`,
      иначе на `/login`.
    - `GET /healthz` сохранён без изменений.
    - В группе `middleware('guest')`: `GET/POST /register`,
      `GET/POST /login` (стандартный Laravel-алиас `guest`
      редиректит уже залогиненного пользователя на дефолтный
      home — это нормально).
    - В группе `middleware('auth')`: `POST /logout`,
      `GET /dashboard` (заглушка через `Route::view`).
12. Создан `resources/views/layouts/guest.blade.php` —
    минимальный «карточный» layout для login/register
    (Tailwind+Alpine из CDN, центрирование через flex,
    ширина `max-w-md`, лого «🎯 Виртуальный наставник»,
    flash-сообщения с auto-dismiss через 4 секунды).
13. Созданы `resources/views/auth/login.blade.php` и
    `resources/views/auth/register.blade.php` — формы с CSRF,
    выводом `$errors->all()`, индикатором правил
    (минимум 8 символов), checkbox «Запомнить меня» для логина,
    ссылками между login и register.
14. Создан временный `resources/views/dashboard.blade.php`
    (используется существующий `layouts/app.blade.php` из
    Этапа 01 + кнопка «Выйти» через POST-форму). Это заглушка
    для проверки auth-цепочки; полноценный дашборд появится на
    Этапе 06.
15. Создан `database/seeders/AdminSeeder.php` —
    `User::updateOrCreate` по `email`:
    - `admin@example.com` / `password` (role: `admin`)
    - `user@example.com`  / `password` (role: `user`)
      `updateOrCreate` выбран вместо `create`, чтобы
      переплейстеп не падал на UNIQUE constraint
      (см. Notes #4).
16. Запущена `php artisan migrate` (применилась
    `add_role_to_users_table`) и
    `php artisan db:seed --class=AdminSeeder`.
17. Smoke-проверки выполнены:
    - `php artisan route:list` показывает все 10 маршрутов
      (включая `login`, `register`, `logout`, `dashboard`,
      `home`, `healthz`).
    - Через tinker: `Auth::attempt` для admin/password →
      OK, `isAdmin()` → true. Для user/password → OK,
      `isAdmin()` → false. Для admin/wrong → rejected.
    - HTTP-проверка через локальный `php artisan serve`:
      `/` гостем → 302 на `/login`, `/login` → 200,
      `/register` → 200, `/dashboard` гостем → 302 на
      `/login`, `/healthz` → JSON c корректным русским
      названием. Полный POST `/login` с cookie-jar
      создаёт сессию, последующий GET `/dashboard` → 200.
18. Создан `docs/stage-02-log.md` (этот файл).

## Созданные файлы

- `app/Http/Controllers/AuthController.php`
- `app/Http/Middleware/RequireAuth.php`
- `app/Http/Middleware/RequireAdmin.php`
- `app/Http/Requests/LoginRequest.php`
- `app/Http/Requests/RegisterRequest.php`
- `app/Services/AuthService.php`
- `database/migrations/2026_05_06_162336_add_role_to_users_table.php`
- `database/seeders/AdminSeeder.php`
- `resources/views/auth/login.blade.php`
- `resources/views/auth/register.blade.php`
- `resources/views/dashboard.blade.php`
- `resources/views/layouts/guest.blade.php`
- `docs/stage-02-log.md`

## Изменённые файлы

- `app/Models/User.php` — добавлено `role` в `$fillable`,
  константы `ROLE_USER`/`ROLE_ADMIN`, метод `isAdmin()`.
- `bootstrap/app.php` — добавлены alias-ы `auth` и `admin`
  в `withMiddleware`.
- `routes/web.php` — добавлены auth-маршруты, redirect
  на `/login` или `/dashboard` в зависимости от
  авторизации.

## Краткое описание ключевых изменений в коде

**`app/Models/User.php`** — добавлены константы
`ROLE_USER='user'` и `ROLE_ADMIN='admin'`. Это лучше, чем
литералы по тексту: если завтра придётся переименовать роль
или ввести третью (например, `moderator`), правка будет в
одном месте. Метод `isAdmin()` — однострочник, делает
проверку явной и удобной для использования в Blade и в
middleware.

**`app/Services/AuthService.php`** — три метода
(`register`, `login`, `logout`) — по одной ответственности
у каждого. Контроллер вызывает их и не знает деталей про
`session()->regenerate()`, `Auth::attempt`, `Hash::make`.
Если позже потребуется логировать события входа/выхода
или интегрировать 2FA, изменение будет внутри сервиса
без правок контроллера. `register()` сразу логинит — это
ожидаемое UX-поведение (сразу попадаешь в систему).

**`app/Http/Middleware/RequireAuth.php`** — две ветки:
для JSON-запросов отдаёт `401` (для будущего демо-API на
Этапе 12, чтобы клиент видел корректный код), для
обычных — редиректит на `/login`. Без этого fork API
получал бы HTML-страницу логина в ответе, что путает
JSON-клиента.

**`bootstrap/app.php`** — алиас `auth` переопределяет
встроенный Laravel `Authenticate` middleware. Так
маршруты пишутся короче (`Route::middleware('auth')`)
и поведение полностью под нашим контролем (например,
сообщение об ошибке для JSON-запроса). Альтернатива —
давать алиас другое имя (`require.auth`) — но тогда
это нестандартно для Laravel-разработчиков и каждый
раз надо помнить, что у нас особенные алиасы.

**Сообщение об ошибке логина «Неверный email или
пароль.»** — намеренно не уточняет, что именно
неправильно: email не существует или пароль не
подошёл. Это стандартная мера защиты от
user-enumeration атак (нельзя через перебор узнать,
зарегистрирован ли email в системе).

## Использованные команды

```bash
# Базовые миграции (Этап 02 начинается с пустой БД)
php artisan migrate

# Скаффолдинг
php artisan make:migration add_role_to_users_table --table=users
php artisan make:request RegisterRequest
php artisan make:request LoginRequest

# (контроллер, middleware, сервис и сидер написаны вручную —
# через `Write`, без `make:controller`/`make:middleware`,
# чтобы не плодить лишний boilerplate)

# Применение миграции и сидинг
php artisan migrate
php artisan db:seed --class=AdminSeeder

# Smoke-проверки
php artisan route:list
php artisan tinker --execute="..."   # см. ниже
```

## Как поднять и проверить

```bash
# Если это свежий клон / fresh-сетап:
cp .env.example .env
# заполнить DB_USERNAME / DB_PASSWORD
composer install
php artisan key:generate
php artisan migrate
php artisan db:seed --class=AdminSeeder
php artisan serve   # http://localhost:8000
```

Smoke-чеклист:

- [ ] `GET /` гостем → редирект на `/login`.
- [ ] `GET /login` → форма входа (200, русский UI).
- [ ] `GET /register` → форма регистрации (200).
- [ ] `POST /login` с верными кредами `admin@example.com`
  / `password` → редирект на `/dashboard`,
  на дашборде роль `admin`.
- [ ] `POST /login` с неверным паролем → форма с ошибкой
  «Неверный email или пароль.» (поле `email`).
- [ ] `POST /logout` → редирект на `/login` с flash
  «Вы вышли из аккаунта.», `/dashboard` снова
  редиректит на `/login`.
- [ ] `POST /register` (новый email, корректный пароль)
  → автоматический логин + редирект на `/dashboard`.
- [ ] `POST /register` с паролем 7 символов → форма с
  ошибкой «Пароль должен быть не короче 8 символов.».
- [ ] `POST /register` с уже существующим email → ошибка
  «Пользователь с таким email уже существует.».
- [ ] `php artisan route:list` — присутствуют именованные
  маршруты `home`, `login`, `register`, `logout`,
  `dashboard`, `healthz`.
- [ ] `php artisan tinker` →
  `Auth::attempt(['email'=>'admin@example.com','password'=>'password'])`
  → true; `Auth::user()->isAdmin()` → true.

## Notes (нетривиальные обоснования)

1. **Почему отдельная миграция `add_role_to_users_table`,
   а не правка стандартной `0001_01_01_000000_create_users_table.php`.**
   Стандартная миграция создаёт таблицу `users` и она уже
   была применена в Этапе 02 (шаг 1) — менять «запущенную»
   миграцию запрещено мастер-промптом (раздел ЗАПРЕЩЕНО).
   Отдельная миграция `add_role_to_users_table` — правильный
   путь: её можно откатить (`php artisan migrate:rollback`),
   она не сломает уже накатанную схему. Аналогичный подход
   будем использовать дальше для всех изменений схемы.
2. **Почему алиас `auth` переопределён на наш `RequireAuth`,
   а не использован встроенный `Illuminate\Auth\Middleware\Authenticate`.**
   Встроенный `Authenticate` редиректит на маршрут с именем
   `login`, который у нас и есть, так что и без переопределения
   всё работало бы. Но мастер-промпт явно требует создать
   `RequireAuth.php` (п. 8 этапа) и его зарегистрировать.
   Свой middleware даёт два преимущества: (а) можно отдавать
   `401 JSON` для API (понадобится на Этапе 12), (б) проще
   диагностика — стек ошибки указывает на наш файл, а не
   глубоко во vendor. Стоимость переопределения — нулевая,
   risk небольшой и контролируемый.
3. **Почему `register()` сразу логинит пользователя
   без email verification.** В мастер-промпте email
   verification не упоминается ни разу. Дипломный проект —
   это локальная система с тестовыми аккаунтами; включать
   verification означало бы поднимать SMTP. Если позже
   потребуется — добавить `MustVerifyEmail` интерфейс и
   middleware `verified` тривиально, но сейчас это лишний
   функционал, который не приносит ценности комиссии.
4. **Почему `AdminSeeder` использует `updateOrCreate`,
   а не `create`.** `create` падает на UNIQUE constraint
   при повторном запуске (`migrate --seed` или ручной
   `db:seed --class=AdminSeeder` второй раз). А
   `updateOrCreate` идемпотентен: первая часть (поиск по
   `email`) — уникальный ключ, вторая — атрибуты,
   которые перезаписываются. Это даёт побочное удобство:
   если разработчик случайно поменял пароль admin
   через UI и хочет сбросить — повторный сидинг чинит.
5. **Почему ошибка логина показывается в поле `email`,
   а не в поле `password` или в общем баннере.**
   Технически ошибка относится к комбинации, а не к
   полю — но Laravel валидирует поля по отдельности,
   общего «non-field errors» канала нет. Привязка к
   `email` — стандартная практика Laravel Breeze/UI:
   валидационная ошибка появляется под первым полем
   формы, что соответствует паттерну пользовательского
   восприятия (читаешь форму сверху вниз → видишь
   ошибку рядом с email). Главное — текст не выдаёт,
   что именно не так («Неверный email или пароль»),
   и это снимает риск user-enumeration.
