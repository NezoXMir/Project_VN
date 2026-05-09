# Этап 09 — Напоминания и in-app уведомления

## Цель

Дать пользователю два канала уведомлений о его целях:
- **In-app** — bell-иконка с dropdown в шапке дашборда, счётчик
  непрочитанных, кнопки «прочитать одно / все».
- **Email** — ежедневное письмо в 9:00 (cron) с просроченными и
  приближающимися дедлайнами. Можно отключить чекбоксом в
  профиле.

Оба канала питаются от одного источника — `DailyReminder`
notification, которая через стандартный Laravel-механизм
пишется в БД и/или отправляется по почте в зависимости от
настроек пользователя.

## Архитектура

```
┌────────────────────────────────────────────────────────┐
│ Cron / `php artisan schedule:run`                      │
│   → ежедневно в 09:00                                  │
└──────────────────────┬─────────────────────────────────┘
                       │
                       ▼
┌────────────────────────────────────────────────────────┐
│ Console\Commands\SendDailyReminders                    │
│   for each User in chunks of 100:                      │
│     ReminderService::buildSummary(userId)              │
│       → null  ⇒ skip                                   │
│       → array ⇒ $user->notify(new DailyReminder(...))  │
└──────────────────────┬─────────────────────────────────┘
                       │
                       ▼
┌────────────────────────────────────────────────────────┐
│ DailyReminder::via($user)                              │
│   → ['database', 'mail']  если email_reminders_enabled │
│   → ['database']           иначе                       │
└──────┬─────────────────────────────────────┬───────────┘
       │                                     │
       ▼                                     ▼
┌──────────────────┐                ┌──────────────────────┐
│ database channel │                │ mail channel         │
│  → notifications │                │  → emails/daily-     │
│    table         │                │     reminder.blade   │
│  → bell на /     │                │    + .text-blade     │
│    dashboard     │                │  → MAIL_MAILER       │
└──────────────────┘                └──────────────────────┘
```

## Выполненные действия

1. Миграция `2026_05_09_150024_create_notifications_table` —
   стандартная Laravel-таблица из `php artisan notifications:table`
   (uuid id, type, notifiable_type/id, data json, read_at,
   timestamps).
2. Миграция `add_email_reminders_enabled_to_users_table` —
   `boolean default true` после `bio`.
3. `app/Models/User.php`: добавлено `email_reminders_enabled`
   в `$fillable` и в casts (boolean). `Notifiable` trait уже
   подключён по умолчанию.
4. `app/Services/ReminderService.php` —
   `buildSummary(userId): ?array`. Возвращает
   `['overdue' => [...], 'upcoming' => [...]]` или `null`
   если у пользователя нет активных целей с дедлайнами в
   зоне внимания (не шлём пустых писем).
