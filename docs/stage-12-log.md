# Этап 12 — Demo API, тесты, финальная проверка (итог проекта)

## Цель

Завершить проект тремя последними шагами:
1. **Demo REST API** — 4 endpoint'а, демонстрирующие интеграционный
   контракт. Используют те же сессии, что и веб-интерфейс
   (без JWT — это демонстрация, не публичное API).
2. **Feature-тесты** — минимальный набор регрессионных тестов
   на критичные сценарии (auth, goals, tasks, admin).
3. **CLAUDE.md** — инструкция разработчика в корне проекта.
4. **Полный smoke** по 10 сценариям из мастер-промпта.

После Этапа 12 дорожная карта проекта закрыта полностью.

## Demo REST API

`app/Http/Controllers/Api/GoalApiController.php` — 4 эндпоинта,
все JSON, под `middleware('auth')`:

| Метод | Маршрут | Описание |
|-------|---------|----------|
| GET | `/api/goals` | Список целей текущего пользователя с категорией и прогрессом |
| POST | `/api/goals` | Создать цель (валидация через `GoalRequest`) |
| GET | `/api/goals/{id}` | Детали + подцели + задачи. Чужая → 403 (GoalPolicy) |
| GET | `/api/user/stats` | KPI-срез из `StatsService::dashboard()` |

**Сессионная авторизация, не JWT.** Промпт явно требует «те же
сессии, что и веб-интерфейс». Для дипломного проекта это
оправдано — никакого отдельного механизма для API мы не
строим. Если в будущем нужно будет интегрироваться с мобильным
клиентом — переходим на Sanctum + bearer tokens.

**JSON-сериализация в приватных методах контроллера**
(`serializeGoal`, `serializeGoalWithSubtasks`). Альтернатива —
Laravel API Resources. Для 2 ответов это overkill; если бы
эндпоинтов было 10+, выделили бы в `app/Http/Resources/`.

Smoke API:
- `/api/goals` → JSON со списком ✓
- `/api/goals/{own}` → 200, со subtasks/tasks ✓
- `/api/goals/{other}` → 403 (Policy блокирует) ✓
- `/api/user/stats` → 200, KPI ✓
- Гость → 302 на login ✓

## Feature-тесты

`phpunit.xml` сконфигурирован на отдельную MySQL-базу
`virtual_mentor_test` — sqlite не подошёл из-за `enum`-полей и
`FIELD()` в наших миграциях/запросах.

| Тест-класс | Сценариев | Что проверяет |
|------------|-----------|---------------|
| `AuthTest` | 6 | guest → /login, register с валидацией пароля, login OK/wrong, logout |
| `GoalTest` | 6 | create + валидация, edit, чужая → 403, archive/complete |
| `TaskTest` | 4 | create через AJAX, toggle on/off (включая `goal_progress` в ответе), чужая → 403 |
| `AdminTest` | 5 | guest → 302, user → 403, admin OK, смена роли, защита последнего admin |

