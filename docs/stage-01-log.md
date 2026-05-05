# Этап 01 — Bootstrap проекта

## Цель
Создать с нуля Laravel 11 проект «Виртуальный наставник», настроить подключение
к MySQL, базовую структуру папок (Services, Repositories, Helpers, docs),
маршрут healthcheck и заготовку основного blade-layout. На выходе — рабочий
скелет, готовый к Этапу 02 (аутентификация и роли).

## Выполненные действия
1. Установлен Laravel 11 (`composer create-project laravel/laravel _laravel_install "11.*"`)
   во временную подпапку, потому что корневая директория содержала файл
   `CLAUDE_CODE_PROMPT.md` и `composer create-project .` отказался работать.
2. Содержимое `_laravel_install/` (включая dotfiles) перемещено в корень проекта,
   временная папка удалена. `composer create-project` автоматически выполнил
   `key:generate` (APP_KEY уже сгенерирован в `.env`).
3. Файл `.env.example` приведён к проектной конфигурации:
   `APP_NAME="Виртуальный наставник"`, `APP_TIMEZONE=Europe/Moscow`,
   `APP_LOCALE=ru`, `APP_FAKER_LOCALE=ru_RU`,
   `DB_CONNECTION=mysql`, `DB_DATABASE=virtual_mentor`, `DB_USERNAME=root`.
4. `.env` создан из шаблона + проставлены реальные креды для локального MySQL.
   `.env` исключён из git через `.gitignore`.
5. Созданы пустые директории каркаса с `.gitkeep`:
   `app/Services/`, `app/Repositories/`, `app/Helpers/`, `docs/`.
6. В `routes/web.php` добавлен маршрут `GET /healthz` (имя `healthz`),
   возвращающий JSON `{"status":"ok","app":"Виртуальный наставник"}`.
7. Создан `resources/views/layouts/app.blade.php` со всеми CDN
   (Tailwind, Alpine.js@3 defer, Chart.js@4), мета-тегами (charset, viewport,
   csrf-token), `@yield('content')` в теле и `@yield('scripts')` + `@stack('scripts')`
   перед `</body>`. Добавлен `@stack('head')` в `<head>` для будущих Chart.js inline-данных.
8. Файл `.gitignore` дополнен: `/storage/logs/*.log`, `.DS_Store`, `Thumbs.db`,
   `_laravel_install/` (страховка от случайного коммита временной папки,
   если этап будет переигран).
9. Инициализирован git-репозиторий (`git init -b main`), локально (без --global)
   проставлены `user.email`/`user.name` для возможности коммитить.
10. Сделан первый коммит `chore: bootstrap laravel project`
    (70 staged файлов; vendor/ и .env корректно проигнорированы).
11. Проверка: `php artisan route:list` показывает 4 маршрута, среди них `healthz` —
    значит роутинг и автозагрузка работают.

## Созданные файлы
- `.env` (локальные креды, в git не уходит)
- `app/Helpers/.gitkeep`
- `app/Repositories/.gitkeep`
- `app/Services/.gitkeep`
- `docs/.gitkeep`
- `docs/stage-01-log.md` (этот лог)
- `resources/views/layouts/app.blade.php`
- Полная файловая структура Laravel 11 (composer создал ~70 файлов в `app/`,
  `bootstrap/`, `config/`, `database/`, `public/`, `resources/`, `routes/`,
  `storage/`, `tests/`, плюс корневые `composer.json`, `phpunit.xml` и т.д.)

## Изменённые файлы
- `.env.example` — заменён DB_CONNECTION sqlite → mysql, добавлены
  настройки локали ru / таймзоны Москвы, имя приложения переведено.
- `.gitignore` — добавлены `/storage/logs/*.log`, `.DS_Store`, `Thumbs.db`,
  `_laravel_install/`.
- `routes/web.php` — добавлен маршрут `/healthz`.

## Краткое описание ключевых изменений в коде

**`routes/web.php`** — закрытое замыкание возвращает `response()->json([...])`.
Маршрут именован (`->name('healthz')`), чтобы при необходимости генерировать
URL через `route('healthz')` без хардкода пути. Для healthcheck-эндпоинта
не нужен контроллер — это бесполезный слой, у эндпоинта одна строка.

**`resources/views/layouts/app.blade.php`** — три CDN-скрипта подключены
именно в таком порядке:
- Tailwind CDN в `<head>` (синхронно, чтобы стили применились до первого paint).
- Alpine.js с атрибутом `defer` — обязательное требование Alpine для корректной
  инициализации `x-data` на уже распаршенном DOM.
- Chart.js — UMD-сборка из `dist/chart.umd.min.js`, корректно работает без
  ESM-импортов (важно, т.к. мы не используем bundler).

