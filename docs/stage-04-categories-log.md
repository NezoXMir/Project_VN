# Этап 04 (доп.) — Пользовательские категории целей

## Цель

Дополнить готовый Goals CRUD из Этапа 04 поддержкой
пользовательских категорий. Изначальный enum-список
(`study`, `sport`, `work`, `other`) заменён на полноценную
таблицу `categories` с per-user областью. Системные
категории (3 штуки: Учёба / Спорт / Работа) общедоступны
и неудаляемы; пользователь может добавлять, переименовывать
и удалять собственные. Каждая категория имеет произвольный
цвет (палитра пресетов + произвольный hex через
color picker). Удаление заблокировано, если в категории есть
цели — это защита от случайной потери группировки.

## Выполненные действия

1. Создана миграция
   `database/migrations/2026_05_07_011023_create_categories_table.php`:
   `id`, `user_id` FK nullable с `cascadeOnDelete`
   (NULL = системная), `label string(60)`, `color string(7)`
   (hex `#RRGGBB`), `is_system bool default false`,
   `timestamps`. Индекс на `user_id`. В этой же миграции
   выполняется insert трёх системных категорий с предвыбранной
   палитрой (`#4F46E5`, `#10B981`, `#F59E0B`) — без отдельного
   сидера, см. Notes #1.
2. Создана миграция
   `database/migrations/2026_05_07_011147_add_category_id_to_goals_table.php`:
   - Шаг 1: добавляется nullable `category_id` FK на
     `categories` с `cascadeOnDelete` (см. Notes #2 про
     каскад при удалении пользователя).
   - Шаг 2: маппинг старых строковых значений в id
     системных категорий (`study→Учёба`, `sport→Спорт`,
     `work→Работа`, `other→Учёба` — «Другое» убрано
     по решению).
   - Шаг 3: удаление старого enum-поля `category` и
     `change()` `category_id` в `nullable(false)`.
   - `down()` делает обратный маппинг — всё восстановимо.
3. Обновлён `app/Models/Goal.php`:
   - Удалены константы `CATEGORY_*` и словарь `CATEGORIES`
     (теперь источник истины — таблица).
   - Удалён хелпер `categoryLabel()`.
   - Удалён `category` из `$fillable`, добавлен `category_id`.
   - Добавлена связь `category(): BelongsTo`.
4. Создан `app/Models/Category.php`:
   - `$fillable`: user_id, label, color, is_system.
   - Каст `is_system` → bool.
   - Связи: `user(): BelongsTo`, `goals(): HasMany`.
   - Скоупы: `availableTo($userId)` — системные + свои,
     `ownedBy($userId)` — только свои.
   - Метод `isOwnedBy($userId): bool`.
5. Создан `app/Services/CategoryService.php`:
   - `listAvailable($userId)` — системные сначала
     (по id), потом свои (новые сверху).
   - `listOwn($userId)`, `create`, `update`, `delete`,
     `findAvailable` (для валидации в GoalRequest),
     приватный `findOwned` (404/403 + блокировка системных).
   - `delete()` ловит `goals()->count()` > 0 → бросает
     `DomainException` с сообщением по правильному склонению
     («1 цель / 2 цели / 5 целей»).
   - Приватный `normalizeColor()` нормализует hex
     к `^#[0-9A-F]{6}$`.
6. Создан `app/Http/Requests/CategoryRequest.php`:
   `label required string max:60`, `color required regex
   /^#[0-9A-Fa-f]{6}$/`. Сообщения на русском.
7. Создан `app/Http/Controllers/CategoryController.php`:
   index/create/store/edit/update/destroy. Константа
   `PALETTE` — 8 hex-цветов для пресетов. Контроллер
   проверяет `is_system` перед `edit` и кидает 403, чтобы
   пользователь не получил форму, которая всё равно
   упадёт в сервисе.
