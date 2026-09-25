# Система сервиса предварительной записи — Индекс документации
> **Languages**: [中文](../README.md) · [English](../en/DOCS.md) · [한국어](../ko/DOCS.md) · [Deutsch](../de/DOCS.md) · [Français](../fr/DOCS.md) · [Español](../es/DOCS.md) · [Português](../pt/DOCS.md) · [हिन्दी](../hi/DOCS.md) · [العربية](../ar/DOCS.md) · [বাংলা](../bn/DOCS.md) · [Bahasa Indonesia](../id/DOCS.md) · [日本語](../ja/DOCS.md)

> **Статус проекта**: всё выполнено ✅ | 143 контроллера (service 69 / admin 74) | 87 моделей | 757 теста (service 579 / admin 178) | 95 таблиц БД | 479 маршрутов (service 221 / admin 258)

## Основная документация

| Документ | Описание |
|------|------|
| [ARCHITECTURE.md](ARCHITECTURE.md) | Архитектура: общий обзор системы, состав проекта, ключевые компоненты, цепочка middleware, потоки данных |
| [FEATURES.md](FEATURES.md) | Возможности: полный список функций пользовательской части + рабочего места мастера + админки |
| [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) | Проектирование архитектуры: слоистая архитектура, проектирование middleware, проектирование БД, проектирование безопасности, интеграция ES |
| [FEATURE-DESIGN.md](FEATURE-DESIGN.md) | Проектирование функций: процесс покупки, конечный автомат заказа, правила возврата, проектирование карт участника, смена ролей |
| [STRUCTURE.md](STRUCTURE.md) | Структура проекта: полное дерево каталогов четырёх платформ, цепочка выполнения middleware, список таблиц БД |
| [INSTALL.md](INSTALL.md) | Установка: Web-мастер установки, ручная установка, развёртывание Docker, переменные окружения, FAQ |
| [USAGE.md](USAGE.md) | Использование: работа в админке / пользовательской части / для мастера (API см. [API.md](API.md)) |
| [API.md](API.md) | API-документация: бизнес-API + API админки, с примерами запросов/ответов + OpenAPI-эндпоинтами |

## Схемы (SVG)

Все схемы находятся в [diagrams/](diagrams/): китайские `cn-*` и английские `en-*` оригиналы — в `docs/diagrams/`, у каждого языка свой зеркальный набор в `docs/<lang>/diagrams/`:

| Схема | Описание | Исходник Mermaid |
|------|------|-----------|
| [ru-architecture.svg](diagrams/ru-architecture.svg) | Системная архитектура: топология четырёх клиентских слоёв + middleware + слой данных + сторонние сервисы | [ARCHITECTURE-DIAGRAM.md](diagrams/ARCHITECTURE-DIAGRAM.md) |
| [ru-architecture-design.svg](diagrams/ru-architecture-design.svg) | Проектирование архитектуры: 7 слоёв + цепочка middleware + ограничение запросов + принципы проектирования БД + проектирование безопасности | [ARCHITECTURE-DESIGN.md](ARCHITECTURE-DESIGN.md) |
| [ru-feature-design.svg](diagrams/ru-feature-design.svg) | Проектирование функций: три функциональные области + процессы покупки + торговые правила + активы и права + расчёты с мастерами + переключение ролей + оплата | [FEATURE-DESIGN.md](FEATURE-DESIGN.md) |
| [ru-project-structure.svg](diagrams/ru-project-structure.svg) | Структура проекта: дерево каталогов четырёх платформ + детализация модулей | [STRUCTURE.md](STRUCTURE.md) |
| [ru-appointment-flow.svg](diagrams/ru-appointment-flow.svg) | Процесс записи на услугу | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [ru-payment-refund.svg](diagrams/ru-payment-refund.svg) | Процесс оплаты и возврата | [FLOWCHART.md](diagrams/FLOWCHART.md) |
| [ru-order-lifecycle.svg](diagrams/ru-order-lifecycle.svg) | Конечный автомат жизненного цикла заказа | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [ru-lifecycle-overview.svg](diagrams/ru-lifecycle-overview.svg) | Обзор всех жизненных циклов (17, четыре группы) | [LIFECYCLE-DIAGRAM.md](diagrams/LIFECYCLE-DIAGRAM.md) |
| [ru-security-defense.svg](diagrams/ru-security-defense.svg) | Семиуровневая эшелонированная оборона | [SECURITY-ARCHITECTURE.md](diagrams/SECURITY-ARCHITECTURE.md) |
| [mascot.svg](diagrams/mascot.svg) | Талисман проекта — календарный дух «Юэ» (SMIL-анимация, без внешних зависимостей) | — |

## Тестирование и безопасность

| Документ | Описание |
|------|------|
| [TEST-REPORT.md](TEST-REPORT.md) | Отчёт о тестировании: аудит покрытия — 558 кейсов / 2508 утверждений + записи HTTP-смоук-тестов |
| [AUDIT-REPORT.md](AUDIT-REPORT.md) | Отчёт о ревизии: результаты тестов, оценка конфигурации экосистемы, записи об исправлениях, анализ архитектуры кода |
| [SECURITY-AUDIT-REPORT.md](SECURITY-AUDIT-REPORT.md) | Отчёт об аудите безопасности |

## База данных и эксплуатация

| Документ | Описание |
|------|------|
| [install.sql](../install.sql) | Единый скрипт установки: 67 объединённых миграций, 2723 строки, 95 таблиц / 285 прав / 38 конфигураций + демо-данные |

## Спецификации и планы

| Документ | Описание |
|------|------|
| [specs/2026-05-26-appointment-system-design.md](specs/2026-05-26-appointment-system-design.md) | Спецификация проектирования системы |
| [plans/2026-05-26-appointment-system-plan.md](plans/2026-05-26-appointment-system-plan.md) | План реализации |

## Документация админки

В `admin/` собственная документация: ARCHITECTURE.md、DESIGN.md、SECURITY.md、API.md、nginx-security.conf。
