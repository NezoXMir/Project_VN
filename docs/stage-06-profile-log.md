# Этап 06 (доп. — профиль) — Личный кабинет пользователя

## Цель

Дать каждому пользователю отдельную страницу `/profile` для
управления аккаунтом: загрузка аватара, изменение имени, email,
короткой подписи (bio), смена пароля и безопасное удаление
аккаунта. Базовый набор функций — расширения (тема, default-категория,
часовой пояс, email-уведомления) подъезжают вместе с Этапами 09/10
без необходимости ломать структуру профиля.

## Выполненные действия

### A. Схема БД

1. Миграция
   `database/migrations/2026_05_08_230959_add_avatar_and_bio_to_users_table.php`:
   - `avatar_path string nullable` — относительный путь файла на
     public-диске. Текстовое поле, не FK на отдельную таблицу
     «изображений», потому что аватар привязан 1-к-1 к юзеру и
     в БД хранить большие файлы дороже, чем имя файла на диске.
   - `bio string(500) nullable` — короткая подпись о себе.
     Текст-поле в 500 символов: достаточно для абзаца, но не
     даёт превратить профиль в блог.
2. Выполнено `php artisan storage:link` — создан симлинк
   `public/storage → storage/app/public`, чтобы загруженные
   аватары были доступны браузеру через `/storage/avatars/file.png`.

### B. Расширение модели User