8. Обновлён `app/Http/Requests/GoalRequest.php`:
   правило `category_id` использует `Rule::exists` с
   замыканием — категория должна быть либо системной, либо
   принадлежать текущему `Auth::id()`. Это блокирует
   попытку подсунуть чужой `category_id` через
   DevTools/curl.
9. Обновлён `app/Services/GoalService.php`:
   `category_id` вместо `category` в `create`/`update`,
   `findOwned()` теперь делает `with('category')`,
   `listForUser()` добавляет `with('category')` чтобы
   избежать N+1 при рендере списка.
10. Обновлён `app/Http/Controllers/GoalController.php`:
    инжектится `CategoryService`, в `create()`/`edit()`
    передаётся `listAvailable()` для select.
11. Созданы вью:
    - `resources/views/categories/index.blade.php` —
      grid карточек, у системных нет кнопок управления,
      у пользовательских — «Изменить» и «Удалить».
    - `resources/views/categories/create.blade.php` и
      `edit.blade.php` — обёртки над общей формой.
    - `resources/views/categories/_form.blade.php` —
      partial с палитрой пресетов (8 кружков), HTML5
      color picker (`<input type="color">`), live-превью
      бейджа через Alpine.js (`x-data="{ color: ... }"`),
      hidden input для отправки на сервер.
12. Обновлены вью целей:
    - `goals/index.blade.php` — группировка по `category_id`,
      бейдж и левая полоска карточки берут `$cat->color`
      через inline-стили (CSS-переменных не используем).
    - `goals/show.blade.php` — бейдж категории через
      inline-стили + резерв `$goal->category?->color`
      на случай если связь почему-то не загрузилась.
    - `goals/create.blade.php` и `edit.blade.php` —
      select с `$categories` из `CategoryService`,
      опции дополняются меткой «(моя)» для
      пользовательских.
13. Обновлены маршруты `routes/web.php`:
    `Route::resource('categories', CategoryController::class)
    ->except(['show'])->whereNumber('category')` под
    `middleware('auth')`. Show у категории не нужен —
    список и редактирование закрывают потребность.
14. Обновлён `resources/views/dashboard.blade.php`:
    добавлена кнопка «Категории» рядом с «Мои цели».
15. Прогон 13 smoke-сценариев (см. ниже) — все прошли.
16. Создан `docs/stage-04-categories-log.md` (этот файл).

## Созданные файлы

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
- `docs/stage-04-categories-log.md`

## Изменённые файлы

- `app/Http/Controllers/GoalController.php` — инжект
  `CategoryService`, передача `listAvailable()` в формы.
- `app/Http/Requests/GoalRequest.php` — `category_id`
  с проверкой принадлежности.
- `app/Models/Goal.php` — удалены константы категорий,
  заменено `category` на `category_id` в fillable,
  добавлена связь `category()`.
- `app/Services/GoalService.php` — переход на
  `category_id`, eager `with('category')`.
- `resources/views/dashboard.blade.php` — кнопка
  «Категории».
- `resources/views/goals/create.blade.php`,
  `edit.blade.php`, `index.blade.php`, `show.blade.php` —
  всё, что было привязано к старому enum, переехало
  на `$goal->category->...` + inline-стили.
- `routes/web.php` — добавлен `Route::resource('categories', ...)`.

## Краткое описание ключевых изменений в коде

**`app/Services/CategoryService.php::delete`** — главное
бизнес-правило этого этапа. Перед удалением считаем
`$category->goals()->count()`. Если > 0 — кидаем
`DomainException` с правильно склонённым числом целей.
Это даёт пользователю понять не только что удалить нельзя,
но и сколько именно целей надо переместить. Без счётчика
сообщение было бы абстрактным «нельзя — есть цели».

