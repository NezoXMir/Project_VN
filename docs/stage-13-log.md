# Этап 13 — Yandex SmartCaptcha + Верификация email

**Дата:** 13.05.2026  
**Коммиты:** `bdeaff9`, `865a250`, `a975c69`

---

## Что было сделано

### 1. Yandex SmartCaptcha на странице регистрации (`bdeaff9`)

**Задача:** защитить форму регистрации от ботов с помощью Yandex SmartCaptcha.

**Изменённые файлы:**

| Файл | Что сделано |
|---|---|
| `resources/views/auth/register.blade.php` | Кнопка «Зарегистрироваться» получила `type="button"` и `id="register-btn"`. Добавлен скрытый `<div id="captcha-container">` и `<input type="hidden" name="smart-token">`. SDK подключается через `@push('head')`, JS-логика — через `@push('scripts')`. |
| `app/Http/Controllers/AuthController.php` | Добавлен приватный метод `verifyCaptcha(Request $request)`: валидирует `smart-token` через `POST https://smartcaptcha.yandexcloud.net/validate`. При пустом или невалидном токене бросает `ValidationException::withMessages()`. |
| `config/services.php` | Добавлена секция `yandex_captcha` с ключами `sitekey` и `secret` из `.env`. |
| `.env.example` | Добавлены плейсхолдеры `YANDEX_CAPTCHA_SITEKEY=` и `YANDEX_CAPTCHA_SECRET=`. |

**Логика работы капчи:**

1. Пользователь заполняет форму и нажимает кнопку.
2. JS-функция `onSmartCaptchaLoad` (вызывается SDK через `onload=onSmartCaptchaLoad` в URL скрипта) вешает listener на кнопку.
3. По клику — контейнер виджета становится видимым, кнопка блокируется, вызывается `smartCaptcha.render()`.
4. После успешного прохождения капчи callback записывает токен в `smart-token` и вызывает `form.submit()`.
5. Бэкенд (`verifyCaptcha`) проверяет токен через API Яндекса.

**Подводный камень:** нельзя использовать `abort()` для возврата ошибок — он принимает HTTP-статус, а не redirect-ответ. Использован `throw ValidationException::withMessages([...])`, который редиректит назад с ошибками через стандартный Laravel-механизм.

---

### 2. Система верификации email (`865a250`)

**Задача:** после регистрации и при входе с неподтверждённым адресом показывать страницу подтверждения email. Поддержать два способа верификации: 6-значный код и подписанная ссылка.

#### 2.1 Миграция

**`database/migrations/2026_05_13_130000_add_email_verification_code_to_users_table.php`**

Добавлены два поля в таблицу `users`:
- `email_verification_code` — `string(6) nullable` — числовой код;
- `email_verification_expires_at` — `timestamp nullable` — TTL кода (30 минут).

Поле `email_verified_at` уже присутствовало в стандартной Laravel-миграции.

#### 2.2 Модель `User`

- Добавлены в `$fillable`: `email_verified_at`, `email_verification_code`, `email_verification_expires_at`.
- В `casts()`: `'email_verification_expires_at' => 'datetime'`.
- Новый метод `isEmailVerified(): bool` — проверяет `email_verified_at !== null`.

#### 2.3 `EmailVerificationService`

Новый сервис `app/Services/EmailVerificationService.php`:

| Метод | Описание |
|---|---|
| `sendVerificationEmail(User $user)` | Генерирует код, создаёт подписанный URL (`URL::temporarySignedRoute`, 24 ч), отправляет `EmailVerificationMail`. |
| `verifyByCode(User $user, string $code): bool` | Сверяет код и проверяет не истёк ли TTL. При успехе вызывает `markVerified()`. |
| `verifyByLink(User $user)` | Вызывает `markVerified()` напрямую (сигнатура URL уже проверена middleware `signed`). |
| `generateCode(User $user): string` | `random_int(0, 999999)` с левым паддингом до 6 цифр; сохраняет в БД с `expires_at = now()->addMinutes(30)`. |
| `markVerified(User $user)` | Сохраняет `email_verified_at = now()`, очищает код и TTL. |

#### 2.4 `EmailVerificationMail` + шаблоны

**`app/Mail/EmailVerificationMail.php`** — Mailable с тремя `public readonly` полями: `$user`, `$code`, `$verifyLink`. Тема: «Подтвердите ваш email — {app.name}».

**`resources/views/emails/verify-email.blade.php`** — HTML-письмо: стилизованный блок с кодом (dashed border, крупный шрифт, `letter-spacing`) и кнопка-ссылка «Подтвердить email».

**`resources/views/emails/verify-email-text.blade.php`** — plain-text фолбэк.

#### 2.5 `EmailVerificationController`

Новый контроллер `app/Http/Controllers/EmailVerificationController.php`:

| Метод | Маршрут | Описание |
|---|---|---|
| `show()` | `GET /email/verify` | Если уже верифицирован — редирект на дашборд. Иначе — страница верификации. |
| `verifyCode()` | `POST /email/verify` | Валидация поля `code` (size:6), вызов `verifyByCode()`, редирект на дашборд. |
| `verifyLink($id)` | `GET /email/verify/{id}` | Находит пользователя, проверяет подпись (middleware `signed`), логинит если не аутентифицирован, вызывает `verifyByLink()`. |
| `resend()` | `POST /email/verification-notification` | Повторная отправка письма, throttle `3,1`. |

#### 2.6 Страница верификации

