# Этап 14 — Мобильная адаптация (Mobile Responsiveness)

**Дата:** 14.05.2026  
**Коммит:** `c42a116`

---

## Что было сделано

Полная адаптация пользовательского интерфейса и админ-панели под мобильные устройства. Затронуты 12 Blade-файлов.

---

## Анализ проблем перед исправлением

Перед внесением изменений был проведён аудит всех Blade-файлов. Выявлены следующие категории проблем:

### Причина глобального горизонтального скролла

Корневая причина — отсутствие `min-w-0` на flex-контейнере главной колонки в `layouts/admin.blade.php`:

```html
<!-- ДО: flex-дочерний элемент без ограничения -->
<div class="flex-1 md:ml-64 flex flex-col">
```

В CSS flex-модели дочерний элемент по умолчанию имеет `min-width: auto`, что позволяет ему расширяться под контент — даже если контент шире viewport. Без `min-w-0` широкая таблица внутри могла растягивать всю страницу, даже если у таблицы был `overflow-x-auto`.

```html
<!-- ПОСЛЕ: min-w-0 ограничивает flex-child -->
<div class="flex-1 md:ml-64 flex flex-col min-w-0">
```

### Прочие проблемы

| Проблема | Файлы | Тип |
|---|---|---|
| Таблицы без горизонтального скролла | 5 admin index-страниц | Таблица |
| `p-6` на main — слишком большие отступы на mobile | `layouts/admin.blade.php` | Layout |
| `px-6` на topbar — сжимает контент на узких экранах | `layouts/admin.blade.php` | Layout |
| Кнопка «Выйти» — текст вместо иконки | `layouts/admin.blade.php` | UI |
| Mobile drawer без ARIA, escape, scroll lock | `layouts/admin.blade.php` | Accessibility |
| `flex` без `flex-wrap` на группах кнопок | goals, categories, dashboard | Layout |
| Avatar-блок профиля не переносится в колонку | `profile/index.blade.php` | Layout |
| Код верификации с фиксированным `tracking` | `auth/verify-email.blade.php` | Typography |
| Форма очистки уведомлений без `flex-wrap` | `admin/notifications/index.blade.php` | Layout |
| Тост-контейнер на `goals/show` за пределами экрана | `goals/show.blade.php` | Layout |
| Отсутствует мобильная навигация на дашборде | `dashboard.blade.php` | Navigation |

---

## 1. Admin Layout (`layouts/admin.blade.php`)

Самый объёмный и критичный файл. Изменения разбиты по категориям.

### 1.1 Глобальный x-cloak стиль

```html
<!-- Добавлено в <head>, до @stack('head') -->
<style>[x-cloak] { display: none !important; }</style>
```

Ранее каждая страница, использующая Alpine.js `x-cloak`, должна была сама добавлять этот стиль через `@push('head')`. Теперь он определён глобально в layout — все admin-страницы защищены от мигания при гидратации Alpine.

### 1.2 Alpine-компонент `adminLayout()`

Заменён inline `x-data="{ sidebarOpen: false }"` на вызов именованной функции:

```html
<!-- ДО -->
<div class="min-h-screen flex" x-data="{ sidebarOpen: false }">

<!-- ПОСЛЕ -->
<div class="min-h-screen flex"
     x-data="adminLayout()"
     @keydown.escape.window="sidebarOpen && closeSidebar()">
```

Функция определена в `<script>` в конце body (до `@yield('scripts')`), поэтому Alpine находит её при инициализации:

```javascript
function adminLayout() {
    return {
        sidebarOpen: false,

        openSidebar() {
            this.sidebarOpen = true;
            document.body.style.overflow = 'hidden'; // блокировка скролла фона
            this.$nextTick(() => {
                document.getElementById('admin-mobile-sidebar')?.focus();
            });
        },

        closeSidebar() {
            this.sidebarOpen = false;
            document.body.style.overflow = ''; // возврат скролла
            this.$nextTick(() => this.$refs.menuBtn?.focus()); // фокус на burger
        },
    };
}
```

**Почему `$nextTick`:** Alpine обновляет DOM асинхронно. Фокус нужно выставлять после того, как элемент стал видимым — иначе `focus()` игнорируется.

### 1.3 Mobile sidebar drawer

