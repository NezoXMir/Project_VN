# Demo REST API — гайд по использованию

API использует ту же session-cookie авторизацию, что и веб-интерфейс
(никаких JWT/Bearer-токенов). Это значит: сначала нужно залогиниться
через `/login`, **сохранив cookie**, и только потом делать API-запросы.

CSRF-защита для `/api/*` отключена — клиенту достаточно cookie от
логина, никаких `X-XSRF-TOKEN` или `X-CSRF-TOKEN` заголовков
передавать не нужно. Это стандартная практика для API в Laravel
(аналогично работает Sanctum).

Все примеры предполагают что dev-сервер запущен:

```bash
php artisan serve
# → http://localhost:8000
```

---

## 1. Базовый flow

Любой клиент (Postman, curl, fetch, мобильный) проходит **2 шага**:

1. **POST `/login`** — отправить email + password (для формы login
   нужен `_token` из её HTML, см. ниже). В ответ получишь
   session-cookie. Сервер запомнит твою авторизацию.

2. **GET/POST `/api/...`** — с тем же cookie. Postman/curl
   автоматически отправляют cookie, если включено их хранение
   (Cookie Jar в Postman, `-b cookies.txt` в curl). Никаких
   дополнительных заголовков не нужно.

> **Важно:** CSRF нужен только на сам `/login` (это веб-форма).
> Все `/api/*` запросы CSRF-свободны.

---

## 2. Полная настройка Postman

### Шаг 1 — окружение

Создай Environment в Postman: `Виртуальный наставник local`.
Переменные:

| Variable | Initial value |
|----------|---------------|
| `base_url` | `http://localhost:8000` |
| `email` | `user@example.com` |
| `password` | `password` |

Включи это окружение в правом верхнем углу.

### Шаг 2 — Cookie Jar

Postman → Settings → General → проверь, что **Automatically follow
redirects** включено и **Cookie Jar** активна (по умолчанию да).
После логина в Cookie Jar появится `virtual_наставник_session` —
это значит ты залогинен.

### Шаг 3 — запрос «Login»

В Pre-request Script этого запроса (нужен только тут — на `/login`
работает CSRF):

```javascript
pm.sendRequest({
    url: pm.environment.get('base_url') + '/login',
    method: 'GET',
}, (err, res) => {
    const m = res.text().match(/name="_token"\s+value="([^"]+)"/);
    if (m) pm.variables.set('csrf_token', m[1]);
});
```

Сам запрос:

```
POST {{base_url}}/login

Body → x-www-form-urlencoded:
  email     = {{email}}
  password  = {{password}}
  _token    = {{csrf_token}}
```

→ Send. Должно прийти **302** + cookies сохранятся в Postman.

### Шаг 4 — GET-запросы (читать данные)

GET-запросы ничего не требуют, кроме cookie сессии:

```
GET {{base_url}}/api/goals
GET {{base_url}}/api/goals/49
GET {{base_url}}/api/user/stats
```

Никакого body, никаких заголовков — просто Send. Должен прийти
JSON.

### Шаг 5 — POST-запрос (создать цель)

```
POST {{base_url}}/api/goals

Headers:
  Content-Type: application/json
  Accept:       application/json

Body → raw → JSON:
{
    "title": "Цель из API",
    "category_id": 1,
    "description": "Опционально",
    "deadline": "2026-12-31"
}
```

Поля `description` и `deadline` опциональны. `category_id` должен
соответствовать существующей системной (1, 2, 3) или твоей
пользовательской категории.

Должен прийти **201 Created** с JSON созданной цели.

---

## 3. Все 4 эндпоинта

### `GET /api/goals` — список целей пользователя

**Headers:** `Accept: application/json` (опционально, JSON и так будет)

**Body:** —

**Ответ 200:**

```json
{
    "data": [
        {
            "id": 51,
            "title": "Вакансии",
            "description": null,
            "status": "active",
            "progress": 0,
            "deadline": null,
            "category": {
                "id": 3,
                "label": "Работа",
                "color": "#F59E0B"
            },
            "created_at": "2026-05-09T15:54:56+03:00"
        }
    ]
}
```

Поля:
- `status` — `"active"` / `"completed"` / `"archived"`
- `progress` — 0-100, процент выполненных задач
- `deadline` — ISO 8601 или `null`
- `category` — связанная категория

---

### `GET /api/goals/{id}` — детали + подцели + задачи

**Headers:** —

**Body:** —

**Ответ 200:**

