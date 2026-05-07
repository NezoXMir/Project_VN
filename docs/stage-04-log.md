# Этап 04 — Цели: CRUD, категории, личная палитра, морфология дедлайнов

## Цель

Реализовать полноценную работу с моделью «Цель» — CRUD,
группировку по категориям, цветовую визуализацию — за один
функциональный этап, разбив выполнение на четыре последовательных
блока. На выходе: пользователь создаёт/редактирует/архивирует/удаляет
свои цели, группирует их в системные или собственные категории
с произвольным цветом, не выбирает один и тот же hex дважды
(блок «Твои цвета»), видит до дедлайна правильную русскую подпись
с согласованным родом и числом (1 день / 2 дня / 5 дней,
1 неделя / 3 недели, 1 месяц / 2 месяца).

Прогресс цели пока всегда `0%` — заполнится в Этапе 05, когда
появятся подцели и задачи. Связь `hasMany(Subtask::class)`
объявлена заранее (lazy-резолв), `progress()` защищён
`Schema::hasTable('tasks')` от падения до Этапа 05.

## Структура этапа

- **Часть A.** Goals CRUD (коммит `ae56124`).
- **Часть B.** Пользовательские категории взамен enum-поля
  (коммит `1c5a523`).
- **Часть C.** Личная палитра «Твои цвета» в форме категории
  (коммит `0b08b2b`).
- **Часть D.** Хелпер `DateHelper` для правильного склонения
  «осталось/осталась/остался» с автовыбором единицы (дни /
  недели / месяцы).

## Выполненные действия

### A. Goals CRUD

1. Создана миграция
   `database/migrations/2026_05_07_004046_create_goals_table.php`:
   `id`, `user_id` FK с `cascadeOnDelete`, `title (string 200)`,
   `description text nullable`, `category enum('study','sport','work','other')`
   *(удалится в части B)*, `status enum('active','completed','archived')
   default 'active'`, `deadline date nullable`, `timestamps()`.
   Индексы: `user_id`, `status`. Применена.