**`app/Http/Requests/GoalRequest.php`** — `Rule::exists`
с вложенным where-замыканием:
```php
Rule::exists('categories', 'id')->where(function ($q) {
    $q->where(function ($q2) {
        $q2->where('is_system', true)
            ->orWhere('user_id', Auth::id());
    });
}),
```
Внешний `where` (от `exists`) задаёт область поиска,
вложенный `where(function...)` — обязательная скобочка,
без неё `OR is_system OR user_id` потеряет приоритет.
Альтернатива — кастомное Rule-объект, но для одной
проверки overkill.

**`resources/views/categories/_form.blade.php`** —
форма работает через Alpine.js `x-data="{ color: '...' }"`
со скрытым полем `<input type="hidden" name="color" :value="color">`.
Преимущество перед классическим подходом (один `<input
type="color" name="color">`): можно одновременно показывать
палитру пресетов И color picker, оба меняют единое
состояние `color`, превью обновляется реактивно. Live-превью
бейджа использует `${color}1A` — это hex с альфа-каналом
~10% (стандартный приём для контрастного фона тэга).

**Inline-стили вместо Tailwind-классов для цветов
категорий.** До этого этапа палитра была захардкожена
в Blade (`bg-indigo-50`/`text-indigo-700` и т.п.), и
Tailwind-purger без проблем включал нужные классы в
сборку. Теперь цвета динамические из БД — Tailwind-классы
неприменимы (purger не знает заранее, какие нужны).
Inline-стили — единственный практичный путь без сложной
конфигурации safelist'а. Для бейджей используется
`background-color: {{ $color }}1A; color: {{ $color }}` —
лёгкий фон + тёмный текст того же тона, читаемо для любого
hex'а.

**`down()` миграции `add_category_id_to_goals`** —
полностью симметричен `up()`: восстанавливает enum-поле,
переносит значения обратно через reverse-map, удаляет
FK и колонку. Пользовательские категории теряют точное
маппирование (мапятся в `other`) — это компромисс,
обратное восстановление не идеально, но работоспособно.

## Использованные команды

```bash
php artisan make:migration create_categories_table
php artisan make:migration add_category_id_to_goals_table --table=goals

# модель/сервис/контроллер/реквест/вью — вручную через Write

php artisan migrate
php artisan route:list

# smoke
php artisan serve --port=8765
# 13 сценариев через curl + tinker (см. ниже)
```

## Как поднять и проверить

```bash
php artisan migrate    # применит две новые миграции
php artisan serve
```

Smoke-чеклист:

- [ ] `/categories` — видно 3 системных (Учёба, Спорт,
      Работа) с цветными кружками и пометкой «системная»,
      без кнопок управления.
- [ ] `/categories/create` — палитра 8 кружков выделяет
      выбранный (ring), color picker открывается, превью
      реагирует на изменение цвета и текста live.
- [ ] Создание новой категории с пресет-цветом → редирект
      на `/categories` с flash «Категория создана.».
- [ ] Создание с произвольным цветом через picker → также OK.
- [ ] Создание с пустым label → ошибка «Укажите название
      категории.».
- [ ] Создание с цветом без `#` → ошибка «Цвет должен
      быть в формате #RRGGBB.».
- [ ] У пользовательской есть кнопки «Изменить» и
      «Удалить»; у системной — нет.
- [ ] `/categories/{id}/edit` для системной → 403.
- [ ] DELETE категории, в которой есть цели → flash
      error «Нельзя удалить категорию: в ней N целей. ...».
- [ ] DELETE категории без целей → удаляется, redirect
      с flash «Категория удалена.».
- [ ] На `/goals/create` в select есть все системные +
      собственные пользователя (с пометкой «(моя)»).
- [ ] Чужие пользовательские категории в select **не
      появляются** (разные пользователи — изолированные
      пространства).
- [ ] POST `/goals` с подделанным `category_id` (чужим
      пользователю) → ошибка «Категория недоступна.».
- [ ] На `/goals` карточки имеют цветную левую полоску
      и бейдж в цвете категории; группы по категориям.
- [ ] На `/goals/{id}` бейдж категории и прогресс-бар
      берут цвет из БД.

## Notes (нетривиальные обоснования)