```json
{
    "data": {
        "id": 49,
        "title": "Диплом",
        "description": null,
        "status": "active",
        "progress": 0,
        "deadline": "2026-05-17T00:00:00+03:00",
        "category": {
            "id": 1,
            "label": "Учёба",
            "color": "#4F46E5"
        },
        "created_at": "2026-05-09T15:54:16+03:00",
        "subtasks": [
            {
                "id": 20,
                "title": "Проект",
                "position": 1,
                "tasks": [
                    {
                        "id": 61,
                        "title": "Дописать проект",
                        "is_done": false,
                        "completed_at": null,
                        "position": 1
                    }
                ]
            }
        ]
    }
}
```

Поля задач:
- `is_done` — `true`/`false`
- `completed_at` — ISO 8601 если завершена, иначе `null`
- `position` — порядок отображения

**Возможные ошибки:**
- **403** — цель чужая (`GoalPolicy::view` блокирует)
- **404** — цели с таким id не существует

---

### `POST /api/goals` — создать цель

**Headers:**

```
Content-Type: application/json
Accept:       application/json
```

**Body (JSON):**

```json
{
    "title": "Подготовиться к экзамену",
    "category_id": 1,
    "description": "Опционально, до 5000 символов",
    "deadline": "2026-06-15"
}
```

Обязательные поля:
- `title` — строка, до 200 символов
- `category_id` — integer, должен быть либо системной (1, 2, 3),
  либо твоей собственной категорией

Опциональные:
- `description` — строка, до 5000 символов, или null
- `deadline` — формат `YYYY-MM-DD`, должен быть в будущем
  (хотя бы завтра); или null

**Ответ 201:**

```json
{
    "data": {
        "id": 73,
        "title": "Подготовиться к экзамену",
        "description": "Опционально, до 5000 символов",
        "status": "active",
        "progress": 0,
        "deadline": "2026-06-15T00:00:00+03:00",
        "category": {
            "id": 1,
            "label": "Учёба",
            "color": "#4F46E5"
        },
        "created_at": "2026-05-10T15:30:42+03:00"
    }
}
```

**Ошибки:**

- **422** — валидация:
  ```json
  {
      "message": "Укажите название цели.",
      "errors": {
          "title": ["Укажите название цели."],
          "deadline": ["Дедлайн должен быть в будущем."]
      }
  }
  ```
- **302** — нет cookie сессии (не залогинен, редирект на `/login`)

---

### `GET /api/user/stats` — KPI пользователя

**Headers:** —

**Body:** —

**Ответ 200:**

```json
{
    "data": {
        "active_goals": 3,
        "completed_goals": 0,
        "archived_goals": 0,
        "total_tasks": 3,
        "done_tasks": 0,
        "overall_progress": 0,
        "streak": 0,
        "activity_30d": [
            {"date": "2026-04-11", "count": 0},
            {"date": "2026-04-12", "count": 0},
            {"date": "2026-04-13", "count": 0}
        ],
        "upcoming_deadlines": [
            {
                "id": 50,
                "title": "Футбол",
                "deadline": "2026-05-10T00:00:00+03:00",
                "category_id": 2
            }
        ]
    }
}
```

Поля:
- `streak` — дней подряд с хотя бы одной завершённой задачей
- `overall_progress` — процент завершённых задач от общего
- `activity_30d` — массив **всегда длиной 30**, даже если в эти
  дни не было активности (для удобства Chart.js)
- `upcoming_deadlines` — активные цели с дедлайном в ближайшие
  14 дней или просроченные

---

## 4. Возможные коды ошибок

| Код | Что значит | Как починить |
|-----|------------|--------------|
| **200** | Успех (GET) | — |
| **201** | Успех создания (POST) | — |
| **302** | Не залогинен | Сделать POST /login и сохранить cookie |
| **403** | Чужой ресурс | Этот ID не принадлежит твоему юзеру |
| **404** | Ресурс не существует | Проверить id |
| **422** | Валидация не прошла | Проверить тело JSON, см. поле `errors` |
| **500** | Серверная ошибка | Глянуть `storage/logs/laravel.log` |

> **419 (CSRF token mismatch)** для `/api/*` больше не возникает —
> CSRF-проверка отключена для этого префикса (см.
> `bootstrap/app.php` → `validateCsrfTokens(except: ['api/*'])`).

---

## 5. Примеры на других языках

### curl (bash)

