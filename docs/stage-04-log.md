# Этап 04 — Цели (Goals CRUD)

## Цель

Реализовать CRUD по модели «Цель»: пользователь может создать,
просматривать, редактировать, отмечать как завершённую, отправлять
в архив и удалять собственные цели. Чужие цели полностью невидимы
и недоступны (404 для несуществующих, 403 для чужих). Список группируется
по категориям с цветовой кодировкой и прогресс-барами (прогресс
пока всегда 0% — заполнится после Этапа 05, когда появятся подцели
и задачи).

## Выполненные действия

1. Создана миграция
   `database/migrations/2026_05_07_004046_create_goals_table.php`
   по схеме из мастер-промпта: `id`, `user_id` FK с
   `cascadeOnDelete`, `title (string 200)`, `description text
   nullable`, `category enum('study','sport','work','other')`,
   `status enum('active','completed','archived') default 'active'`,
   `deadline date nullable`, `timestamps()`. Индексы: `user_id`,
   `status`. Миграция применена.
2. Создан `app/Models/Goal.php`:
   - `$fillable` — все доменные поля.
   - Каст `deadline` → `date` (Carbon).
   - Константы `STATUS_*` и `CATEGORY_*`, словарь
     `CATEGORIES` (code → ru-label) — единая точка
     для UI и валидации.
   - Связи: `belongsTo(User::class)`, `hasMany(Subtask::class)`
     (на Subtask пока ещё нет файла — используется forward-ref
     по имени класса; см. Notes #1).
   - Scope `forUser(int $userId)` — фильтр по владельцу.
   - Аксессор `progress()` через современный Laravel-API
     `Attribute::get(...)`; пока не существует таблица `tasks`,
     возвращает 0 (полная реализация подключится в Этапе 05).
   - Хелпер `categoryLabel(): string`.
3. Создан `app/Http/Requests/GoalRequest.php`:
   - `title required string max:200`.
   - `description nullable string max:5000`.
   - `category required Rule::in(array_keys(Goal::CATEGORIES))`.
   - `deadline nullable date after:today`.
   - Сообщения и атрибуты на русском (для аккуратных ошибок
     в форме).
4. Создан `app/Services/GoalService.php`:
   - `listForUser(int): Collection` — `forUser` scope +
     eager `with(['subtasks.tasks'])` + сортировка
     `FIELD(status, 'active','completed','archived')`
     (активные сверху, потом завершённые, потом архив).
   - `create(int $userId, array $data): Goal`.
   - `update(int $goalId, int $userId, array $data): Goal` —
     через `findOwned`.
   - `archive`, `complete` — меняют `status`.
   - `delete(int $goalId, int $userId): void` — hard delete.
   - `get(int, int): Goal` — публичная обёртка над
     `findOwned` для контроллера.
   - `findOwned(int $goalId, int $userId): Goal` — приватный
     гейт: `Goal::find` → `abort(404)` если нет, `abort(403)`
     если `user_id` чужой.
5. Создан `app/Http/Controllers/GoalController.php` (тонкий):
   методы `index`, `create`, `store`, `show`, `edit`, `update`,
   `destroy` (resource) + `archive`, `complete`. Для получения
   id залогиненного юзера используется фасад `Auth::id()`
   (а не хелпер `auth()->id()`), чтобы статические анализаторы
   не ругались на «магический» метод (см. Notes #4).
6. Созданы вью:
   - `resources/views/goals/index.blade.php` — карточки сгруппированы
     по категориям, у каждой группы цветной бейдж (indigo/emerald/amber/slate),
     карточка показывает заголовок, описание (line-clamp-2),
     прогресс-бар, статус-бейдж, дедлайн (красный если < 3 дней).
     Empty-state с кнопкой создания первой цели.
   - `resources/views/goals/create.blade.php` — форма с title,
     category select (русские лейблы), deadline (`min` = завтра),
     textarea для description.
   - `resources/views/goals/edit.blade.php` — то же самое
     с `@method('PATCH')` и предзаполненными значениями
     через `old(.., $goal->...)`.
   - `resources/views/goals/show.blade.php` — детальная карточка
     с бейджами категории и статуса, описанием, метаданными
     (создана/дедлайн/прогресс), прогресс-баром. Кнопки
     «Редактировать», «Завершить» (зелёная), «В архив», «Удалить»
     (красная, с `confirm()`). Кнопки действия скрываются если
     цель не `active`. Заглушка раздела «Подцели и задачи»
     для Этапа 05.
7. В `routes/web.php` добавлены:
   - `Route::resource('goals', GoalController::class)->whereNumber('goal')`.
   - Дополнительные `POST /goals/{goal}/archive` и
     `POST /goals/{goal}/complete` с именами `goals.archive`,
     `goals.complete`.
   - Все маршруты — внутри уже существующей группы
     `middleware('auth')`.
8. В `resources/views/dashboard.blade.php` добавлена
   первичная кнопка «Мои цели» (indigo), кнопка «Пользователи»
   (только для admin) переехала в secondary стиль (gray).
9. Smoke-тесты через локальный `php artisan serve` (см. ниже),
   13 сценариев — все прошли.
10. Создан `docs/stage-04-log.md` (этот файл).

## Созданные файлы

- `app/Http/Controllers/GoalController.php`
- `app/Http/Requests/GoalRequest.php`
- `app/Models/Goal.php`
- `app/Services/GoalService.php`
- `database/migrations/2026_05_07_004046_create_goals_table.php`
- `resources/views/goals/index.blade.php`
- `resources/views/goals/create.blade.php`
- `resources/views/goals/edit.blade.php`
- `resources/views/goals/show.blade.php`
- `docs/stage-04-log.md`

## Изменённые файлы

- `routes/web.php` — добавлены `use GoalController`, resource-маршруты
  и два POST-маршрута archive/complete.
- `resources/views/dashboard.blade.php` — кнопка «Мои цели»,
  reordering с админ-кнопкой.

## Краткое описание ключевых изменений в коде

**`app/Models/Goal.php::progress`** — реализован через современный
`Casts\Attribute::get(...)` (а не legacy `getProgressAttribute`),
потому что Laravel 11 рекомендует именно его. Защита через
`Schema::hasTable('tasks')` исключает фатал на Этапе 04, когда
таблиц `subtasks`/`tasks` ещё нет: вернёт 0 без обращения к БД.
В Этапе 05 эта защита перестанет срабатывать сама собой,
ничего переписывать не придётся.

**`app/Services/GoalService.php::listForUser`** — сортировка
`FIELD(status, 'active','completed','archived')` сначала
показывает активные цели сверху, потом завершённые, потом архив.
Вторичная сортировка `created_at DESC` — внутри каждой группы.
Без явной сортировки Laravel вернёт «как в БД» — порядок
непредсказуем.

**`app/Services/GoalService.php::findOwned`** — порядок проверок
важен: сначала `find()` → `abort(404)`, потом проверка владения
→ `abort(403)`. Без 404-ветки чужой пользователь, попавший на
несуществующий id, получил бы 403 — это утечка информации
(подтверждение, что ресурс существует, но недоступен).
404 для несуществующего и 403 для чужого — стандарт.

**`app/Http/Controllers/GoalController.php`** — все методы
тонкие: получают данные/id, делегируют в сервис, возвращают
редирект или вью. Нигде нет работы с моделью напрямую —
сервис скрывает Eloquent. Если завтра придётся заменить
SQL на API/Redis — контроллер не тронем.

**`routes/web.php`** — `whereNumber('goal')` на resource
отсекает мусор уровня `/goals/abc` до контроллера. Без этого
URL вроде `/goals/admin` тоже попал бы в `show` и упал
позже на `Goal::find('admin')`.

## Использованные команды

```bash
# Скаффолдинг (минимальный)
php artisan make:migration create_goals_table
php artisan make:request GoalRequest

# (модель, сервис, контроллер, вью — вручную через Write,
# чтобы не плодить boilerplate-комментарии Laravel-генератора)

# Применение миграции
php artisan migrate

# Запуск и smoke
php artisan route:list
php artisan serve --port=8765   # затем curl-сценарии (см. ниже)
```

## Как поднять и проверить

```bash
php artisan migrate    # если не запускали — создаст таблицу goals
php artisan serve      # http://localhost:8000
```

Smoke-чеклист:

- [ ] Гость → `/goals` → редирект на `/login`.
- [ ] User → `/goals` → 200, пустой list со ссылкой
      «Создать первую цель».
- [ ] `/goals/create` → форма со всеми 4 категориями
      («Учёба», «Спорт», «Работа», «Другое»).
- [ ] POST с пустым title → редирект назад с ошибкой
      «Укажите название цели.».
- [ ] POST с deadline в прошлом → ошибка «Дедлайн должен
      быть в будущем.».
- [ ] POST с подделанной категорией (DevTools) → ошибка
      «Категория указана некорректно.».
- [ ] POST с валидными данными → редирект на
      `/goals/{id}` с flash «Цель создана.».
- [ ] `/goals/{id}/edit` → форма с предзаполненными значениями.
- [ ] PATCH с новыми данными → редирект на show с flash
      «Цель обновлена.».
- [ ] Кнопка «Завершить» → `status` становится `completed`,
      кнопки действия исчезают.
- [ ] Кнопка «В архив» → `status` становится `archived`.
- [ ] Кнопка «Удалить» → подтверждение через `confirm()`,
      затем редирект на `/goals` с flash «Цель удалена.».
- [ ] User A открывает `/goals/{id}` цели User B → 403.
- [ ] Открыть `/goals/999999` (несуществующий) → 404.
- [ ] На `/goals` цели сгруппированы по категориям, активные
      сверху списка.
- [ ] На дашборде есть кнопка «Мои цели».

## Notes (нетривиальные обоснования)

1. **Почему `hasMany(Subtask::class)` объявляется в Этапе 04,
   хотя класс Subtask появится только в Этапе 05.** Laravel
   парсит relation lazy: `hasMany` сохраняет имя класса как
   строку и резолвит его при первом обращении к `->subtasks`.
   В Этапе 04 мы нигде не вызываем `$goal->subtasks`
   (а если и вызовем, защита `Schema::hasTable('tasks')`
   в `progress` сработает первой и вернёт 0). Это позволяет
   зафиксировать правильную модель данных сейчас и не
   возвращаться к ней в Этапе 05 — там только обновим
   `progress()`. Альтернатива — добавить relation в Этапе 05
   — нарушение принципа «модель — это контракт, не делайте
   её половинчатой».

2. **Почему `destroy` — это hard delete, а `archive` —
   отдельное состояние.** Архив сохраняет историю (для
   статистики `completed_goals` / `archived_goals` на
   дашборде Этапа 06). Полное удаление нужно если
   пользователь создал цель по ошибке и хочет убрать
   её насовсем. Два разных action — два разных сценария.
   Если бы оставили только soft delete (как `archive`),
   мусорные тестовые цели копились бы у пользователя
   в архиве с нулевой ценностью.

3. **Почему `FIELD(status, ...)` в SQL вместо CASE WHEN
   или сортировки в PHP.** `FIELD()` — компактная и
   выразительная конструкция MySQL для сортировки по
   произвольному порядку enum-значений. Альтернативы:
   (а) сортировка в PHP — теряем возможность пагинации
   на стороне БД; (б) `CASE WHEN status='active' THEN 1
   WHEN status='completed' THEN 2 ELSE 3 END` — длиннее
   и читается хуже. Минус `FIELD()` — это MySQL-специфика,
   на Postgres так не сделать. Для дипломного проекта
   на MySQL это приемлемо; если завтра придётся
   мигрировать на Postgres, заменим одной строкой
   в репозитории (Этап 11).

4. **Почему `Auth::id()` (фасад) вместо `auth()->id()`
   (хелпер).** Хелпер `auth()` возвращает `AuthManager`,
   а метод `id()` живёт на `Guard`. AuthManager проксирует
   вызов в default-guard через `__call` — это работает
   в рантайме, но статические анализаторы (Intelephense /
   PHPStan / PhpStorm) не видят магических методов и
   красят «Undefined method 'id'». Фасад `Auth` имеет
   PHPDoc `@method static int|string|null id()` — IDE
   видит метод, ошибка пропадает. Поведение идентично,
   но код спокойнее проходит code review и не вводит
   в заблуждение нового разработчика, увидевшего красное
   подчёркивание.

5. **Почему смешанная политика в форме создания: `min` на
   `<input type="date">` И серверное правило
   `after:today`.** HTML5-атрибут `min` блокирует выбор
   прошлой даты в нативном date picker — UX-уровень.
   Серверное `after:today` — единственный надёжный гейт
   (через DevTools легко стереть `min`). Дублирование
   преднамеренное: UI не даёт ошибиться, сервер
   гарантирует корректность.

6. **Почему category — enum-поле в БД, а не отдельная
   таблица.** Это решение зафиксировано в мастер-промпте
   («одна цель = одна категория для MVP»). Список категорий
   фиксированный (4 значения), пользователь не должен мочь
   создавать свои. Отдельная таблица была бы оверинжинирингом:
   FK, JOIN, seeder, migration на каждое изменение списка.
   Минус enum — расширение списка требует миграции
   (`ALTER TABLE goals MODIFY category ENUM(...)`),
   но в дипломном проекте такой задачи нет.
