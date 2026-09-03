# Система сервиса предварительной записи — Руководство по установке
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## Требования к окружению

| Компонент | Минимальная версия | Описание |
|------|----------|------|
| PHP | 8.3+ | Расширения: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | Префикс таблиц `appointment_`, кодировка utf8mb4 |
| Redis | 6.0+ | Кэш / ограничение частоты / Session / хранение кодов подтверждения |
| Composer | 2.x | Управление PHP-зависимостями |
| Elasticsearch | 8.x (опционально) | Полнотекстовый поиск, без установки не влияет на основные функции |

---

## I. Web-мастер установки (рекомендуется)

После запуска админки откройте в браузере `/install` — запустится мастер установки в один клик:

```bash
# 1. Установка зависимостей и запуск
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # порт по умолчанию 8787
```

Откройте в браузере `http://localhost:8787/install` и пройдите 4 шага:

1. **Проверка окружения** — автоматическое определение версии PHP, обязательных расширений, прав на файлы
2. **Конфигурация БД** — заполните данные подключения MySQL, нажмите «проверить подключение»
3. **Учётная запись администратора** — задайте название приложения, имя пользователя и пароль администратора
4. **Выполнение установки** — автоматический импорт SQL → создание администратора → запись конфигурации .env

После завершения установки войдите с заданными именем пользователя и паролем. При успешной установке пишется файл `.install.lock`, интерфейс `/install` имеет двойную проверку (файловый замок + isInstalled) от повторной установки; `.install.lock` добавлен в `.gitignore`. Для продакшена рекомендуется удалить маршрут `/install` из `admin/config/route.php`.

---

## II. Установка вручную

### 2.1 Клонирование проекта

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 Установка PHP-зависимостей

```bash
# Сервис бизнес-API
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# Админка
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 Настройка переменных окружения

Отредактируйте `service/.env` (бизнес-API) и `admin/.env` (админка), измените ключевые настройки:

```bash
# Подключение к БД
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service использует appointment, admin — open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Подключение к Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT-ключ — в продакшене обязательно замените на случайную строку из 64 символов
JWT_SECRET_KEY=your-64-char-random-string

# Ключи шифрования — в продакшене обязательно замените
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Соль Hashids — в продакшене обязательно замените
HASHIDS_SALT=your-random-salt

# Режим отладки — в продакшене обязательно false
APP_DEBUG=false
```

> Полное описание переменных — в `service/.env.example` и `admin/.env.example`.

### 1.4 Создание БД и импорт

```bash
# Создание БД (service и admin могут использовать одну БД или разные)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Импорт единого скрипта установки (все 54+ таблиц + данные прав + демо-данные)
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` объединяет все файлы миграций, всего 2723 строки, содержит все структуры таблиц и сид-данные админки и бизнес-сервиса. Для чистой установки выполняется один раз; повторное выполнение на существующей БД прервётся из-за конфликтов первичных ключей/колонок — для сценария обновления сначала сделайте резервную копию или разрешите конфликты вручную.

### 1.5 Запуск сервисов

```bash
# Запуск сервиса бизнес-API (порт по умолчанию 8787)
cd service/
php start.php start -d

# Запуск админки (порт по умолчанию 8787)
cd ../admin/
php start.php start -d
```

### 1.6 Проверка установки

```bash
# Бизнес-API
curl http://localhost:8787/api/v1/common/config

# Health-проверка админки
curl http://localhost:8787/health

# Вход в админку (учётная запись по умолчанию ниже)
curl -X POST http://localhost:8787/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 Учётная запись по умолчанию

| Роль | Имя пользователя | Пароль | Описание |
|------|--------|------|------|
| Суперадминистратор | `admin` | `admin123` | Обладает всеми правами |

> Сразу после первого входа смените пароль.

---

## III. Развёртывание через Docker

### 2.1 Сервис бизнес-API

```bash
cd service/
cp .env.docker .env
# Отредактируйте .env: ключи и пароли
docker-compose up -d
```

Оркестрация: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 Админка

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Импорт БД в Docker-окружении