| Атрибут | ДО | ПОСЛЕ |
|---|---|---|
| Ширина | `w-64` | `w-72` — чуть больше пространства для текста |
| Overlay | `bg-black/40`, без ARIA | `bg-black/50`, `aria-hidden="true"`, анимация opacity |
| Aside | нет атрибутов | `id="admin-mobile-sidebar"`, `role="dialog"`, `aria-modal="true"`, `aria-label`, `tabindex="-1"` |
| Transitions | `duration-200` без `ease` | `duration-250 ease-out` (enter), `duration-200 ease-in` (leave) |
| Закрытие | только клик по overlay | + клавиша Escape (`@keydown.escape.window`) |
| Фокус | не управляется | при открытии — `focus()` на aside; при закрытии — `focus()` на burger |
| Scroll lock | нет | `document.body.style.overflow = 'hidden'` при открытии |

`tabindex="-1"` на `<aside>` позволяет ему получать фокус программно (через `.focus()`), не попадая при этом в обычный tab-порядок.

### 1.4 Кнопка бургера

```html
<!-- ДО -->
<button @click="sidebarOpen = true" class="md:hidden text-gray-600 hover:text-gray-900">
    <svg class="w-6 h-6">...</svg>
</button>

<!-- ПОСЛЕ -->
<button x-ref="menuBtn"
        @click="openSidebar()"
        aria-label="Открыть меню"
        :aria-expanded="sidebarOpen.toString()"
        aria-controls="admin-mobile-sidebar"
        class="md:hidden flex-shrink-0 p-2 -ml-1 rounded-lg text-gray-600
               hover:text-gray-900 hover:bg-gray-100 active:bg-gray-200
               focus:outline-none focus:ring-2 focus:ring-indigo-500 transition">
    <svg class="w-5 h-5" aria-hidden="true">...</svg>
</button>
```

- `x-ref="menuBtn"` — для возврата фокуса после закрытия drawer
- `:aria-expanded` — скринридеры озвучивают состояние меню
- `aria-controls` — связывает кнопку с управляемым элементом
- `p-2 rounded-lg` — tap target минимум 44×44px согласно WCAG
- `flex-shrink-0` — кнопка не сжимается при длинном заголовке страницы

### 1.5 Кнопка выхода (logout)

```html
<!-- ДО -->
<button type="submit"
        class="text-sm text-gray-600 hover:text-gray-900 px-3 py-1.5 rounded-lg hover:bg-gray-100">
    Выйти
</button>

<!-- ПОСЛЕ -->
<button type="submit"
        aria-label="Выйти из системы"
        title="Выйти"
        class="p-2 rounded-lg text-gray-500 hover:text-red-600 hover:bg-red-50
               active:bg-red-100 focus:outline-none focus:ring-2 focus:ring-red-400 transition">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"
         stroke-width="2" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
    </svg>
</button>
```

Текст «Выйти» заменён на SVG-иконку (стрелка с дверью). Смысл сохранён через `aria-label` и `title` (tooltip на hover). Hover-состояние — красный цвет, визуально соответствует деструктивному действию.

### 1.6 Topbar и main padding

```html
<!-- ДО -->
<div class="px-6 py-3 ...">      <!-- topbar: 24px по горизонтали -->
<main class="flex-1 p-6">        <!-- content: 24px со всех сторон -->

<!-- ПОСЛЕ -->
<div class="px-4 sm:px-6 py-3 ...">  <!-- topbar: 16px mobile / 24px ≥640px -->
<main class="flex-1 p-4 sm:p-6">     <!-- content: 16px mobile / 24px ≥640px -->
```

На экране 320px экономит 16px полезной ширины — существенно для узких устройств.

---

## 2. Мобильная навигация на дашборде (`dashboard.blade.php`)

Поскольку `layouts/app.blade.php` (пользовательский layout) не содержит navbar, навигация была добавлена прямо в `dashboard.blade.php` в блоке `sm:hidden` — только на мобильных, только на главной странице.

### 2.1 Структура