**Все 21 тест проходят** (53 assertion'а), `Duration: 3.48s`.

Каждый тест использует `RefreshDatabase` — миграции прогоняются
с нуля для изоляции. Это медленнее чем транзакции, но надёжнее
для интеграционных проверок.

**Удалили дефолтные `tests/Feature/ExampleTest.php` и
`tests/Unit/ExampleTest.php`** от Laravel-генератора — они
ожидают, что `/` возвращает 200, но у нас он редиректит на
`/dashboard` или `/login`.

## CLAUDE.md

Корневой файл инструкции для разработчика. Покрывает:
- Стек и быстрый старт (clone → composer → migrate → serve)
- Тестовые аккаунты (`admin@example.com` / `user@example.com`,
  пароль `password`)
- Таблица env-переменных (DB, MAIL)
- Artisan-команды (`reminders:send`, `migrate:fresh`, `test`)
- Архитектура (Controller → Service → Repository → Model)
- Подводные камни:
  - `mb_strcut` deprecated в PHP 8.5 → email-шаблоны через
    `view()` напрямую
  - `MAIL_MAILER=log` по умолчанию для dev
  - Mailtrap free rate-limit
  - Системные категории в миграции, не в seeder
  - PDF-экспорт отложен (см. соответствующий лог)
  - Авторизация на сервис-уровне (а не controller-уровне)

## Полный smoke (10 сценариев из мастер-промпта)

Все 10 сценариев пройдены автоматизированно через curl + php -r:

| # | Сценарий | Результат |
|---|----------|-----------|
| 1 | Дашборд `/dashboard` рендерится | ✅ 200, ключевые секции |
| 2 | Создание цели в 3 системных категориях | ✅ 302 × 3 |
| 3 | Подцель + задача + AJAX toggle → progress 100% | ✅ |
| 4 | `/goals` список | ✅ 200 |
| 5 | `/achievements` с прогрессом | ✅ 200 |
| 6 | `reminders:send` → письмо в Mailtrap | ✅ Отправлено 1, ошибок 0 |
| 7 | `GET /api/goals` → JSON | ✅ 6 целей в `data` |
| 8 | `GET /api/user/stats` → JSON | ✅ active_goals + streak |
| 9 | Admin `/admin/users` | ✅ 200 |
| 10 | Несуществующий URL | ✅ 404 |

## Созданные файлы

- `app/Http/Controllers/Api/GoalApiController.php`
- `tests/Feature/AuthTest.php`
- `tests/Feature/GoalTest.php`
- `tests/Feature/TaskTest.php`
- `tests/Feature/AdminTest.php`
- `CLAUDE.md` (корень проекта)
- `docs/stage-12-log.md` (этот файл)

## Изменённые файлы

- `routes/web.php` — импорт `GoalApiController` + 4 маршрута
  под префиксом `/api`
- `phpunit.xml` — `DB_CONNECTION=mysql`, `DB_DATABASE=virtual_mentor_test`,
  убран комментарий с sqlite (он не подходит для проекта)
- `README.md` — Этап 12 отмечен как выполненный

## Удалённые файлы

- `tests/Feature/ExampleTest.php` — дефолт от Laravel, не
  применим
- `tests/Unit/ExampleTest.php` — то же

---

# Итоги всего проекта

## Сделано — 12 этапов + 2 доп.

| Этап | Что | Документация |
|------|-----|--------------|
| 01 | Bootstrap (Laravel + MySQL + healthz + layout) | `stage-01-log.md` |
| 02 | Аутентификация + RBAC | `stage-02-log.md` |
| 03 | Управление пользователями (Admin) | `stage-03-log.md` |
| 04 | Goals CRUD + категории + личная палитра + DateHelper | `stage-04-log.md` |
| 05 | Подцели и задачи через AJAX | `stage-05-log.md` |
| 06 | Дашборд: KPI + график + дедлайны + рекомендации | `stage-06-log.md` |
| 06 (доп.) | Админ-дашборд | `stage-06-admin-log.md` |
| 06 (доп.) | Профиль (аватар, bio, смена пароля, удаление) | `stage-06-profile-log.md` |
| 07 | Rule-based рекомендации (7 правил) | `stage-07-log.md` |
| 08 | Достижения и геймификация (8 бейджей) | `stage-08-log.md` |
| 09 | In-app + email-напоминания (cron + bell) | `stage-09-log.md` |
| 10 | UI Polish: 5 Blade-компонентов | `stage-10-log.md` |
| 11 | Рефакторинг: репозитории + политики + helpers | `stage-11-log.md` |
| 12 | Demo API + тесты + CLAUDE.md | `stage-12-log.md` |

## Финальная статистика

**Кодовая база:**
- 11 доменных таблиц БД (users, categories, goals, subtasks,
  tasks, achievements, user_achievements, notifications,
  password_reset_tokens, sessions, jobs)
- ~7 моделей Eloquent
- 4 репозитория
- 9 сервисов + 4 политики + 2 хелпера
- 13 контроллеров (включая admin и api)
- ~25 Blade-вьюх + 5 компонентов

**Качество:**
- 21 feature-тест (53 assertion'а), все проходят
- Авторизация через Laravel Policies (auto-discovered)
- Чёткая трёхслойная архитектура (Controller → Service → Repository)
- DRY: streak-логика, email-шаблоны, UI-компоненты —
  каждое в одном месте
- Логи к каждому этапу с обоснованием решений

**UX:**
- Bell-уведомления с unread-счётчиком
- Toast-уведомления при разблокировке достижений
- Live-превью аватара перед загрузкой
- Цветовая градация дедлайнов (red / amber / gray)
- Прогресс-бары везде где есть прогресс
- Empty-states для каждого пустого блока
- AJAX-операции с задачами (без перезагрузки)

**Best practices применены:**
- Form Request классы для валидации
- Eager loading везде где нужно (`with()`)
- Cascading FKs для целостности
- Indexes на горячих полях
- Сессионная безопасность (regenerate, invalidate)
- CSRF на всех формах + AJAX
- Без сырого SQL, только Eloquent
- Bcrypt для паролей (Laravel default)
- 404 для несуществующего, 403 для чужого (защита от
  user-enumeration)

## Что НЕ сделано (намеренно или отложено)

- **PDF-экспорт админ-отчёта** — отложен (3 подхода
  пробовали, ни один не дошёл до production-качества).
  См. `stage-06-admin-log.md`. На данный момент админ
  смотрит статистику прямо на странице.
- **Email verification при регистрации** — намеренно нет.
  Проект локальный, верификация требует SMTP. Если нужно —
  добавить `MustVerifyEmail` интерфейс на User, плюс
  middleware `verified`.
- **2FA** — out of scope для дипломки.
- **Public API с JWT** — Demo API использует session-cookie,
  что заявлено явно. Для мобильного клиента можно подключить
  Sanctum.
- **Drag-and-drop сортировка подцелей/задач** — поле `position`
  есть в схеме, готов для DnD. UI можно доделать
  отдельным шагом.
- **Тёмная тема** — обсуждалась в Этапе 10, отложена.
  Tailwind поддерживает `darkMode: 'class'`, можно подключить
  через переключатель в шапке.

## Следующие шаги (для будущих итераций)

Если проект пойдёт развиваться дальше:
1. PDF-экспорт через Browserless / spatie/browsershot
2. Real-time уведомления через Laravel Reverb (websockets)
3. API на Sanctum для мобилы
4. DnD сортировка через `Sortable.js`
5. i18n (английский / другие языки)
6. Soft delete для целей с возможностью восстановления
7. Sharing celей между пользователями (опционально, требует
   пересмотра privacy-promise)

## Notes (нетривиальные обоснования финального этапа)

1. **Почему JSON serialization в приватных методах контроллера,
   а не Laravel API Resources.** Resources хороши когда у вас
   много endpoint'ов с похожей структурой и нужны versioned API
   responses. У нас 4 endpoint'а с двумя формами сериализации
   (с подцелями и без). Простые приватные методы читаются
   быстрее и не вводят лишний слой.

2. **Почему `RefreshDatabase` а не `DatabaseTransactions`.**
   Transactions быстрее (rollback вместо migrate), но не
   откатывают auto-increment'ы и не работают с `DDL`-операциями
   внутри тестов. RefreshDatabase надёжнее — каждый тест с
   нуля. На 21 тесте 3.5 секунды — приемлемо.

3. **Почему явная test DB, а не sqlite `:memory:`.** Несколько
   миграций используют MySQL-специфику:
   - `enum('user', 'admin')` в users
   - `FIELD(status, 'active', 'completed', 'archived')` в
     `Goal::listForUser`
   - `selectRaw('DATE(completed_at)')` в StreakCalculator
   Sqlite на этом упадёт. Отдельная MySQL-база `virtual_mentor_test`
   — самый надёжный путь, цена — пользователь должен раз создать
   её через `CREATE DATABASE`.

4. **Почему mbstring extension upgrade документирован в CLAUDE.md.**
   Windows-сборка PHP по умолчанию выключает mbstring. PHPUnit
   при запуске проверяет `extension_loaded('mbstring')` напрямую,
   и symfony-polyfill ему не помогает (полифил регистрирует
   функции, но не extension). Документация упоминает эту
   гриблю — будущему разработчику не придётся отлаживать.

5. **Почему 21 тест, а не 50+.** Промпт требует
   "минимальные feature-тесты" — критичные сценарии. Полный
   coverage потребует unit-тестов на каждый сервис/политику/
   репо, что для дипломной работы избыточно. 21 теста хватает
   для регрессионной защиты ключевых путей: auth, CRUD целей,
   AJAX задач, авторизация админа.

6. **Почему API endpoints под `/api/` префиксом, но в
   `routes/web.php`, а не `routes/api.php`.** Используем
   session-cookie auth, а это `web` middleware группа.
   `routes/api.php` подключается без сессий и CSRF — нам
   это не подходит.

7. **Почему миграция `migrate:fresh --seed` не запускалась
   на проверке.** Команда уничтожает реальные данные
   пользователя (его «Диплом», «Футбол», «Вакансии»).
   Документировано в CLAUDE.md как опция для setup'а с нуля,
   но для финальной проверки полагаемся на feature-тесты с
   их собственной БД и smoke на реальной.