```bash
# Скопируйте install.sql в контейнер и выполните
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## IV. Обзор структуры БД

| Домен | Таблиц | Ключевые таблицы |
|----|------|--------|
| Админка | 8 | `appointment_admin_user`, `appointment_admin_role`, `appointment_admin_permission`, `appointment_operation_log` |
| Пользователи | 4 | `appointment_user`, `appointment_user_address`, `appointment_user_favorite`, `appointment_user_device` |
| Мастера | 8 | `appointment_technician_profile`, `appointment_technician_schedule`, `appointment_technician_earning`, `appointment_technician_withdrawal`, `appointment_technician_tier_config` |
| Услуги | 4 | `appointment_service_category`, `appointment_service`, `appointment_service_package`, `appointment_service_record` |
| Заказы | 5 | `appointment_order`, `appointment_order_item`, `appointment_order_payment`, `appointment_order_refund`, `appointment_order_review` |
| Маркетинг | 8 | `appointment_coupon`, `appointment_member_card`, `appointment_gift_card`, `appointment_user_points`, `appointment_promotion` |
| Очередь | 1 | `appointment_queue_number` |
| Контент | 5 | `appointment_banner`, `appointment_announcement`, `appointment_faq`, `appointment_feedback`, `appointment_platform_agreement` |
| Сообщество | 3 | `appointment_post`, `appointment_comment`, `appointment_moment` |
| Филиалы | 1 | `appointment_store` |
| Обучение | 2 | `appointment_training_course`, `appointment_training_progress` |
| Экзамены | 3 | `appointment_exam`, `appointment_exam_question`, `appointment_exam_attempt` |
| Система | 3 | `appointment_system_config`, `appointment_notification`, `appointment_signature` |
| **Итого** | **55** | |

Все таблицы используют префикс `appointment_`, первичный ключ `id` — BIGINT без автоинкремента (генерируется на уровне приложения snowflake-php).

---

## V. Запуск тестов

```bash
# Тесты бизнес-API (21 tests)
cd service/
php vendor/bin/phpunit

# Тесты админки (59 tests)
cd admin/
php vendor/bin/phpunit

# Статический анализ
php vendor/bin/phpstan analyse --level=5 app/

# Проверка стиля кода
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## VI. Настройка сторонних сервисов

В админке в разделе «Системная конфигурация» заполните следующие группы конфигурации:

| Группа | Назначение | Обязательна |
|--------|------|------|
| `wechat_pay` | Мерчант-номер WeChat Pay / API-ключ / сертификаты | Нужна для функции оплаты |
| `wechat_app` | AppID / AppSecret мини-программы WeChat | Нужен для входа через WeChat |
| `sms` | SMS-провайдер (aliyun/tencent) + подпись/шаблоны | Нужен для SMS-кодов |
| `map_service` | Картографический сервис (amap/tencent) + API Key | Нужен для LBS-функций |
| `storage` | Объектное хранилище (oss/cos) + AccessKey/Endpoint | Нужно для загрузки файлов |

---

## VII. Частые вопросы

**В: При запуске ошибка `Class 'support\Model' not found`**
О: Выполните `composer dump-autoload`.

**В: Ошибка подключения к БД `SQLSTATE[HY000] [2002]`**
О: Проверьте настройки `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` в `.env`.

**В: Ошибка кодировки при импорте SQL**
О: Используйте `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql`

**В: Не удаётся подключиться к Redis**
О: Убедитесь, что Redis запущен, проверьте настройки `REDIS_HOST`/`REDIS_PORT`.

**В: Порт занят**
О: Измените порт `listen` в `config/server.php`.

**В: Код подтверждения не отображается**
О: Убедитесь, что расширение GD установлено, настройка `POSTER_CAPTCHA_STORAGE` корректна (локально можно `file`, в продакшене `redis`).

**В: Elasticsearch не работает**
О: ES — опциональный компонент; убедитесь, что `SCOUT_HOSTS` настроен правильно и ES запущен.

---

## VIII. Структура каталогов

```
appointment-php/
├── admin/                    # Админка (webman v2)
│   ├── app/                  # Контроллеры / модели / middleware
│   ├── config/               # Конфигурация маршрутов / БД / middleware
│   ├── database/             # Скрипты резервного копирования (структура и сид-данные едины в docs/install.sql)
│   ├── tests/                # Тесты PHPUnit (59 tests)
│   ├── .env.example          # Шаблон переменных окружения
│   ├── .env.docker           # Переменные окружения для Docker
│   ├── Dockerfile            # Файл сборки Docker
│   └── docker-compose.yml    # Оркестрация Docker
├── service/                  # Сервис бизнес-API (webman v2)
│   ├── app/                  # Контроллеры / модели / middleware
│   ├── config/               # Конфигурация безопасности / маршрутов / БД
│   ├── seed.php              # Запуск сид-данных демо (читает раздел демо-данных docs/install.sql)
│   ├── tests/                # Тесты PHPUnit (21 tests)
│   ├── .env.example          # Шаблон переменных окружения
│   ├── .env.docker           # Переменные окружения для Docker
│   ├── Dockerfile            # Файл сборки Docker
│   └── docker-compose.yml    # Оркестрация Docker
├── docs/                     # Документация
│   ├── INSTALL.md            # Настоящее руководство по установке
│   ├── install.sql           # Единый скрипт установки БД (2723 строки)
│   ├── ARCHITECTURE.md       # Документация по архитектуре
│   ├── API.md                # Справочник API
│   └── AUDIT-REPORT.md       # Отчёт о ревизии
└── .github/workflows/        # Конвейер CI/CD
    └── ci.yml
```