**`resources/views/auth/verify-email.blade.php`** (extends `layouts.guest`):
- Показывает адрес почты пользователя.
- Поле ввода кода: `inputmode="numeric"`, `pattern="[0-9]{6}"`, шрифт с увеличенным `tracking`.
- Кнопка «Подтвердить».
- Разделитель «или нажмите ссылку из письма».
- Форма повторной отправки (POST `email.verification.resend`).
- Ссылка «Продолжить без подтверждения →» на дашборд.

#### 2.7 Маршруты

В `routes/web.php` добавлено 4 маршрута:

```php
// Вне auth — ссылка из письма работает в любом браузере
Route::get('/email/verify/{id}', [EmailVerificationController::class, 'verifyLink'])
    ->middleware('signed')
    ->name('email.verify.link');

// Внутри auth-группы
Route::get('/email/verify',  [EmailVerificationController::class, 'show'])->name('email.verify.notice');
Route::post('/email/verify', [EmailVerificationController::class, 'verifyCode'])->name('email.verify.code');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:3,1')->name('email.verification.resend');
```

Маршрут `email.verify.link` намеренно вынесен **за пределы** группы `auth` — чтобы пользователь мог открыть ссылку в другом браузере. Контроллер сам логинит пользователя, если сессия не активна.

#### 2.8 Интеграция в `AuthController`

- После регистрации: вызов `sendVerificationEmail($user)` → редирект на `email.verify.notice`.
- После логина: если `!$user->isEmailVerified() && !$user->isStaff()` → `sendVerificationEmail($user)` → редирект на `email.verify.notice`.
- Staff (admin, manager) **обходят** верификацию — они создаются вручную через сидер/админку.

#### 2.9 Сброс верификации при смене email

**`app/Services/ProfileService.php`** — в методе `updateProfile()` добавлена проверка: если `email` изменился — обнуляются `email_verified_at`, `email_verification_code`, `email_verification_expires_at`.

**`app/Http/Controllers/ProfileController.php`** — в методе `update()`: если email изменился — вызывает `sendVerificationEmail($user->fresh())` и редиректит на `email.verify.notice` вместо профиля.

Сервис `ProfileService` используется и в пользовательском `ProfileController`, и в `Admin\ProfileController` — сброс верификации работает для обоих.

#### 2.10 Статус верификации в профиле

**`resources/views/profile/index.blade.php`** — в начале страницы добавлена карточка-статус:
- Зелёная карточка с датой подтверждения — если email верифицирован.
- Янтарная карточка с кнопкой «Подтвердить email» (POST `email.verification.resend`) — если нет.

#### 2.11 Исправление 500 в `/admin/profile/edit`

**Проблема:** `$errors->only([...])` вызывал `Fatal Error` — метод `only()` отсутствует у `MessageBag` в этой версии Laravel.

**Исправление в `resources/views/admin/profile/edit.blade.php`:** заменён на вложенные `@foreach` с `$errors->get($field)`:

```blade
@foreach (['name', 'email'] as $field)
    @foreach ($errors->get($field) as $msg)
        <li>{{ $msg }}</li>
    @endforeach
@endforeach
```

#### 2.12 `AdminSeeder`

Все три тестовых аккаунта (`admin@example.com`, `manager@example.com`, `user@example.com`) теперь создаются с `email_verified_at = now()`, чтобы демо-логин не блокировался петлёй верификации.

---

### 3. Обновление документации (`a975c69`)

**`CLAUDE.md`:**
- Таблица переменных окружения: добавлены `YANDEX_CAPTCHA_SITEKEY` и `YANDEX_CAPTCHA_SECRET`.
- Quick start: упоминание ключей капчи в комментарии к шагу настройки `.env`.
- Нюансы и подводные камни: 5 новых пунктов (MessageBag::only, ValidationException vs abort, SmartCaptcha init, signed link вне auth, staff bypass верификации).
- Структура проекта: добавлены `EmailVerificationController`, `EmailVerificationMail`, `EmailVerificationService`, `verify-email.blade.php` (view + email), новая миграция; обновлены описания `AuthController`, `ProfileController`, `ProfileService`, `User`, `register.blade.php`, `profile/index.blade.php`, `AdminSeeder`, `web.php`.

**`README.md`:**
- Возможности: добавлен пункт «Верификация email».
- Стек: добавлена строка Yandex SmartCaptcha.
- Архитектура: исправлен список middleware в схеме (`staff / user.only / not.blocked`).
- Схема БД: убраны несуществующие таблицы (`activity_logs`, `reminders`, `user_statistics`), заменены точной таблицей реальных таблиц.
- Структура проекта: добавлены `Mail/`, `EmailVerificationController`, `EmailVerificationService`, `verify-email` views.
- Безопасность: добавлены пункты про SmartCaptcha и email верификацию.

---

## Затронутые файлы (суммарно)

```
.env.example
app/Http/Controllers/AuthController.php
app/Http/Controllers/EmailVerificationController.php   ← новый
app/Http/Controllers/ProfileController.php
app/Mail/EmailVerificationMail.php                     ← новый
app/Models/User.php
app/Services/EmailVerificationService.php              ← новый
app/Services/ProfileService.php
config/services.php
database/migrations/2026_05_13_130000_add_email_verification_code_to_users_table.php  ← новый
database/seeders/AdminSeeder.php
resources/views/admin/profile/edit.blade.php
resources/views/auth/register.blade.php
resources/views/auth/verify-email.blade.php            ← новый
resources/views/emails/verify-email-text.blade.php     ← новый
resources/views/emails/verify-email.blade.php          ← новый
resources/views/profile/index.blade.php
routes/web.php
CLAUDE.md
README.md
```
