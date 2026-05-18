<style>
/*
 * ════════════════════════════════════════════════════════════════
 *  ТЁМНАЯ ТЕМА — переопределение Tailwind-классов для html.dark
 *
 *  КАК РАБОТАЕТ
 *  ─────────────
 *  • Tailwind генерирует CSS с низкой специфичностью (.class).
 *    Наши правила используют html.dark .class (специфичность выше),
 *    поэтому побеждают без !important. Там где Tailwind CDN может
 *    вставить свой <style> позже — добавлено !important для страховки.
 *
 *  ИСКЛЮЧЕНИЕ ДЛЯ РАМОК КАРТОЧЕК
 *  ────────────────────────────────
 *  Правила border-color намеренно написаны БЕЗ !important.
 *  Это нужно чтобы inline-стиль «style="border-left-color:#…"»
 *  на карточках с цветной акцентной полоской не перебивался.
 *
 *  КАК МЕНЯТЬ ЦВЕТА
 *  ──────────────────
 *  Каждый блок ниже отвечает за одну группу элементов.
 *  Просто замените hex-значение или rgba() на нужный цвет.
 *  rgba(R,G,B, прозрачность) — последнее число от 0 (невидимо)
 *  до 1 (полностью непрозрачно). Например, .10 = 10% непрозрачности.
 * ════════════════════════════════════════════════════════════════
 */


/* ────────────────────────────────────────────────────────────────
   АНИМАЦИЯ ПЕРЕХОДОВ
   Цвета плавно меняются только когда пользователь вручную
   переключает тему — не при загрузке страницы.
   Длительность: 0.25s. Можно изменить на 0.15s (быстрее)
   или 0.4s (медленнее).
   ──────────────────────────────────────────────────────────────── */
html.theme-transitioning *,
html.theme-transitioning *::before,
html.theme-transitioning *::after {
    transition: background-color 0.25s ease, color 0.25s ease,
                border-color 0.25s ease, box-shadow 0.25s ease !important;
}


/* ════════════════════════════════════════════════════════════════
   ОСНОВНЫЕ ПОВЕРХНОСТИ
   ════════════════════════════════════════════════════════════════ */

/*
 * ФОН СТРАНИЦЫ (самый тёмный слой — «подложка» за всеми карточками).
 * Используется на body тегах лейаутов пользователя и администратора.
 * Сделайте темнее → #080f1e, светлее → #1a2540
 */