```bash
#!/bin/bash
BASE="http://localhost:8000"
JAR=$(mktemp)

# 1. Login (тут CSRF нужен — это веб-форма)
TOKEN=$(curl -s -c "$JAR" "$BASE/login" \
    | grep -oP 'name="_token"\s+value="\K[^"]+' | head -1)

curl -s -b "$JAR" -c "$JAR" -X POST "$BASE/login" \
    -d "_token=$TOKEN&email=user@example.com&password=password" \
    -o /dev/null

# 2. GET API — никаких CSRF
echo "=== Список целей ==="
curl -s -b "$JAR" "$BASE/api/goals" | python -m json.tool

# 3. POST API — никаких CSRF
echo "=== Создание цели ==="
curl -s -b "$JAR" -X POST "$BASE/api/goals" \
    -H "Content-Type: application/json" \
    -d '{"title":"Через curl","category_id":1}' \
    | python -m json.tool

rm "$JAR"
```

### JavaScript (fetch из браузера)

После логина в UI открой DevTools (F12) → Console:

```javascript
// GET — браузер сам подставит cookie
const goals = await fetch('/api/goals').then(r => r.json());
console.log(goals);

// POST — никаких CSRF-заголовков не нужно для /api/*
const newGoal = await fetch('/api/goals', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
    body: JSON.stringify({
        title: 'Через консоль браузера',
        category_id: 1,
    }),
}).then(r => r.json());

console.log(newGoal);
```

### Python (requests)

```python
import requests
import re

BASE = 'http://localhost:8000'
session = requests.Session()

# 1. Login (CSRF нужен только тут)
login_page = session.get(f'{BASE}/login').text
token = re.search(r'name="_token"\s+value="([^"]+)"', login_page).group(1)
session.post(f'{BASE}/login', data={
    '_token': token,
    'email': 'user@example.com',
    'password': 'password',
})

# 2. GET — cookie уже у session
goals = session.get(f'{BASE}/api/goals').json()
print(goals)

# 3. POST — без CSRF
new_goal = session.post(
    f'{BASE}/api/goals',
    json={'title': 'Через Python', 'category_id': 1},
).json()
print(new_goal)
```

---

## 6. FAQ

### Почему не Bearer Token / JWT?

Это демонстрационное API для дипломного проекта. Сделано на тех
же сессиях, что и веб-интерфейс, чтобы показать интеграционный
контракт без введения отдельного механизма аутентификации. Для
production-API на мобильное приложение имеет смысл переключиться
на Laravel Sanctum.

### Почему JSON приходит с `Дип...`?

Это стандартный JSON-escape Unicode. Любой парсер (`JSON.parse`
в JS, `json.loads` в Python, Postman View → Pretty) автоматически
декодирует обратно в кириллицу. Если не нужно — открой ответ во
вкладке `Pretty` в Postman.

### Почему CSRF выключен для `/api/*` — это безопасно?

Да. CSRF-защита нужна для предотвращения cross-origin form
submissions (атаки типа «вредоносный сайт заставляет твой
браузер отправить форму куда-то ещё»). Это работает только с
HTML-формами и `application/x-www-form-urlencoded` body. JSON
с `Content-Type: application/json` так не отправить — браузер
блокирует cross-origin fetch без preflight. Плюс session-cookie
ставится с `SameSite=Lax`, что отсекает cross-site запросы.

Стандартный паттерн в Laravel: API-эндпоинты CSRF-свободны
(Sanctum работает так же). Защита остаётся на уровне cookie
(нужно быть залогиненным) и Policy (нельзя залезть в чужие
данные).

### Можно ли удалять/редактировать цели через API?

Сейчас в API только 4 эндпоинта (см. список). Удаление/
обновление через веб-интерфейс. Если нужно — несложно добавить:
`PATCH /api/goals/{id}`, `DELETE /api/goals/{id}`. Логика есть в
`GoalService`, нужно только обернуть в API-контроллер.

### Откуда брать `category_id`?

Из ответа `GET /api/goals` (поле `category.id`) или с веб-страницы
`/categories`. Дефолтные значения системных категорий: 1=Учёба,
2=Спорт, 3=Работа.

### Можно ли создать цель в чужой категории?

Нет. Проверка через `Rule::exists` в `GoalRequest` — категория
должна быть либо системной, либо твоя собственная. Попытка
подсунуть `category_id` чужого пользователя вернёт **422**.

---

## 7. Краткая шпаргалка

| Действие | Метод | URL | Body | Ответ |
|----------|-------|-----|------|-------|
| Список целей | GET | `/api/goals` | — | `{data: [...]}` |
| Детали цели | GET | `/api/goals/{id}` | — | `{data: {... subtasks: [...]}}` |
| Создать цель | POST | `/api/goals` | `{title, category_id, ...}` | `{data: {...}}` 201 |
| Статистика | GET | `/api/user/stats` | — | `{data: {kpi, activity_30d, ...}}` |

**Все запросы требуют залогиненной сессии** (cookie с `/login`).
Для `/api/*` CSRF-токен **не нужен**.