1. **Почему системные категории вставляются прямо в
   миграции, а не отдельным сидером.** Системные данные —
   не «тестовые» (которые сидер обычно загружает), а
   часть схемы (без них приложение не работает: в `goals`
   есть FK на `category_id`, и без хотя бы одной
   категории невозможно создать цель). Insert в миграции
   гарантирует, что после `php artisan migrate` БД
   готова к работе без дополнительных шагов. Сидеры
   остаются для опциональных тестовых данных
   (`AdminSeeder`).

2. **Каскад при удалении пользователя: почему оба
   FK — CASCADE, и почему это не ломается.** У нас две
   связи: `categories.user_id → users` (CASCADE) и
   `goals.category_id → categories` (CASCADE). При
   удалении user'а MySQL должен каскадно удалить и его
   категории, и его цели. Если порядок неудачный
   (категории первыми), цели в этих категориях
   автоматически каскадно удалятся. Если цели первыми —
   то же самое. Конфликта нет, потому что обе ветки
   ведут к удалению; нет ситуации «удалить категорию,
   но цели остались с висячим FK». Альтернатива
   `goals.category_id RESTRICT` создала бы тупик:
   нельзя удалить ни user'а, ни категорию (RESTRICT
   блокирует первое каскадное удаление). Уровень
   приложения (`CategoryService::delete`) защищает от
   user-инициированного удаления категории с целями
   через дружелюбный exception, FK CASCADE — это
   только для каскадного удаления user'а.

3. **Почему «Другое» убрано из системного списка.**
   По решению пользователя. UX-аргумент: «Другое» —
   ленивая категория, в которую сваливается всё, что
   не подошло, и со временем становится самой большой.
   Лучше заставить пользователя выбрать одну из
   осмысленных или создать свою. Старые цели с
   `category='other'` миграция мапит на «Учёбу» —
   это компромисс (пришлось выбрать одну из трёх
   оставшихся системных), пользователь может вручную
   перевыбрать категорию через edit-форму.

4. **Почему `category_id` в `Rule::exists` через
   замыкание, а не два отдельных правила.** Альтернатива —
   `['exists:categories,id', 'category_belongs_to_user']`,
   где второе — кастомный Rule. Но текущий вариант:
   (а) одна проверка вместо двух (быстрее), (б) не нужен
   отдельный класс правила, (в) понятен с одного взгляда.
   Минус — длинное замыкание прямо в массиве правил;
   если правил станет больше, перенесём в `prepareForValidation()`
   или вынесем в Rule-класс.

5. **Почему palette — публичная константа на контроллере,
   а не отдельный конфиг или enum.** Палитра используется
   только во вью create/edit (через `$palette`). Контроллер —
   единственное место передачи её во вью. Если завтра
   палитра понадобится ещё где-то (например, в /admin
   для глобальной статистики), вынесем в `config/palette.php`
   или `App\Support\Palette`. Сейчас одна точка
   потребления — нет смысла в дополнительной абстракции.

6. **Почему сохраняется hex без normalisation на уровне
   БД (например, через триггер).** Сервис делает
   `strtoupper + trim + regex` в `normalizeColor()`.
   Этого достаточно: вся запись идёт только через сервис,
   обходных путей нет (модель не имеет boot-events,
   контроллер не делает прямой save). Триггер БД был бы
   защитой второго уровня, но добавил бы зависимость
   на конкретную RDBMS и усложнил отладку. Принцип
   «бизнес-логика в сервисе» здесь работает.

7. **Почему карточки целей теперь имеют левую цветную
   полоску (`border-l-4 + border-left-color`), а раньше
   были border всего цвета.** Полоска даёт яркий визуальный
   маркер категории, не «топя» содержимое карточки в
   фоновом цвете. Целая граница в категорийный цвет
   выглядела OK для пресетов, но для пользовательских
   ярких/тёмных цветов карточка теряла читаемость.
   Полоска же работает с любой палитрой.