5. `app/Notifications/DailyReminder.php`:
   - via() условно (`database` всегда, `mail` если включено).
   - toMail() возвращает `MailMessage` с явным view
     `emails.daily-reminder` (HTML) + `emails.daily-reminder-text`
     (plain). **Не используем цепочку `->line()`** (см. Notes #1).
   - toArray() для bell — содержит overdue/upcoming в JSON.
6. `resources/views/emails/daily-reminder.blade.php` —
   HTML-шаблон письма с inline-стилями.
7. `resources/views/emails/daily-reminder-text.blade.php` —
   plain-text версия (для email-клиентов с отключённым HTML).
8. `app/Console/Commands/SendDailyReminders.php` — команда
   `reminders:send`. Идёт по `User::chunk(100, ...)`, для
   каждого вызывает `buildSummary` и `notify`. Печатает
   итог: «Отправлено: X, пропущено: Y».
9. `routes/console.php` —
   `Schedule::command('reminders:send')->dailyAt('09:00')->timezone(config('app.timezone'))`.
10. `app/Http/Controllers/NotificationController.php` —
    `markRead($id)` и `markAllRead()`, оба JSON.
11. Маршруты `/notifications/{id}/read` (POST) и
    `/notifications/read-all` (POST) под `auth`.
12. В `DashboardController` подгружаются последние 10 unread
    и передаются во вью.
13. `resources/views/dashboard.blade.php`:
    - В шапке между «Достижения» и админ-кнопкой — bell-иконка.
      Бейдж с числом unread, dropdown по клику, список
      уведомлений, кнопка «Прочитать все», кнопка × на каждом.
      Закрывается по клику снаружи через `@click.outside`.
    - JS-компонент `notificationsBell(initialCount, initialItems)`
      в `@push('scripts')`. Хелпер `req(url)` оборачивает fetch
      с CSRF и `X-Requested-With`.
14. `app/Http/Requests/ProfileUpdateRequest.php` — добавлено
    правило `email_reminders_enabled` (nullable, boolean).
    `prepareForValidation` нормализует чекбокс
    (отсутствие в payload означает `false`).
15. `app/Services/ProfileService.php` — `email_reminders_enabled`
    добавлено в `fill()`.
16. `app/Http/Controllers/ProfileController.php` — поле
    добавлено в `safe()->only([...])`.
17. `resources/views/profile/index.blade.php` — чекбокс
    «Получать ежедневные email-напоминания о дедлайнах»
    под bio в секции «Основные данные», с пояснением что
    bell-уведомления остаются всегда.

## Как настроить SMTP

По умолчанию `MAIL_MAILER=log` в `.env` — все письма пишутся
в `storage/logs/laravel.log` и не уходят наружу. Это удобно
для разработки.

Для реальной отправки укажи в `.env`:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.yourprovider.ru
MAIL_PORT=587
MAIL_USERNAME=your@email.ru
MAIL_PASSWORD=your-app-password-here
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="noreply@yourdomain.ru"
MAIL_FROM_NAME="${APP_NAME}"
```

После правки `.env` — `php artisan config:clear` и `php artisan
serve` (или просто перезапуск). Проверить можно командой:

```bash
php artisan reminders:send
```

И посмотреть либо в почту, либо (для log-драйвера) в
`storage/logs/laravel.log`.

## Запуск scheduler в production

Добавить в crontab сервера:

```cron
* * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
```

Этот единый «cron-tick» каждую минуту проверяет, не пора
ли запустить какую-то scheduled-команду. Наша
`reminders:send` сработает в 9:00 по `Europe/Moscow`.

## Созданные файлы

- `app/Console/Commands/SendDailyReminders.php`
- `app/Http/Controllers/NotificationController.php`
- `app/Notifications/DailyReminder.php`
- `app/Services/ReminderService.php`
- `database/migrations/2026_05_09_150024_create_notifications_table.php`
- `database/migrations/2026_05_09_150025_add_email_reminders_enabled_to_users_table.php`
- `resources/views/emails/daily-reminder.blade.php`
- `resources/views/emails/daily-reminder-text.blade.php`
- `docs/stage-09-log.md` (этот файл)

## Изменённые файлы

- `app/Http/Controllers/DashboardController.php` — передача
  unread notifications во вью.
- `app/Http/Controllers/ProfileController.php` —
  `email_reminders_enabled` в `safe()`.
- `app/Http/Requests/ProfileUpdateRequest.php` — правило +
  `prepareForValidation` для чекбокса.
- `app/Models/User.php` — fillable + cast email_reminders_enabled.
- `app/Services/ProfileService.php` — поле в fill().
- `resources/views/dashboard.blade.php` — bell-блок + JS.
- `resources/views/profile/index.blade.php` — чекбокс.
- `routes/console.php` — Schedule::command.
- `routes/web.php` — два POST-маршрута для notifications.
- `README.md` — Этап 09 отмечен как выполненный.

## Краткое описание ключевых решений

**Один Notification-класс на оба канала.** Альтернатива —
отдельная Mailable и отдельная сущность для in-app. Минусы:
два места поддержки одного контента, рассинхрон при правках.
Laravel изначально проектирует Notifications как
multi-channel, переключение через `via()` возвращающее массив.

**`view()` вместо `->line()` для email-шаблона.** Цепочка
`->line('...')` рендерится через `markdown()` →
`league/commonmark` → требует `mb_strcut()`. На некоторых
Windows-сборках PHP функция отсутствует (`function_exists` =
false). Свой Blade-шаблон обходит markdown-рендер целиком,
работает где угодно. Бонусом — больше контроля над HTML.

**`buildSummary()` возвращает null на пустоте.** Альтернатива —
всегда возвращать пустой массив и слать письма «у вас всё в
порядке, дедлайнов нет». Минус — спам. Молчание = всё
хорошо, не отвлекаем пользователя.

**Команда не пишет ничего в БД сама — делегирует Notifications.**
Альтернатива — `Notification::create([...])` напрямую в
команде. Минус — теряем многоканальность Laravel, надо
самим разбираться с email. С `$user->notify(new DailyReminder)`
Laravel сам кладёт в БД (`database` channel) и шлёт письмо
(`mail` channel) — наш код не знает деталей.

**email_reminders_enabled по дефолту true.** Альтернатива —
opt-in (по умолчанию выключено). Спорное решение в
эпоху GDPR, но: (а) пользователь явно пришёл в систему-наставник,
ожидает напоминаний; (б) отключить можно одним кликом в
профиле. Этап 12 (Demo API) при необходимости добавит
явное согласие при регистрации.

**Bell-уведомления показываем только на дашборде.** Чтобы
видеть bell на любой странице, нужен глобальный layout-компонент
с view composer'ом, который тащит unread на каждый рендер.
Для дипломного проекта это перебор — пользователь и так
заходит на дашборд при логине.

## Использованные команды

```bash
php artisan notifications:table
php artisan make:migration add_email_reminders_enabled_to_users_table --table=users
php artisan migrate

php artisan make:notification DailyReminder
php artisan make:command SendDailyReminders

# (тела файлов — вручную через Write)

# Тест
php artisan reminders:send

# Просмотр расписания (увидим reminders:send в 9:00)
php artisan schedule:list
```

## Smoke-чеклист

- [x] **`function_exists('mb_strcut')`** на машине разработки
      → `NO`. Это и подтолкнуло к view-based email вместо
      markdown.
- [x] **ReminderService::buildSummary** на чистом юзере
      → `null` (нет ничего, не шлём).
- [x] Создать просроченную + приближающуюся цели → buildSummary
      возвращает массив с обеими.
- [x] **`php artisan reminders:send`** → «Отправлено: 3,
      пропущено: 2». В БД создалось уведомление, в
      `laravel.log` — отрендеренный HTML письма
      («Здравствуйте, Мирослав!»).
- [x] **GET /dashboard** под user → 200, bell-блок
      рендерится с counter unread.
- [x] **POST /notifications/read-all** через AJAX → 200,
      `{"ok":true}`. Перепроверка БД → unread = 0.
- [x] **`php artisan schedule:list`** показывает
      `reminders:send` daily at 09:00 (Europe/Moscow).

В браузере (вручную):

- [ ] На дашборде кликнуть bell → раскрывается dropdown,
      виден список уведомлений с датами.
- [ ] Кнопка «Прочитать все» → счётчик обнулился, dropdown
      опустел.
- [ ] В профиле снять чекбокс «Получать email-напоминания»
      → сохранить → запустить `reminders:send` повторно →
      bell-уведомление пришло, в `laravel.log` нового письма
      нет (для этого юзера).

## Notes (нетривиальные обоснования)

1. **Почему `view()`-shortcut с массивом
   `['emails.daily-reminder', 'emails.daily-reminder-text']`,
   а не отдельные `->view('html')->text('text')`.** Laravel
   `MailMessage::view()` принимает либо строку (HTML), либо
   массив `[html, text]`. Это короче и явно показывает связь
   двух шаблонов. Альтернатива через `->html()` и `->text()`
   методы Mailable у нас не работает, потому что мы используем
   `MailMessage` (упрощённая обёртка). Если в будущем понадобится
   полноценный `Mailable`, переключим — пока этого хватает.

2. **Почему `prepareForValidation` для checkbox, а не правило
   `accepted_if`.** HTML checkbox не отправляет поле когда
   не отмечен (вместо `false` его просто нет в payload).
   `nullable + boolean` без preprocessing считает отсутствующее
   поле как `null`, не `false` — итого
   `(bool) ($data['email_reminders_enabled'] ?? false)` в
   сервисе ломается на edge cases. `prepareForValidation` с
   `$this->boolean(...)` гарантирует приведение к `false`.

3. **Почему уведомление в БД создаётся даже когда mail-канал
   выключен.** Это сознательно: bell — внутренний канал,
   не связанный с почтой. Юзер мог отключить почту чтобы
   не спамили, но всё равно хочет видеть напоминания в
   приложении. Поэтому `via()` всегда содержит `'database'`,
   а `'mail'` только условно.

4. **Почему `chunk(100)` в команде, а не `each()` или `all()->each()`.**
   `chunk` загружает по 100 пользователей за раз — на
   большой базе (10k+ юзеров) `all()` съест RAM. Для дипломного
   проекта с десятком юзеров разницы нет, но привычка важна.

5. **Почему scheduler регистрируется в `routes/console.php`,
   а не в `app/Console/Kernel.php` (как в Laravel 10).**
   Laravel 11 убрал `Console\Kernel` — расписание объявляется
   декларативно в `routes/console.php` через
   фасад `Schedule::command(...)`. Это чище и поощряет
   функциональный стиль.

6. **Почему текстовая версия письма обязательна, а не
   только HTML.** Многие email-клиенты (особенно корпоративные)
   рендерят plain-text, если он есть, или показывают
   HTML «грязно». Spam-фильтры тоже более лояльны к письмам
   с обоими частями. Стоимость — один блейд-файл.