```
sm:hidden блок (x-data="dashboardMobileNav()")
├── Топ-бар
│   ├── Кнопка-бургер (x-ref="burgerBtn", aria-expanded)
│   ├── Название приложения (app.name)
│   └── Правая группа
│       ├── Колокол уведомлений (x-data="notificationsBell(...)" — вложенный компонент)
│       ├── Аватар / ссылка на профиль
│       └── Кнопка выхода (logout form)
├── Затемнение фона (overlay)
│   └── x-transition opacity 300ms/200ms
│   └── @click="closeDrawer()"
└── Drawer (aside#mobile-nav-drawer)
    ├── Шапка: название + кнопка закрыть
    ├── Блок пользователя (аватар, имя, email)
    ├── nav: Мои цели, Категории, Достижения (SVG иконки)
    └── Подвал: Профиль, Выйти

hidden sm:flex блок (desktop nav — без изменений)
```

### 2.2 Alpine-компонент `dashboardMobileNav()`

```javascript
function dashboardMobileNav() {
    return {
        open: false,

        openDrawer() {
            this.open = true;
            document.body.style.overflow = 'hidden';
            this.$nextTick(() => {
                const focusable = this.$refs.drawer?.querySelector(
                    'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
                );
                focusable?.focus(); // фокус на первый интерактивный элемент в drawer
            });
        },

        closeDrawer() {
            this.open = false;
            document.body.style.overflow = '';
            this.$nextTick(() => this.$refs.burgerBtn?.focus()); // возврат фокуса
        },

        trapFocus(e) { // вешается через @keydown.tab на aside
            if (!this.open) return;
            const focusable = [...this.$refs.drawer.querySelectorAll(
                'a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )];
            if (!focusable.length) return;
            const first = focusable[0];
            const last = focusable[focusable.length - 1];
            if (e.shiftKey) {
                if (document.activeElement === first) { e.preventDefault(); last.focus(); }
            } else {
                if (document.activeElement === last) { e.preventDefault(); first.focus(); }
            }
        },
    };
}
```

`trapFocus` реализует циклический tab внутри drawer — стандартное требование для модальных диалогов (WCAG 2.1, критерий 2.1.2).

### 2.3 Вложенный компонент уведомлений

В топ-баре мобильной навигации переиспользован Alpine-компонент `notificationsBell`, уже определённый в `@push('scripts')`. Вложенный `x-data` работает независимо от родительского — два экземпляра (mobile + desktop) существуют одновременно, но только один видим в любой момент (CSS breakpoint `sm:hidden`/`hidden sm:flex`).

### 2.4 Анимации drawer

Идентичны паттерну из `layouts/admin.blade.php`:

```html
x-transition:enter="transition transform duration-300 ease-out"
x-transition:enter-start="-translate-x-full"
x-transition:enter-end="translate-x-0"
x-transition:leave="transition transform duration-200 ease-in"
x-transition:leave-start="translate-x-0"
x-transition:leave-end="-translate-x-full"
```

Классы `-translate-x-full` / `translate-x-0` уже генерировались Tailwind CDN для admin-drawer, поэтому повторное использование безопасно без дополнительной конфигурации.

---

## 3. Пользовательские страницы

### 3.1 Заголовки с кнопками (`goals/index.blade.php`, `categories/index.blade.php`)

```html
<!-- ДО -->
<div class="flex items-center justify-between mb-6">
    ...
    <div class="flex items-center gap-3">

<!-- ПОСЛЕ -->
<div class="flex flex-wrap items-start justify-between gap-3 mb-6">
    ...
    <div class="flex flex-wrap items-center gap-2">
```

`flex-wrap` позволяет группе кнопок перенестись под заголовок на узких экранах. `items-start` вместо `items-center` предотвращает вертикальное смещение при переносе.

### 3.2 Страница цели (`goals/show.blade.php`)

**Тост-контейнер:**
```html
<!-- ДО -->
class="... max-w-sm"

<!-- ПОСЛЕ -->
class="... w-[calc(100vw-2rem)] sm:w-auto sm:max-w-sm"
```
На мобильном — тост занимает ширину экрана минус 2rem отступы с каждой стороны.

**Заголовок страницы:**
```html
<!-- ДО -->
<div class="flex items-center justify-between mb-6">

<!-- ПОСЛЕ -->
<div class="flex flex-wrap items-center justify-between gap-3 mb-6">
    ...inner div: flex flex-wrap items-center gap-2
```

### 3.3 Профиль (`profile/index.blade.php`)