Версии CDN зафиксированы (`alpinejs@3.x.x`, `chart.js@4.4.1`) — без фиксации
обновление CDN могло бы внезапно сломать дашборд. Tailwind через CDN
не позволяет конфигурировать `tailwind.config.js`, но для MVP дипломного
проекта стандартной палитры достаточно.

**`.env.example` vs `.env`** — `.env.example` уходит в git как шаблон с пустым
`DB_PASSWORD=`, реальный пароль живёт только в локальном `.env`.
Это стандартная Laravel-практика и она же — требование безопасности
(п. «Хардкод секретов запрещён»).

## Использованные команды
```bash
composer create-project laravel/laravel _laravel_install "11.*" --no-interaction --prefer-dist
mv _laravel_install/* .   # с shopt -s dotglob для dotfiles
rmdir _laravel_install
mkdir -p docs app/Services app/Repositories app/Helpers
touch docs/.gitkeep app/Services/.gitkeep app/Repositories/.gitkeep app/Helpers/.gitkeep
mkdir -p resources/views/layouts
php artisan route:list
git init -b main
git config user.email "..."   # локально, не --global
git config user.name "..."
git add .
git commit -m "chore: bootstrap laravel project"
```

## Как поднять и проверить
```bash
# Предполагается что MySQL уже поднят и БД virtual_mentor создана через Workbench.
cp .env.example .env       # если .env ещё нет
# Заполнить DB_USERNAME / DB_PASSWORD реальными значениями
composer install            # если vendor/ отсутствует (в git его нет)
php artisan key:generate    # сгенерирует APP_KEY если пусто
php artisan serve           # http://localhost:8000
```

Smoke-чеклист:
- [ ] Открыть `http://localhost:8000/` — отображается Laravel welcome.
- [ ] Открыть `http://localhost:8000/healthz` — возвращается JSON
      `{"status":"ok","app":"Виртуальный наставник"}` с заголовком
      `Content-Type: application/json`.
- [ ] `php artisan route:list` показывает маршрут `GET healthz` без ошибок.
- [ ] Заглянуть в `app/Services/`, `app/Repositories/`, `app/Helpers/`, `docs/` —
      папки существуют (через `.gitkeep`).
- [ ] `git log --oneline` показывает один коммит
      `chore: bootstrap laravel project`.

## Notes (нетривиальные обоснования)

1. **Почему Laravel ставился в `_laravel_install/` а не сразу в корень.**
   `composer create-project laravel/laravel . "11.*"` по дизайну требует
   пустую целевую директорию. В корне уже лежал `CLAUDE_CODE_PROMPT.md` —
   мастер-промпт, который пользователь явно попросил не двигать. Установка
   в подпапку с последующим `mv` сохранила файл на месте без ручных хаков.

2. **Почему CDN, а не npm/vite — и какие риски это создаёт.**
   Это сознательный выбор из мастер-промпта: для дипломного проекта
   важна понятность сборки, а не bundler-конфиг. Платим за это тремя вещами:
   (а) полный Tailwind CDN весит больше production-билда (~3.5 MB JIT),
   (б) `tailwind.config.js` от Laravel-скаффолда не используется (создан, но не
   подключён) — трогать не стал, потому что простая опция «оставить как есть»
   ничему не мешает, (в) первый paint медленнее. Для MVP всё это приемлемо.

3. **Почему `notifications`, `migrations` и Laravel-сессии оставлены как есть
   до Этапа 02.** В стандартном `php artisan migrate` уже зашиты `users`,
   `cache`, `jobs`, `sessions` миграции. На Этапе 01 их не запускал —
   потому что в этом этапе вообще не требуется работающий DB-коннект,
   а запуск миграций без работающего MySQL приведёт к ошибке и спутает диагностику.
   Миграции запустим в Этапе 02 одним прогоном вместе с `add_role_to_users`.

4. **Почему Alpine.js фиксирован на `@3.x.x` а не на конкретный патч.**
   Alpine v3 семантически стабилен, минорки не ломают API. Жёстко прибивать
   `3.13.5` создаёт фальшивое чувство контроля — если Alpine удалят с CDN,
   падают все версии одинаково. Зафиксированный мажор + плавающий патч —
   разумный компромисс. Chart.js же зафиксирован полностью (`4.4.1`),
   потому что у v4 был breaking change по сравнению с v3, и я хочу
   гарантировать что код, написанный на Этапе 06, не сломается при обновлении.

5. **Почему `_laravel_install/` оставлен в `.gitignore` хотя папки уже нет.**
   Это страховка: если этап будет переигран (например, разработчик удалит
   проект и начнёт заново по этому же мастер-промпту) — временная папка
   автоматически не попадёт в коммит. Стоимость одной строки в gitignore
   нулевая, риск случайного коммита 200 МБ vendor — реальный.
