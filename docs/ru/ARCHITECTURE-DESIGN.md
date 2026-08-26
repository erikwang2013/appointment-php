# Проектирование архитектуры

## Слоистая архитектура

```
┌─────────────────────────────────────────┐
│              Presentation (представление) │
│  WeChat Mini Program / Flutter APP / Flutter Web │
├─────────────────────────────────────────┤
│              Route (маршрутизация)        │
│  config/route.php — группы маршрутов + привязка middleware │
├─────────────────────────────────────────┤
│            Middleware (промежуточный слой) │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│           Controller (контроллеры)        │
│  BaseController → бизнес-контроллеры      │
├─────────────────────────────────────────┤
│            Service (сервисный слой)       │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│             Model (модели)                │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│              Data (данные)                │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## Проектирование middleware

### Цепочка выполнения

```
Cors → Security(обнаружение 31 типа атак) → RateLimit → Auth(JWT+статус пользователя)
    → [TechnicianAuth(роль мастера)] → [AdminPermission(RBAC)] → [OperationLog(источники 8 типов)]
    → Controller
```

### Обязанности middleware

| Middleware | Область | Функция |
|--------|--------|------|
| Cors | глобальная | Preflight OPTIONS + CORS-заголовки ответа |
| Security | глобальная | erikwang2013/security-php, обнаружение 31 типа атак |
| RateLimit | глобальная | Redis скользящее окно + атомарность Lua |
| Auth | группа маршрутов | Разбор JWT + проверка существования/статуса пользователя |
| TechnicianAuth | группа маршрутов | Поиск анкеты мастера + проверка статуса approved |
| AdminAuth | группа маршрутов | JWT-аутентификация админки + чёрный список |
| AdminPermission | группа маршрутов | Проверка прав RBAC, кэш Redis 60с |
| OperationLog | группа маршрутов | Журнал операций + автоматическое определение источников 8 типов |

### Политика ограничения частоты

| Интерфейс | Лимит |
|------|------|
| По умолчанию | 60 раз/мин/IP |
| Вход | 10 раз/мин |
| Регистрация | 5 раз/мин |
| Код подтверждения | 1 раз/60 сек/номер телефона |

## Принципы проектирования БД

### Стратегия первичных ключей

- Все первичные ключи: BIGINT UNSIGNED NOT NULL, без автоинкремента
- Генерируются `erikwang2013/snowflake-php` на уровне приложения
- В модели: `$incrementing = false`, `$keyType = 'string'`

### Префикс таблиц

Единый префикс `erik_`, настраивается в `config/database.php`. Модель пишет исходное имя таблицы, ORM автоматически добавляет префикс.

### Шифрование чувствительных полей

Используется трейт `erikwang2013/encryptable`:

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

Длина VARCHAR шифруемых полей установлена 500 (данные при шифровании расширяются).

### Мягкое удаление и временные метки

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- Все таблицы содержат `created_at` + `updated_at`

## Механизм шифрования/дешифрования ID в API

### Запрос: decodeIds()

Фронтенд отправляет ID в hashids-кодировке → контроллер вызывает `$this->decodeIds($request->all())` для декодирования.

### Ответ: encodeIds()

ID из результатов запросов БД → `BaseController::success()` автоматически вызывает `encodeIds()` для кодирования → возвращается hashids-строка.

### Правила

Рекурсивно обрабатываются в массиве поля с ключом `id` или заканчивающиеся на `_id`.

## Проектирование безопасности

### Эшелонированная оборона

```
WAF → Cors → Security(31 тип проверок) → RateLimit → Auth(JWT+статус)
    → [проверка роли] → [RBAC] → Controller(шифрование в Model) → ответ
```

### Безопасность аутентификации

- Пароль: bcrypt-хэш
- JWT: срок действия 7 дней + обновление + чёрный список
- Блокировка: 5 неудачных попыток → 15 минут
- Параллелизм: не более 3 токенов

### Безопасность данных

- Слой API: erikwang2013/encryption
- Слой DB: трейт erikwang2013/encryptable
- Логи: чувствительные данные в логи не попадают

### Безопасность операций

- erikwang2013/poster-php: проверка перед удалением/проверкой/выводом средств
- Middleware Security: обнаружение XSS/SQL-инъекций/CSRF/обхода путей

## Интеграция Elasticsearch

`erikwang2013/webman-scout` автоматически синхронизирует модели в ES:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Экспорт Excel/PDF

- Excel: PhpSpreadsheet, чувствительные поля автоматически маскируются
- PDF: визуализация панели Dashboard

## Определение источников 8 типов

OperationLog анализирует User-Agent:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / другое → web
```


## TDD-тестирование

| Проект | Количество тестов | Статус |
|------|--------|------|
| admin/ | 60 | ✅ пройдено |
| service/ | 21 | ✅ пройдено |
| Итого | 81 | ✅ |

Покрытие: правила возврата / статусы заказов / Hashids / система очередей / шифрование / коды подтверждения