**Аватар + поле загрузки:**
```html
<!-- ДО -->
<div class="flex items-center gap-4">

<!-- ПОСЛЕ -->
<div class="flex flex-col sm:flex-row gap-4">
```
На мобильном аватар отображается над полем загрузки, а не рядом — исключает горизонтальное сжатие.

### 3.4 Верификация email (`auth/verify-email.blade.php`)

**Поле ввода кода:**
```html
<!-- ДО -->
class="... text-3xl ... tracking-[0.4em]"

<!-- ПОСЛЕ -->
class="... text-2xl sm:text-3xl ... tracking-[0.25em] sm:tracking-[0.4em]"
```
Уменьшенный `tracking` на мобильных предотвращает выход цифр за края поля на устройствах с шириной < 360px.

---

## 4. Admin-страницы

### 4.1 Таблицы (`admin/users`, `goals`, `categories`, `archive`, `notifications`)

На всех пяти страницах таблицы обёрнуты в контейнер с горизонтальным скроллом:

```html
<x-card padding="p-0" class="overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full ...">
            ...
        </table>
    </div>
</x-card>
```

**Важно:** `overflow-x-auto` на самом `<div>` — не на `<table>` и не на `<x-card>`. Именно это сочетание с `min-w-0` на главной колонке layout (п. 1.2) даёт правильное поведение: таблица скроллится внутри своего контейнера, страница — нет.

### 4.2 Форма очистки уведомлений (`admin/notifications/index.blade.php`)

```html
<!-- ДО -->
<form ... class="flex items-center gap-2">

<!-- ПОСЛЕ -->
<form ... class="flex flex-wrap items-center gap-2">
```

Форма содержит input (`w-20`) + span «дней» + кнопку «Очистить прочитанные» (~150px). Без `flex-wrap` на экране шириной 320px они могли не помещаться в одну строку с заголовком страницы.

---

## Итоговая таблица изменений

| Файл | Тип изменения | Ключевой fix |
|---|---|---|
| `layouts/admin.blade.php` | Полный рефакторинг | `min-w-0`, иконка logout, ARIA drawer, `adminLayout()`, padding mobile |
| `dashboard.blade.php` | Добавление mobile nav | Drawer-навигация, `dashboardMobileNav()`, уведомления в topbar |
| `admin/users/index.blade.php` | Таблица | `overflow-x-auto` wrapper |
| `admin/goals/index.blade.php` | Таблица | `overflow-x-auto` wrapper |
| `admin/categories/index.blade.php` | Таблица | `overflow-x-auto` wrapper |
| `admin/archive/index.blade.php` | Таблица | `overflow-x-auto` wrapper |
| `admin/notifications/index.blade.php` | Таблица + форма | `overflow-x-auto` wrapper, `flex-wrap` на purge-форме |
| `goals/index.blade.php` | Header | `flex-wrap` на группе кнопок |
| `goals/show.blade.php` | Toast + header | `w-[calc(100vw-2rem)]`, `flex-wrap` |
| `categories/index.blade.php` | Header | `flex-wrap` на группе кнопок |
| `profile/index.blade.php` | Avatar block | `flex-col sm:flex-row` |
| `auth/verify-email.blade.php` | Code input | Адаптивный `tracking` и `text-size` |

---

## Принятые решения

**Почему мобильная навигация только на `/dashboard`?**  
Пользовательский `layouts/app.blade.php` — минималистичный layout без navbar (только `@yield('content')`). Добавление навигации в layout затронуло бы все страницы и требовало бы передачи `$unreadNotifications` в каждый контроллер через `ViewComposer`. Решение «nav только на дашборде» изолирует изменения и не требует дополнительной архитектуры.

**Почему drawer, а не bottom sheet или hamburger menu?**  
Left-side drawer — стандартный паттерн для admin-панелей (Material Design, Ant Design, Bootstrap). Уже использовался в `layouts/admin.blade.php` — переиспользование паттерна снижает когнитивную нагрузку.

**Почему не изменён breakpoint с `md` на `sm` в admin-layout?**  
Admin-layout использует `md` (768px) как точку перехода между мобильным drawer и desktop sidebar. Изменение breakpoint потребовало бы ревизии всех admin-страниц. Текущее значение соответствует стандартному поведению планшетов (768px) и является корректным выбором для panel-интерфейсов.