3. В `app/Models/User.php`:
   - В `$fillable` добавлены `avatar_path` и `bio`.
   - Метод `avatarUrl(): ?string` — возвращает относительный
     URL аватара (`/storage/...`) или `null`, если не загружен.
     **Намеренно не используется `Storage::url()`** (см. Notes #1).
   - Метод `initial(): string` — первая буква имени для
     fallback-кружка с инициалами. На пустом name возвращает «?».

### C. ProfileService

4. Создан `app/Services/ProfileService.php`:
   - **`updateProfile(User, array $data, ?UploadedFile $avatar)`**
     — обновляет name/email/bio. При наличии нового аватара:
     удаляет старый файл (если был), кладёт новый под именем
     `avatars/u{user_id}_{timestamp}.{ext}`. Имя файла
     детерминированное и не зависит от оригинального имени
     загружаемого файла (которое может содержать кириллицу
     или пробелы — см. Notes #2).
   - **`changePassword(User, $current, $new)`** — проверяет
     текущий пароль через `Hash::check`, при несовпадении
     бросает `DomainException`. При успехе сохраняет
     `Hash::make($new)`.
   - **`deleteAccount(User, $password)`** — проверяет пароль,
     удаляет файл аватара вручную, делает logout с инвалидацией
     сессии и regen CSRF-токена, потом `$user->delete()`.
     Категории/цели/подцели/задачи уходят каскадом через
     FK CASCADE из миграций предыдущих этапов.

### D. Контроллер + Form Requests

5. Создан `app/Http/Requests/ProfileUpdateRequest.php`:
   - `name`: required, string, max:120
   - `email`: required, email, max:160, unique (с исключением
     текущего пользователя через `Rule::unique(...)->ignore($id)`)
   - `bio`: nullable, string, max:500
   - `avatar`: nullable, **image**, **mimes:jpeg,jpg,png,webp**,
     max:2048 (в KB → 2 МБ)
   - Все сообщения и атрибуты на русском.
6. Создан `app/Http/Requests/PasswordChangeRequest.php`:
   - `current_password`: required, string
   - `password`: required, string, min:8, confirmed
   - Поле `password_confirmation` подставляется автоматически
     Laravel-валидацией (не требует отдельного правила).
7. Создан `app/Http/Controllers/ProfileController.php`:
   `index/update/updatePassword/destroy`. Контроллер тонкий,
   делегирует в `ProfileService`. `destroy` валидирует поле
   `confirm_password` (а не `password`), чтобы ошибки одной
   формы не пересекались с ошибками формы смены пароля
   (см. Notes #3).

### E. Маршруты

8. В `routes/web.php`, под уже существующим `middleware('auth')`:
   - `GET    /profile` → `profile.index`
   - `PATCH  /profile` → `profile.update`
   - `PATCH  /profile/password` → `profile.password`
   - `DELETE /profile` → `profile.destroy`

### F. Вью `/profile`

9. `resources/views/profile/index.blade.php` — три секции в
   отдельных карточках:
   - **«Основные данные»**: круглый аватар (или инициалы) +
     file input + name + email + bio + кнопка «Сохранить».
     Live-превью аватара через Alpine.js: `x-data="{ preview, onPick }"`,
     при выборе файла создаётся `URL.createObjectURL(file)`
     для отображения превью **до** отправки формы.
   - **«Смена пароля»**: current + new + confirm. Отдельная
     форма с собственной обработкой ошибок (`$errors->hasAny(['current_password', 'password'])`).
   - **«Опасная зона»**: красная карточка с кнопкой «Удалить
     аккаунт». При клике через Alpine `x-show` разворачивается
     форма с подтверждением паролем + двойная страховка через
     `onsubmit="return confirm(...)"`.
10. Ошибки в форме профиля собираются по соответствующим
    полям через `$errors->hasAny([...])` + цикл `@foreach` по
    нужным именам — без именованных bag'ов, минимально.

### G. Навигация

11. В шапке `dashboard.blade.php` добавлен круглый аватар-кнопка
    (40px) → `/profile`. Если аватар не загружен — показывает
    инициал. Hover даёт ring-эффект для визуального feedback.

## Созданные файлы

- `app/Http/Controllers/ProfileController.php`
- `app/Http/Requests/PasswordChangeRequest.php`
- `app/Http/Requests/ProfileUpdateRequest.php`
- `app/Services/ProfileService.php`
- `database/migrations/2026_05_08_230959_add_avatar_and_bio_to_users_table.php`
- `resources/views/profile/index.blade.php`
- `docs/stage-06-profile-log.md` (этот файл)
- `public/storage` (симлинк через `php artisan storage:link`)

## Изменённые файлы

- `app/Models/User.php` — `avatar_path` и `bio` в `$fillable`,
  методы `avatarUrl()` и `initial()`.
- `routes/web.php` — импорт `ProfileController`, четыре
  маршрута под `middleware('auth')`.
- `resources/views/dashboard.blade.php` — кружок аватара/инициалов
  → `/profile` в шапке.
- `README.md` — отмечен «Этап 06 (доп. — профиль)» как
  выполненный.

## Краткое описание ключевых решений

**Хранение аватара — текстовый путь, не таблица.** Альтернатива —
`avatars` таблица с `id`, `user_id`, `path`, `created_at` для
истории. Минусы: usable только если нужна история (которой нет
по дизайну), один JOIN на каждый рендер карточки в шапке. С
полем `avatar_path` напрямую на `users` — ноль JOIN'ов и
максимум один файл на пользователя.

**Имя файла строится из `user_id + timestamp`.** Альтернативы —
`uuid`, hash от содержимого, оригинальное имя загруженного
файла. `user_id + timestamp` даёт два преимущества:
(а) известно, чей это файл, можно сразу зачищать «потерянные»
(не привязанные к никому) грайдером;
(б) timestamp гарантирует уникальность даже при многократной
загрузке одного и того же исходного файла. Минус — две
загрузки в одну секунду от того же юзера дадут одинаковое
имя, но это race condition в одну секунду — ничтожный для
ручного UX.

**Удаление старого файла при замене.** Без этого диск растёт,
накапливая «забытые» аватары при каждой смене. Через
`Storage::disk('public')->delete($user->avatar_path)` снимается
старый перед сохранением нового.

**Удаление аккаунта — через подтверждение паролем.** Альтернатива
— ввести email или специальную фразу «УДАЛИТЬ». Пароль —
самый сильный сигнал владения аккаунтом, плюс обычный паттерн в
большинстве сервисов. Дополнительный `confirm()` в JS — это
UX-страховка против случайного клика.

**Live-превью аватара через `URL.createObjectURL`.** Браузерный
API создаёт временный `blob:` URL из `File` объекта — никаких
загрузок, никакого FileReader с `result` обратными вызовами.
Превью обновляется мгновенно при выборе файла. URL живёт до
закрытия страницы; формальный `URL.revokeObjectURL` на разгрузку
страницы — overkill для дипломного проекта (браузер сам чистит
при unload).

## Использованные команды

```bash
php artisan make:migration add_avatar_and_bio_to_users_table --table=users
# (тело миграции — вручную через Write)

php artisan migrate
php artisan storage:link

# Сервисы/контроллеры/реквесты/вью — вручную через Write

php artisan route:list | grep profile  # проверка
```

## Smoke-проверки (выполнены через curl)

- [x] **Authorization:**
      - guest → 302 на `/login`
      - user → 200, видны все три секции
- [x] **PATCH /profile** (ASCII name + bio) → 302 + БД обновлена.
      (Кириллица из консоли Windows ломается на CP1251 → 1366
      Incorrect string value, в браузере UTF-8 — без проблем).
- [x] **Avatar upload** (PNG → multipart/form-data) → 302, файл
      сохранён в `storage/app/public/avatars/u{id}_{ts}.png`,
      запись в БД на `avatar_path`.
- [x] **PATCH /profile/password** (неверный текущий) → back с
      ошибкой «Текущий пароль указан неверно.»
- [x] **PATCH /profile/password** (корректный) → 302, пароль
      обновился (восстановлен в smoke ради чистоты).
- [x] **Direct GET /storage/avatars/...** → 200, image/png,
      symlink работает.

В браузере (вручную):
- [x] Загрузить аватар на `/profile` → live-превью обновляется
      сразу при выборе файла, без перезагрузки.
- [x] После сохранения — кружок в шапке дашборда показывает
      загруженный аватар.
- [x] При отсутствии аватара — кружок показывает первую букву
      имени.
- [x] Кнопка «Удалить аккаунт» разворачивает форму с password
      confirm.

## Notes (нетривиальные обоснования)

1. **Почему `avatarUrl()` возвращает относительный путь
   `/storage/...`, а не результат `Storage::disk('public')->url()`.**
   `Storage::url()` собирает абсолютный URL из `APP_URL`,
   взятого из `.env`. Если разработчик запускает `php artisan serve`
   на порту 8000, а в `.env` стоит `APP_URL=http://localhost`
   (без порта или с другим портом) — браузер получает 404 на
   аватар и показывает `alt="Аватар"` вместо картинки. Это
   реальный баг, который мы поймали в smoke. Относительный путь
   `/storage/...` всегда резолвится против хоста текущей
   страницы, независимо от `APP_URL`. Минус — теряем абсолютные
   URL для случаев типа email-нотификаций (там нужен полный
   URL). Когда понадобится — добавим отдельный метод
   `avatarAbsoluteUrl()`.

2. **Почему имя файла генерируется из `user_id + timestamp`,
   а не сохраняется оригинальное.** Загружаемые имена могут
   содержать: кириллицу (`мой-аватар.png`), пробелы (`my photo.jpg`),
   символы (`(1).png`). На Windows-файловой системе всё это
   живёт, но при проксировании через php-fpm/nginx или при
   деплое на Linux — кодировка может меняться, файл становится
   недоступен. Детерминированное ASCII-имя из id и времени
   снимает эту проблему.

3. **Почему поле в форме удаления называется `confirm_password`,
   а не `password`.** В форме смены пароля поле «новый пароль»
   называется `password`. Если в форме удаления тоже использовать
   `password`, то после ошибки в одной из форм Laravel-overrides
   подсветят поле в обеих формах. С `confirm_password` ошибки
   изолированы по своим местам без необходимости заводить
   именованные `MessageBag`'и.

4. **Почему `bio` — `string(500)`, а не `text`.** MySQL `TEXT`
   хранится отдельно от ряда (off-page storage), плюс не
   индексируется без ключевого слова `FULLTEXT`. Для подписи
   до 500 символов `VARCHAR(500)` достаточен и быстрее. Если
   позже захочется длинный «о себе» (страница bio) — сделаем
   отдельную миграцию `change()` на `text`.

5. **Почему `avatarUrl()` и `initial()` — методы модели, а
   не accessors через `Casts\Attribute`.** Для `avatarUrl()`
   логика чуть сложнее (условие на `null`), для `initial()`
   — почти однострочник. Через `Attribute::get(...)` получили
   бы `$user->avatar_url` как магический атрибут, но
   приходилось бы помнить, что метод не публичный. Метод —
   явная штука: `$user->avatarUrl()` нельзя прочитать «как
   поле» из шаблона, нужно вызвать. Семантика «это
   вычисляемое свойство, не из БД» становится явной.

6. **Почему `php artisan storage:link` не запускается
   автоматически в migration.** `storage:link` — это команда
   уровня инфраструктуры, не схемы БД. Запускать её в `up()`
   миграции — нарушение принципа «миграция меняет только БД».
   Развёртывание (`composer install` + `php artisan migrate`
   + `php artisan storage:link`) — это процесс деплоя, который
   обычно описывается в README. Симлинк создаётся один раз
   на сетап, в `composer.json` можно прописать в `post-install-cmd`,
   но для дипломного проекта это уже излишне.