html.dark body.bg-gray-50,
html.dark body.bg-gray-100 { background-color: #0f172a !important; }

/*
 * КАРТОЧКИ / ПАНЕЛИ (белые блоки поверх фона страницы).
 * bg-white   — основные карточки (x-card), выпадающие меню, drawer.
 * bg-gray-50 — вторичные секции внутри карточки (шапка drawer и т.п.).
 * Сделайте темнее → #172035, светлее → #243a55
 */
html.dark .bg-white    { background-color: #1e293b !important; }
html.dark .bg-gray-50  { background-color: #1e293b !important; }

/*
 * ВТОРИЧНЫЙ ФОН — кнопки навигации («Мои цели», «Категории» и т.д.),
 * фон поля ввода в фильтрах, шапка мобильного меню.
 * bg-gray-100 — нормальное состояние.
 * bg-gray-200 — состояние hover (чуть светлее).
 * Если кнопки сливаются с карточкой — сделайте их светлее: #3e5068
 */
html.dark .bg-gray-100 { background-color: #334155 !important; }
html.dark .bg-gray-200 { background-color: #475569 !important; }


/* ════════════════════════════════════════════════════════════════
   ТЕКСТ
   Шкала от самого яркого (900) до самого приглушённого (400).
   Чем меньше цифра — тем тусклее текст в светлой теме,
   тем темнее он становится в тёмной (меньше контраст).
   ════════════════════════════════════════════════════════════════ */

/* Основной текст (заголовки, названия целей, имена пользователей).
   Сделайте ярче → #f8fafc, приглушите → #dde6f0 */
html.dark .text-gray-900 { color: #f1f5f9 !important; }

/* Подзаголовки, жирные метки. */
html.dark .text-gray-800 { color: #e2e8f0 !important; }

/* Обычный текст в формах, описания, подписи кнопок. */
html.dark .text-gray-700 { color: #cbd5e1 !important; }

/* Вспомогательный текст (email в профиле, подпись роли). */
html.dark .text-gray-600 { color: #94a3b8 !important; }

/* Мелкие подписи — верхняя строка KPI-карточек («Активные цели»,
   «Выполнено задач» и т.д.), счётчики, даты.
   Специально немного ярче чем стандартный slate-500
   чтобы читалось на тёмном фоне карточки.
   Приглушите → #78909c, ярче → #b0bec5 */
html.dark .text-gray-500 { color: #94a3b8 !important; }

/* Самые приглушённые подсказки, неактивные иконки. */
html.dark .text-gray-400 { color: #64748b !important; }


/* ════════════════════════════════════════════════════════════════
   РАМКИ (border-color)
   !! БЕЗ !important — чтобы цветная акцентная полоска карточек
   (inline style="border-left-color:#…") оставалась видна. !!
   ════════════════════════════════════════════════════════════════ */

/* Внешние рамки карточек, разделители внутри панелей.
   Сделайте заметнее → #4a5568, тоньше (незаметнее) → #252f3f */
html.dark .border-gray-50,
html.dark .border-gray-100 { border-color: #334155; }

/* Рамки инпутов, таблиц, вторичных блоков. */
html.dark .border-gray-200 { border-color: #475569; }

/* Рамки в нормальном состоянии (Tailwind default для форм). */
html.dark .border-gray-300 { border-color: #64748b; }


/* ════════════════════════════════════════════════════════════════
   ИНТЕРАКТИВНЫЕ СОСТОЯНИЯ
   ════════════════════════════════════════════════════════════════ */

/* Hover (наведение мыши) — должен быть чуть светлее базового фона. */
html.dark .hover\:bg-gray-50:hover  { background-color: #334155 !important; }
html.dark .hover\:bg-gray-100:hover { background-color: #475569 !important; }
html.dark .hover\:bg-gray-200:hover { background-color: #64748b !important; }

/* Active (момент клика) — ещё чуть светлее hover. */
html.dark .active\:bg-gray-100:active,
html.dark .active\:bg-gray-200:active { background-color: #64748b !important; }

/* Разделительные линии внутри списков (divide-y, уведомления и т.д.). */
html.dark .divide-y > * + *,
html.dark .divide-gray-50 > * + *,
html.dark .divide-gray-100 > * + * { border-color: #334155 !important; }


/* ════════════════════════════════════════════════════════════════
   ТЕНИ
   Число после rgba — непрозрачность (.5 = 50%).
   Увеличьте чтобы карточки сильнее «отрывались» от фона.
   ════════════════════════════════════════════════════════════════ */
html.dark .shadow-sm  { box-shadow: 0 1px 2px 0 rgba(0,0,0,.5) !important; }
html.dark .shadow     { box-shadow: 0 1px 3px 0 rgba(0,0,0,.5),
                                    0 1px 2px -1px rgba(0,0,0,.4) !important; }
html.dark .shadow-lg  { box-shadow: 0 10px 15px -3px rgba(0,0,0,.6),
                                    0 4px 6px -4px rgba(0,0,0,.4) !important; }
html.dark .shadow-2xl { box-shadow: 0 25px 50px -12px rgba(0,0,0,.8) !important; }


/* ════════════════════════════════════════════════════════════════
   ПОЛЯ ВВОДА (input, textarea, select)
   Фон совпадает с фоном карточки — поле «утоплено» в неё.
   Цвет рамки чуть светлее рамки карточки для различимости.
   ════════════════════════════════════════════════════════════════ */
html.dark input:not([type=checkbox]):not([type=radio]):not([type=range]):not([type=color]):not([type=submit]):not([type=button]),
html.dark textarea,
html.dark select {
    background-color: #1e293b !important;   /* = фон карточки */
    color: #f1f5f9 !important;              /* = text-gray-900 */
    border-color: #475569 !important;       /* = border-gray-200 */
}

/* Плейсхолдер (подсказка внутри пустого поля). */
html.dark input::placeholder,
html.dark textarea::placeholder { color: #64748b !important; }


/* ════════════════════════════════════════════════════════════════
   ЦВЕТНЫЕ ПАНЕЛИ — рекомендации, дедлайны, flash-уведомления
   ────────────────────────────────────────────────────────────────
   Используем rgba-прозрачность вместо сплошного цвета.
   Так сквозь панель просвечивает тёмный фон карточки —
   нет эффекта «неон на чёрном».

   Последнее число в rgba() — это прозрачность:
     .06 = очень слабый намёк на цвет
     .12 = лёгкий тинт (текущее значение)
     .20 = заметный цветной блок
     .35 = полупрозрачный (используется для рамок)
   ════════════════════════════════════════════════════════════════ */

/*
 * СИНИЕ / ИНДИГО панели
 * Используются: рекомендации типа «info», бейджи на достижениях.
 * Фон (.10 = 10%): rgba(99,102,241,…) — синий индиго.
 * Текст: #a5b4fc — мягкий сиреневый (indigo-300).
 *   Ярче → #c7d2fe (indigo-200), тусклее → #818cf8 (indigo-400)
 */
html.dark .bg-indigo-50      { background-color: rgba(99,102,241,.10) !important; }
html.dark .bg-indigo-50\/40  { background-color: rgba(99,102,241,.15) !important; }
html.dark .border-indigo-200 { border-color: rgba(99,102,241,.35); }
html.dark .text-indigo-900,
html.dark .text-indigo-800   { color: #a5b4fc !important; }

/*
 * КРАСНЫЕ панели
 * Используются: рекомендации «danger», просроченные дедлайны,
 *               flash-сообщения об ошибках, строки в таблице admin.
 * bg-red-50\/30 — фон строки просроченной цели в таблице admin.
 * Текст: #fc9090 — мягкий розово-красный.
 *   Ярче → #fca5a5 (red-300), тусклее → #f87171 (red-400)
 */
html.dark .bg-red-50         { background-color: rgba(239,68,68,.10) !important; }
html.dark .bg-red-50\/30     { background-color: rgba(239,68,68,.08) !important; }
html.dark .border-red-200    { border-color: rgba(239,68,68,.35); }
html.dark .text-red-900,
html.dark .text-red-800,
html.dark .text-red-700      { color: #fc9090 !important; }

/*
 * ЯНТАРНЫЕ / ОРАНЖЕВЫЕ панели
 * Используются: рекомендации «warning», дедлайны < 7 дней,
 *               flash-предупреждения, неподтверждённый email.
 * text-amber-600 — мелкая подпись под предупреждением (чуть темнее).
 * Текст: #f5c469 — тёплый золотистый.
 *   Ярче → #fde68a (amber-200), тусклее → #f59e0b (amber-500)
 */
html.dark .bg-amber-50       { background-color: rgba(245,158,11,.10) !important; }
html.dark .border-amber-200  { border-color: rgba(245,158,11,.35); }
html.dark .text-amber-900,
html.dark .text-amber-800,
html.dark .text-amber-700    { color: #f5c469 !important; }
html.dark .text-amber-600    { color: #f0b840 !important; }

/*
 * ЗЕЛЁНЫЕ панели
 * Используются: flash-успех, подтверждённый email в профиле.
 * Текст: #6dd8a0 — мягкий зелёный.
 *   Ярче → #86efac (green-300), тусклее → #4ade80 (green-400)
 */
html.dark .bg-green-50       { background-color: rgba(34,197,94,.10) !important; }
html.dark .border-green-200  { border-color: rgba(34,197,94,.35); }
html.dark .text-green-800,
html.dark .text-green-700,
html.dark .text-green-600    { color: #6dd8a0 !important; }

/*
 * СИНИЕ панели (sky/blue — misc)
 */
html.dark .bg-blue-50        { background-color: rgba(59,130,246,.10) !important; }


/* ════════════════════════════════════════════════════════════════
   БЕЙДЖИ-ПИЛЮЛИ (bg-{цвет}-100 + text-{цвет}-700)
   ────────────────────────────────────────────────────────────────
   Используются повсюду: статус цели («активна», «завершена»,
   «в архиве»), роль пользователя, категории, дедлайны.
   Та же rgba-логика — тинт поверх фона карточки.
   Прозрачность .18 = чуть насыщеннее чем у панелей (.10).
   Увеличьте до .25 если бейджи нужно сделать заметнее.
   ════════════════════════════════════════════════════════════════ */

/* Зелёный бейдж — «завершена», «подтверждено». */
html.dark .bg-green-100      { background-color: rgba(34,197,94,.18) !important; }
html.dark .text-green-700    { color: #6dd8a0 !important; }

/* Изумрудный бейдж — достижения («Получено …»). */
html.dark .bg-emerald-100    { background-color: rgba(16,185,129,.18) !important; }
html.dark .text-emerald-700  { color: #5ecfa0 !important; }

/* Янтарный бейдж — предупреждения, дедлайны < 7 дней. */
html.dark .bg-amber-100      { background-color: rgba(245,158,11,.18) !important; }
/* text-amber-700 задан выше в разделе «Янтарные панели» */

/* Красный бейдж — просрочено, ошибка. */
html.dark .bg-red-100        { background-color: rgba(239,68,68,.18) !important; }
/* text-red-700 задан выше в разделе «Красные панели» */

/* Индиго бейдж — «активна», «info». */
html.dark .bg-indigo-100     { background-color: rgba(99,102,241,.18) !important; }
html.dark .text-indigo-700   { color: #818cf8 !important; }

/* Синий бейдж. */
html.dark .bg-blue-100       { background-color: rgba(59,130,246,.18) !important; }
html.dark .text-blue-700     { color: #60a5fa !important; }

/* Небесно-голубой бейдж. */
html.dark .bg-sky-100        { background-color: rgba(14,165,233,.18) !important; }
html.dark .text-sky-700      { color: #38bdf8 !important; }
</style>