2. Создан `app/Models/Goal.php`:
   - `$fillable`, каст `deadline → date` (Carbon).
   - Константы `STATUS_*`.
   - Связи `user(): BelongsTo`, `subtasks(): HasMany` *(forward-ref
     по имени класса; см. Notes #1)*.
   - Scope `forUser(int $userId)` — фильтр по владельцу.
   - Аксессор `progress()` через современный
     `Casts\Attribute::get(...)`. Защита через
     `Schema::hasTable('tasks')` исключает падение, пока
     таблиц `subtasks`/`tasks` нет.
3. Создан `app/Http/Requests/GoalRequest.php`:
   - `title required string max:200`, `description nullable string
     max:5000`, `deadline nullable date after:today`. Валидация
     `category` в этой части — через `Rule::in(array_keys(Goal::CATEGORIES))`
     *(перепишется в части B на `Rule::exists`)*.
4. Создан `app/Services/GoalService.php`:
   - `listForUser` — `forUser` + сортировка
     `FIELD(status, 'active','completed','archived')` ASC,
     `created_at` DESC.
   - `create` / `update` / `archive` / `complete` /
     `delete` / `get` — все через приватный `findOwned()`
     (404 → 403, порядок важен — см. Notes #3).
5. Создан `app/Http/Controllers/GoalController.php` (тонкий):
   resource-методы + `archive`, `complete`. Везде `Auth::id()`
   через фасад, не `auth()->id()` *(IDE-friendliness, см.
   Notes #4)*.
6. Созданы вью:
   - `goals/index.blade.php` — карточки сгруппированы по
     категориям, у каждой группы цветной бейдж, прогресс-бар,
     статус, дедлайн (красный если < 3 дней). Empty-state.
   - `goals/create.blade.php` — форма с title, select категорий,
     deadline (`min` = завтра), textarea.
   - `goals/edit.blade.php` — то же с `@method('PATCH')` и
     предзаполненными значениями через `old(.., $goal->...)`.
   - `goals/show.blade.php` — детальная карточка, кнопки
     «Редактировать», «Завершить», «В архив», «Удалить»
     (с `confirm()`). Кнопки скрываются если цель не `active`.
     Заглушка раздела «Подцели и задачи» под Этап 05.
7. В `routes/web.php` добавлены `Route::resource('goals')->whereNumber('goal')`
   + `POST /goals/{goal}/archive` и `POST /goals/{goal}/complete`
   с именами `goals.archive`, `goals.complete`. Всё под
   уже существующим `middleware('auth')`.
8. В `dashboard.blade.php` добавлена первичная кнопка
   «Мои цели» (indigo); кнопка «Пользователи» переведена в
   secondary-стиль (gray).

### B. Пользовательские категории

9. Создана миграция
   `2026_05_07_011023_create_categories_table.php`:
   `id`, `user_id` FK nullable с `cascadeOnDelete`
   (NULL = системная), `label string(60)`,
   `color string(7)` (hex `#RRGGBB`),
   `is_system bool default false`, `timestamps`,
   индекс на `user_id`. В этой же миграции — insert трёх
   системных категорий: Учёба `#4F46E5`, Спорт `#10B981`,
   Работа `#F59E0B`. Без отдельного сидера — см. Notes #5.
10. Создана миграция
    `2026_05_07_011147_add_category_id_to_goals_table.php`:
    - Шаг 1: добавляется nullable `category_id` FK на
      `categories` с `cascadeOnDelete` (см. Notes #6 про
      каскад при удалении пользователя).
    - Шаг 2: маппинг старых значений в id системных
      (`study→Учёба`, `sport→Спорт`, `work→Работа`,
      `other→Учёба` — «Другое» убрано по решению, см. Notes #7).
    - Шаг 3: удаление enum-поля `category` и `change()`
      `category_id` в `nullable(false)`.
    - `down()` — обратный маппинг, всё восстановимо
      (с потерей точного маппирования пользовательских
      категорий — мапятся в `other`).
11. Обновлён `app/Models/Goal.php`:
    - Удалены константы `CATEGORY_*` и словарь `CATEGORIES`.
    - Удалён хелпер `categoryLabel()`.
    - Удалён `category` из `$fillable`, добавлен `category_id`.
    - Добавлена связь `category(): BelongsTo`.
12. Создан `app/Models/Category.php`:
    - `$fillable`: `user_id`, `label`, `color`, `is_system`.
    - Каст `is_system` → bool.
    - Связи: `user(): BelongsTo`, `goals(): HasMany`.
    - Скоупы: `availableTo($userId)` — системные + свои,
      `ownedBy($userId)` — только свои.
    - Метод `isOwnedBy($userId): bool`.
13. Создан `app/Services/CategoryService.php`:
    - `listAvailable($userId)` — системные первыми (по id),
      потом свои (новые сверху).
    - `listOwn($userId)`, `create`, `update`, `delete`,
      `findAvailable` (для валидации в `GoalRequest`),
      приватный `findOwned` (404/403 + блок системных).
    - `delete()` ловит `goals()->count()` > 0 → бросает
      `DomainException` с правильно склонённым числом
      целей («1 цель / 2 цели / 5 целей»). Это даёт
      пользователю понять, не только что удалить нельзя,
      но и сколько именно целей надо переместить.
    - Приватный `normalizeColor()` — `strtoupper + trim +
      regex` к `^#[0-9A-F]{6}$`.
14. Создан `app/Http/Requests/CategoryRequest.php`:
    `label required string max:60`, `color required regex
    /^#[0-9A-Fa-f]{6}$/`. Сообщения на русском.
15. Создан `app/Http/Controllers/CategoryController.php`:
    `index/create/store/edit/update/destroy`. Константа
    `PALETTE` — 8 hex'ов для пресетов. В `edit()` явно
    проверяется `is_system` → `abort(403)` (чтобы 403 не
    приходил позже из сервиса).
16. Обновлён `app/Http/Requests/GoalRequest.php`:
    правило `category_id` через `Rule::exists` со
    вложенным замыканием — категория должна быть либо
    системной, либо принадлежать `Auth::id()`. Это блокирует
    подделку чужого `category_id` через DevTools/curl
    (см. Notes #8).
17. Обновлён `app/Services/GoalService.php`:
    `category_id` вместо `category` в `create`/`update`,
    `findOwned()` теперь делает `with('category')`,
    `listForUser()` добавляет `with('category')` против N+1
    при рендере списка.
18. Обновлён `app/Http/Controllers/GoalController.php`:
    инжектится `CategoryService`, в `create()` и `edit()`
    передаётся `listAvailable()` для select.
19. Созданы вью:
    - `categories/index.blade.php` — grid карточек, у системных
      без кнопок управления, у пользовательских —
      «Изменить» / «Удалить».
    - `categories/create.blade.php` и `edit.blade.php` —
      обёртки над общей формой.
    - `categories/_form.blade.php` — partial с палитрой пресетов
      (8 кружков), HTML5 color picker (`<input type="color">`),
      live-превью бейджа через Alpine.js (`x-data="{ color: ... }"`),
      hidden input для отправки на сервер.
20. Обновлены вью целей:
    - `goals/index.blade.php` — группировка по `category_id`,
      бейдж и левая полоска карточки берут `$cat->color` через
      inline-стили (CSS-переменные не используем).
    - `goals/show.blade.php` — бейдж категории через
      inline-стили + резерв `$goal->category?->color` если связь
      не загрузилась.
    - `goals/create.blade.php`, `edit.blade.php` — select
      с `$categories` из `CategoryService`, опции дополняются
      меткой «(моя)» для пользовательских.
21. Обновлены маршруты `routes/web.php`:
    `Route::resource('categories', CategoryController::class)
    ->except(['show'])->whereNumber('category')` под
    `middleware('auth')`. Show у категории не нужен — index +
    edit закрывают потребность.
22. В `dashboard.blade.php` добавлена кнопка «Категории» рядом
    с «Мои цели».

### C. Личная палитра «Твои цвета»

23. В `CategoryService` добавлен `userColorsFor(int $userId):
    array` — `pluck('color')->unique()` по категориям пользователя
    в порядке от свежих к старым.
24. В `CategoryController` появился приватный `userPaletteFor(int
    $userId): array` — берёт результат сервиса и **исключает**
    пресеты `PALETTE` (чтобы «Твои цвета» не дублировали
    «Стандартные»). Передаётся во вью под именем
    `$userPalette`.
25. В `categories/_form.blade.php` между «Стандартные:» и
    color picker'ом — новый блок «Твои цвета:» с теми же
    кружками. Оборачивается в `@if (! empty($userPalette))`,
    поэтому новый пользователь без своих категорий блок не видит.
26. `categories/create.blade.php` и `edit.blade.php` принимают
    `$userPalette` из контроллера и передают в partial без
    изменений.

### D. Морфология подписи дедлайна (DateHelper)

27. Создан `app/Helpers/DateHelper.php`:
    - `timeLeftLabel(CarbonInterface $deadline): ?string` —
      возвращает строку вида «остался 1 день» / «осталось 2 дня» /
      «осталась 1 неделя» / «остался 1 месяц» либо `null`,
      если дедлайн уже в прошлом.
    - Спецслучай `$days === 0` (сегодня) → «остался последний
      день».
    - Пороги выбора единицы: `< 7 дней` → дни (м.р.), `7–29` →
      недели (ж.р., счёт `round($days / 7)`), `30+` → месяцы
      (м.р., счёт `round($days / 30)`).
    - Приватный `pluralIndex(int $n)` — стандартная формула
      русского склонения для числительных: `1, 21, 31, …` (без 11)
      → 0; `2-4, 22-24, …` (без 12-14) → 1; всё остальное → 2.
    - Приватный `verbForm(int $idx, string $gender)` — для
      `$idx === 0` берёт `остался/осталась` по роду, для
      остальных — нейтральное `осталось`.
28. В `goals/index.blade.php` блок дедлайна переписан:
    - Раньше: подпись `(осталось N дней)` показывалась **только
      при `$deadlineSoon` (< 3 дней)**.
    - Теперь: `({{ $timeLeft }})` где `$timeLeft =
      \App\Helpers\DateHelper::timeLeftLabel($g->deadline)`,
      показывается **всегда для будущего дедлайна**.
    - Красный цвет (`text-red-600 font-medium`) по-прежнему
      включается только при `$deadlineSoon`.

## Созданные файлы

### Часть A
- `app/Http/Controllers/GoalController.php`
- `app/Http/Requests/GoalRequest.php`
- `app/Models/Goal.php`
- `app/Services/GoalService.php`
- `database/migrations/2026_05_07_004046_create_goals_table.php`
- `resources/views/goals/index.blade.php`
- `resources/views/goals/create.blade.php`
- `resources/views/goals/edit.blade.php`
- `resources/views/goals/show.blade.php`

### Часть B
- `app/Http/Controllers/CategoryController.php`
- `app/Http/Requests/CategoryRequest.php`
- `app/Models/Category.php`
- `app/Services/CategoryService.php`
- `database/migrations/2026_05_07_011023_create_categories_table.php`
- `database/migrations/2026_05_07_011147_add_category_id_to_goals_table.php`
- `resources/views/categories/_form.blade.php`
- `resources/views/categories/create.blade.php`
- `resources/views/categories/edit.blade.php`
- `resources/views/categories/index.blade.php`

### Часть D
- `app/Helpers/DateHelper.php`

### Документация
- `docs/stage-04-log.md` (этот файл — единый лог Этапа 04)

## Изменённые файлы (сводно)

- `app/Http/Controllers/GoalController.php` — инжект
  `CategoryService`, передача `listAvailable()` в формы (часть B).
- `app/Http/Requests/GoalRequest.php` — `category_id` через
  `Rule::exists` с проверкой принадлежности (часть B).
- `app/Models/Goal.php` — удалены `CATEGORY_*` и словарь
  категорий, замена `category` → `category_id`, связь
  `category()` (часть B).
- `app/Services/GoalService.php` — переход на `category_id`,
  eager `with('category')` (часть B).
- `app/Services/CategoryService.php` — добавлен
  `userColorsFor()` (часть C).
- `app/Http/Controllers/CategoryController.php` — приватный
  `userPaletteFor()`, передача `$userPalette` во вью (часть C).
- `resources/views/categories/_form.blade.php` — секция
  «Твои цвета» (часть C).
- `resources/views/categories/create.blade.php`,
  `categories/edit.blade.php` — проброс `$userPalette` (часть C).
- `resources/views/dashboard.blade.php` — кнопки «Мои цели»
  (часть A) и «Категории» (часть B).
- `resources/views/goals/create.blade.php`,
  `goals/edit.blade.php`, `goals/index.blade.php`,
  `goals/show.blade.php` — переход на `$goal->category->...`
  + inline-стили (часть B).
- `resources/views/goals/index.blade.php` — подпись «осталось
  …» через `DateHelper`, вынесена из `$deadlineSoon` (часть D).
  Дополнительно: автоформаттер редактора прижал отступы
  Blade-блоков влево — изменение чисто whitespace, поведение
  идентично.
- `routes/web.php` — `Route::resource('goals')` +
  `archive`/`complete` (часть A); `Route::resource('categories')`
  (часть B).

## Краткое описание ключевых решений

**`Goal::progress` через `Casts\Attribute::get(...)`** —
современный API Laravel 11 (а не legacy `getProgressAttribute`).
Защита `Schema::hasTable('tasks')` исключает фатал на Этапе 04,
когда таблиц ещё нет: вернёт 0 без обращения к БД. В Этапе 05
эта защита перестанет срабатывать сама собой.

**`GoalService::findOwned`** — порядок проверок: сначала
`find()` → `abort(404)`, потом проверка владения → `abort(403)`.
Без 404-ветки чужой пользователь, попавший на несуществующий
id, получил бы 403 — это утечка информации (подтверждение,
что ресурс существует, но недоступен).

**`FIELD(status, ...)` в `listForUser`** — компактная и
выразительная конструкция MySQL для сортировки по произвольному
порядку enum-значений. Альтернативы: сортировка в PHP (теряем
пагинацию на стороне БД); `CASE WHEN` (длиннее и хуже читается).
Минус — это MySQL-специфика, для Postgres переписать одной
строкой в репозитории на Этапе 11.

**`CategoryService::delete`** — главное бизнес-правило части B.
Перед удалением считаем `$category->goals()->count()`. Если
> 0 — бросаем `DomainException` с правильно склонённым числом
целей. Без счётчика сообщение было бы абстрактным («есть цели»),
со счётчиком — конкретное действие («сначала перенесите 3 цели»).

**`Rule::exists` со вложенным замыканием в `GoalRequest`** —
```php
Rule::exists('categories', 'id')->where(function ($q) {
    $q->where(function ($q2) {
        $q2->where('is_system', true)
            ->orWhere('user_id', Auth::id());
    });
}),
```
Внешний `where` (от `exists`) задаёт область поиска, вложенный
`where(function...)` — обязательная скобочка, без неё `OR is_system
OR user_id` потеряет приоритет. Альтернатива — кастомное
Rule-объект, но для одной проверки overkill.

**Inline-стили вместо Tailwind-классов для цветов категорий.**
До части B палитра была захардкожена в Blade
(`bg-indigo-50`/`text-indigo-700`), и Tailwind-purger без проблем
включал нужные классы в сборку. Теперь цвета динамические из
БД — Tailwind-классы неприменимы (purger не знает заранее, какие
нужны). Inline-стили — единственный практичный путь без сложной
конфигурации safelist'а. Для бейджей используется
`background-color: {{ $color }}1A; color: {{ $color }}` — лёгкий
фон + тёмный текст того же тона, читаемо для любого hex'а.

**Левая цветная полоска (`border-l-4 + border-left-color`)
вместо целой границы.** Полоска даёт яркий визуальный маркер
категории, не «топя» содержимое карточки в фоновом цвете.
Целая граница в категорийный цвет выглядела OK для пресетов,
но для пользовательских ярких/тёмных цветов карточка теряла
читаемость. Полоска работает с любой палитрой.

**`DateHelper` как статический метод хелпера, а не на модели
Goal.** Логика склонения и выбора единицы — это форматирование,
не бизнес-правило цели. Метод на модели сделал бы её ответственной
за UI-представление дедлайна, что нарушает SRP. Хелпер же
переиспользуется на дашборде Этапа 06, в подписи карточки на
show, в email-напоминаниях Этапа 09.

**`null` для просроченного дедлайна, а не строка вроде
«просрочено».** Хелпер занимается одним — форматированием
времени **до** дедлайна. Если дата в прошлом — это другая
семантика («N дней просрочки», или «дедлайн сорван»), которой
может не быть в текущем UI вообще. Возвращая `null`, хелпер
позволяет вьюхе самой решить, что показывать; `goals/index`
просто скрывает подпись (`@if ($timeLeft)`).

**`userPaletteFor()` — приватный метод контроллера, а не
публичный сервиса.** «Минус пресеты» — это UI-логика конкретной
страницы создания/редактирования. Сервис не должен знать про
`CategoryController::PALETTE`. Контроллер — посредник: берёт
сырые цвета из сервиса (`userColorsFor`) и адаптирует под вью.

## Использованные команды

```bash
# Часть A
php artisan make:migration create_goals_table
php artisan make:request GoalRequest
# (модель/сервис/контроллер/вью — вручную через Write)
php artisan migrate
php artisan route:list

# Часть B
php artisan make:migration create_categories_table
php artisan make:migration add_category_id_to_goals_table --table=goals
# (модель/сервис/контроллер/реквест/вью — вручную через Write)
php artisan migrate

# Части C, D — все файлы вручную через Write/Edit

# Smoke
php artisan serve --port=8765
# далее ручные сценарии через браузер + curl + tinker
```

## Как поднять и проверить

```bash
php artisan migrate    # применит три миграции этапа
php artisan serve      # http://localhost:8000
```

Smoke-чеклист:

### Goals CRUD (часть A)

- [ ] Гость → `/goals` → редирект на `/login`.
- [ ] User → `/goals` → 200, пустой список со ссылкой
      «Создать первую цель».
- [ ] POST с пустым title → ошибка «Укажите название цели.».
- [ ] POST с deadline в прошлом → ошибка «Дедлайн должен быть
      в будущем.».
- [ ] POST с валидными данными → редирект на `/goals/{id}` с
      flash «Цель создана.».
- [ ] PATCH через edit → редирект на show с flash «Цель
      обновлена.».
- [ ] Кнопка «Завершить» → `status` → `completed`, кнопки
      действий исчезают.
- [ ] Кнопка «В архив» → `status` → `archived`.
- [ ] Кнопка «Удалить» → confirm + редирект на `/goals` с
      flash «Цель удалена.».
- [ ] User A открывает цель User B → 403; несуществующий id → 404.
- [ ] На `/goals` цели сгруппированы по категориям, активные
      сверху.

### Категории (часть B)

- [ ] `/categories` — видно 3 системных (Учёба / Спорт / Работа)
      с цветными кружками и пометкой «системная», без кнопок
      управления.
- [ ] `/categories/create` — палитра 8 кружков выделяет
      выбранный (ring), color picker открывается, превью
      реагирует live.
- [ ] Создание новой с пресет-цветом → flash «Категория создана.».
- [ ] Создание с пустым label → ошибка «Укажите название
      категории.».
- [ ] Создание с цветом без `#` → ошибка «Цвет должен быть
      в формате #RRGGBB.».
- [ ] У пользовательской есть «Изменить» / «Удалить»; у
      системной — нет.
- [ ] `/categories/{id}/edit` для системной → 403.
- [ ] DELETE категории, в которой есть цели → flash error
      «Нельзя удалить категорию: в ней N целей. …».
- [ ] DELETE категории без целей → удаляется, flash
      «Категория удалена.».
- [ ] На `/goals/create` в select все системные + свои (с пометкой
      «(моя)»). Чужие категории **не появляются**.
- [ ] POST `/goals` с подделанным чужим `category_id` → ошибка
      «Категория недоступна.».
- [ ] На `/goals` карточки имеют цветную левую полоску и бейдж
      в цвете категории; группы по категориям.

### Личная палитра (часть C)

- [ ] `/categories/create` у пользователя без своих категорий —
      секция «Твои цвета» **не видна**.
- [ ] Создать категорию с произвольным цветом `#A3E635` →
      на следующем `/categories/create` появилась секция
      «Твои цвета» с этим кружком.
- [ ] Создать вторую с цветом `#4F46E5` (один из пресетов) →
      этот цвет в «Твои цвета» **не дублируется** (фильтр по
      `PALETTE` отрабатывает).

### Морфология дедлайна (часть D)

- [ ] Дедлайн завтра → `(остался 1 день)`, красным.
- [ ] Через 2 дня → `(осталось 2 дня)`, красным.
- [ ] Через 5 дней → `(осталось 5 дней)`, красным.
- [ ] Через 7 дней → `(осталась 1 неделя)`, серым (> 3 дней).
- [ ] Через 21 день → `(осталось 3 недели)`.
- [ ] Через 60 дней → `(осталось 2 месяца)`.
- [ ] Дедлайн вчера (если есть) → подпись «(осталось …)»
      **не показывается**, сама дата по-прежнему видна.

## Notes (нетривиальные обоснования)

1. **Почему `hasMany(Subtask::class)` объявляется в Этапе 04,
   хотя класс `Subtask` появится только в Этапе 05.** Laravel
   парсит relation lazy: `hasMany` сохраняет имя класса как
   строку и резолвит его при первом обращении к `->subtasks`.
   В Этапе 04 мы нигде не вызываем `$goal->subtasks` (а если
   и вызовем, защита `Schema::hasTable('tasks')` в `progress()`
   сработает первой и вернёт 0). Это позволяет зафиксировать
   правильную модель данных сейчас и не возвращаться к ней.

2. **Почему `destroy` — это hard delete, а `archive` — отдельное
   состояние.** Архив сохраняет историю (для статистики
   `completed_goals` / `archived_goals` на дашборде Этапа 06).
   Полное удаление нужно если пользователь создал цель по
   ошибке. Два разных action — два разных сценария. Если бы
   оставили только soft delete (как `archive`), мусорные
   тестовые цели копились бы у пользователя в архиве с нулевой
   ценностью.

3. **Почему `404 → 403`, а не наоборот.** Чужой пользователь,
   попавший на несуществующий id, должен получить 404, а не
   403, иначе по реакции системы можно угадать, какие id
   существуют (user-enumeration по ресурсам). Стандартный
   протокол: 404 для несуществующего, 403 для существующего
   но чужого.

4. **Почему `Auth::id()` (фасад) вместо `auth()->id()`
   (хелпер).** Хелпер `auth()` возвращает `AuthManager`, а
   метод `id()` живёт на `Guard`. AuthManager проксирует вызов
   в default-guard через `__call` — работает в рантайме, но
   статические анализаторы (Intelephense / PHPStan / PhpStorm)
   не видят магических методов и красят «Undefined method 'id'».
   Фасад `Auth` имеет PHPDoc `@method static int|string|null id()`
   — IDE видит метод, ошибка пропадает. Поведение идентично,
   но код спокойнее проходит code review.

5. **Почему системные категории вставляются прямо в миграцию,
   а не отдельным сидером.** Системные данные — не «тестовые»,
   а часть схемы (без них приложение не работает: в `goals`
   FK на `category_id`, без хотя бы одной категории невозможно
   создать цель). Insert в миграции гарантирует, что после
   `php artisan migrate` БД готова к работе. Сидеры остаются
   для опциональных тестовых данных (`AdminSeeder`).

6. **Каскад при удалении пользователя: почему оба FK CASCADE
   и почему это не ломается.** У нас две связи:
   `categories.user_id → users` (CASCADE) и `goals.category_id
   → categories` (CASCADE). При удалении user'а MySQL должен
   каскадно удалить и его категории, и его цели. Если порядок
   неудачный (категории первыми), цели в этих категориях
   автоматически каскадно удалятся. Если цели первыми — то
   же самое. Конфликта нет, обе ветки ведут к удалению; нет
   ситуации «удалить категорию, но цели остались с висячим
   FK». Альтернатива `goals.category_id RESTRICT` создала бы
   тупик: нельзя удалить ни user'а, ни категорию. Уровень
   приложения (`CategoryService::delete`) защищает от
   user-инициированного удаления через дружелюбный exception,
   FK CASCADE — только для каскадного удаления user'а.

7. **Почему «Другое» убрано из системного списка.** UX-аргумент:
   «Другое» — ленивая категория, в которую сваливается всё, и
   со временем становится самой большой. Лучше заставить
   пользователя выбрать осмысленную или создать свою. Старые
   цели с `category='other'` миграция мапит на «Учёбу» — это
   компромисс (одна из трёх оставшихся системных), пользователь
   может вручную перевыбрать через edit.

8. **Почему `category_id` в `Rule::exists` через замыкание, а
   не два отдельных правила.** Альтернатива —
   `['exists:categories,id', 'category_belongs_to_user']`,
   где второе — кастомный Rule. Текущий вариант: одна проверка
   вместо двух (быстрее), не нужен отдельный класс правила,
   понятен с одного взгляда. Минус — длинное замыкание прямо
   в массиве правил; если правил станет больше, перенесём в
   `prepareForValidation()` или в Rule-класс.

9. **Почему `palette` — публичная константа на контроллере,
   а не отдельный конфиг или enum.** Палитра используется
   только во вью create/edit. Контроллер — единственное место
   передачи во вью. Если завтра палитра понадобится ещё
   где-то (`/admin` для глобальной статистики), вынесем в
   `config/palette.php` или `App\Support\Palette`. Сейчас
   одна точка потребления — без дополнительной абстракции.

10. **Почему сохраняется hex без normalisation на уровне БД
    (триггер).** Сервис делает `strtoupper + trim + regex` в
    `normalizeColor()`. Этого достаточно: вся запись идёт
    только через сервис, обходных путей нет. Триггер БД был
    бы защитой второго уровня, но добавил бы зависимость на
    конкретную RDBMS и усложнил отладку.

11. **Почему пороги `7 / 30` в `DateHelper`, а не `14 / 60`
    (как было в первой версии).** Запрос пользователя: «1 неделя»
    должна показываться, когда осталась именно 1 неделя.
    При пороге `< 14 дней` семь дней оставались бы в дневном
    режиме (`осталось 7 дней`) — не соответствует ожиданию.
    Сместили: 1–6 дней — дни, 7+ — недели, 30+ — месяцы.
    Минус — пограничные значения округляются (`round(8/7) = 1`,
    8 дней показывается как «осталась 1 неделя»). Сознательный
    компромисс ради семантической корректности на «круглых»
    числах.

12. **Почему `round`, а не `floor` / `ceil` для перевода в
    недели/месяцы.** `round` даёт ближайшее целое — это
    соответствует тому, как человек говорит о времени.
    Дедлайн через 9 дней на восприятие — «чуть больше недели»,
    что лучше передаётся как «1 неделя» (round), чем как
    «1 неделя» (floor) или «2 недели» (ceil). Через 11 дней —
    «полторы недели», склоняем к 2 (round).

13. **Почему `Carbon\CarbonInterface` в сигнатуре `DateHelper`,
    а не `Illuminate\Support\Carbon` (как в Laravel-коде).**
    `CarbonInterface` — контракт от пакета `nesbot/carbon`,
    которому удовлетворяют и базовый `Carbon\Carbon`, и
    Laravel-овский `Illuminate\Support\Carbon`. Сигнатура
    принимает любую из этих реализаций — хелпер тестируем
    и не привязан к Laravel-конкретике. Внутри уже используется
    `Illuminate\Support\Carbon::now()` — потому что нужна
    «сейчас» с учётом testing-time freeze.

14. **Почему `\App\Helpers\DateHelper::timeLeftLabel(...)`
    полностью квалифицированно во вью, а не через `@inject`.**
    `@inject` создаёт переменную в шаблоне
    (`@inject('dates', 'App\Helpers\DateHelper')`), но у нас
    один статический вызов в одной вью — добавлять директиву
    ради этого избыточно. Если хелпер начнёт использоваться
    в 3+ вью — перейдём на `@inject` или View Composer.

15. **Почему whitespace-переформатирование `goals/index.blade.php`
    осталось внутри коммита, а не было откатено.** Текстовое
    содержимое не пострадало, поведение идентично, а откат
    потребовал бы ручной правки `git checkout --patch` или
    отказа от подключения `DateHelper`. Принято обе правки
    одним коммитом — flat-стиль отступов совместим с остальным
    проектом (Blade-форматтер сам выровнял).
