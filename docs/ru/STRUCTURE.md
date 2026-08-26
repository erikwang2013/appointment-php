# Система сервиса предварительной записи — Структура проекта
> **Languages**: [中文](../STRUCTURE.md) · [English](../en/STRUCTURE.md) · [한국어](../ko/STRUCTURE.md) · [Deutsch](../de/STRUCTURE.md) · [Français](../fr/STRUCTURE.md) · [Español](../es/STRUCTURE.md) · [Português](../pt/STRUCTURE.md) · [हिन्दी](../hi/STRUCTURE.md) · [العربية](../ar/STRUCTURE.md) · [বাংলা](../bn/STRUCTURE.md) · [Bahasa Indonesia](../id/STRUCTURE.md) · [日本語](../ja/STRUCTURE.md)

## Обзор репозитория

```
appointment-php/
├── admin/              # Админка (webman v2 + Flutter Web)
├── service/            # Сервис бизнес-API (webman v2)
├── apps/               # Пользовательские фронтенд-приложения
│   ├── wechat/         #   WeChat Mini Program (нативная)
│   ├── flutter/        #   Flutter APP (iOS + Android)
│   └── harmonyos/      #   HarmonyOS APP (нативный)
├── docs/               # Документация проекта
└── .claude/            # Конфигурация Claude Code
```

## Связи между проектами

```
┌──────────────────────────────────────────────┐
│                   apps/                       │
│  ┌─────────────┐  ┌──────────┐  ┌─────────┐  │
│  │ wechat/      │  │ flutter/  │  │harmonyos/│  │
│  │ 微信小程序    │  │iOS/Android│  │ 鸿蒙 APP │  │
│  └──────┬──────┘  └────┬─────┘  └────┬────┘  │
│         │      функции полностью одинаковы      │
│         └──────────┬─────────┘            │
│                    │ HTTP API                 │
├────────────────────┼─────────────────────────┤
│              service/                         │
│          Бизнес-API (webman v2)                │
│              порт: 8787                        │
│                    │                          │
│                    │ общие MySQL/Redis/ES      │
│                    │                          │
│              admin/                           │
│          API админки (webman v2)               │
│              порт: 8787                        │
│                    │                          │
│         ┌──────────┴──────────┐               │
│         │                     │               │
│    admin/apps/flutter/    Flutter Web         │
│    фронтенд админки (PC)                       │
└──────────────────────────────────────────────┘
```

## admin/ — Админка

```
admin/
├── app/
│   ├── admin/controller/       # Контроллеры админки
│   │   ├── BaseController          # Базовый контроллер
│   │   ├── DashboardController     # Дашборд
│   │   ├── UserController          # Управление пользователями
│   │   ├── RoleController          # Управление ролями
│   │   ├── PermissionController    # Управление правами
│   │   ├── ConfigController        # Системная конфигурация
│   │   ├── LogController           # Журнал операций
│   │   ├── ProfileController       # Личный кабинет
│   │   ├── ExportController        # Экспорт
│   │   ├── ImportController        # Импорт
│   │   ├── UploadController        # Загрузка файлов
│   │   ├── HealthController        # Проверка здоровья
│   │   ├── DocsController          # API-документация
│   │   ├── MetricsController       # Метрики Prometheus
│   │   │                            # ✅ Реализованные бизнес-модули:
│   │   ├── TechnicianController    #   Управление мастерами (список/проверка/расписание/экспорт)
│   │   ├── MemberController        #   Управление участниками (уровни/покупки)
│   │   ├── StoreController         #   CRUD филиалов
│   │   ├── ServiceController       #   CRUD позиций услуг
│   │   ├── ServiceCategoryController # CRUD категорий услуг (дерево)
│   │   ├── ProductController       #   CRUD товаров
│   │   ├── MallOrderController     #   Заказы магазина/отправка/послепродажное обслуживание
│   │   ├── SalesStatsController    #   Статистика продаж (кэш Redis)
│   │   ├── AppointmentOrderController  # Заказы записи (отмена/завершение)
│   │   ├── MemberCardController    #   CRUD определений карт участника
│   │   ├── ReviewController        #   Управление отзывами об услугах
│   │   ├── ReportController        #   Статистика и отчёты
│   │   ├── CouponController        #   CRUD купонов
│   │   ├── FinanceController       #   Финансовые операции/статистика
│   │   ├── WithdrawalController    #   Проверка вывода средств (одобрить/отклонить/завершить)
│   │   ├── CommissionController    #   Настройка комиссии/поощрения и штрафы
│   │   ├── WithdrawalAccountController # Управление счетами вывода
│   │   ├── WithdrawalConfigController  # Конфигурация лимитов вывода
│   │   ├── BannerController        #   CRUD карусели
│   │   ├── AnnouncementController  #   CRUD/публикация объявлений
│   │   ├── FaqController           #   CRUD FAQ
│   │   ├── FeedbackController      #   Обратная связь/ответы
│   │   ├── MomentController        #   Модерация публикаций
│   │   ├── AgreementController     #   Редактирование/публикация соглашений
│   │   ├── AboutController         #   Настройка «О нас»
│   │   └── SystemMessageController #   Шаблоны/отправка системных сообщений
│   │   │                            # ✅ Расширенные модули:
│   │   ├── ServiceCardController    #   Конструктор карт
│   │   ├── SystemMonitorController  #   Мониторинг системы
│   │   ├── IpBlacklistController    #   Управление IP-чёрным списком
│   │   ├── DbBackupController       #   Резервное копирование БД
│   │   ├── SmsConfigController      #   Конфигурация SMS
│   │   ├── StorageConfigController  #   Конфигурация хранилища
│   │   ├── StoreManagerController   #   Аккаунт управляющего
│   │   ├── TrainingController       #   Обучение мастеров
│   │   ├── ScheduledTaskController  #   Плановые задачи
│   │   ├── CustomerProfileController #  Профиль клиента
│   │   ├── BatchMessageController   #   Массовый пуш
│   │   ├── RefundWorkflowController #   Проверка возвратов
│   │   ├── TechnicianTierController #   Уровни мастера
│   │   │                            # ✅ Добавлено в раундах 22-25:
│   │   ├── FullReductionController  #   Акция «скидка при достижении суммы»
│   │   ├── AttendanceController     #   Учёт рабочего времени мастера
│   │   ├── ProfitSharingController  #   Разделение средств WeChat
│   │   ├── LuckyWheelController     #   Колесо удачи за бонусы
│   │   ├── PointsExchangeGoodsController # Товары обмена бонусов
│   │   ├── ReviewAuditController    #   Модерация фото отзывов
│   │   ├── InvoiceController        #   Электронные счета
│   │   ├── TicketController         #   Тикеты поддержки
│   │   ├── ReferralRewardController #   Записи комиссии 1-го уровня
│   │   ├── ReferralLevel2Controller #   Записи комиссии 2-го уровня
│   │   ├── ReturnCustomerController #   Награда постоянному клиенту
│   │   ├── SeckillController        #   Акции распродажи
│   │   ├── VersionController        #   Управление версиями APP
│   │   ├── TechnicianScheduleController # Управление расписанием/экспорт CSV
│   │   ├── AftersaleController      #   Обработка послепродажного обслуживания
│   │   ├── OrderVerificationController # Записи подтверждений
│   │   ├── CommunityModerationController # Модерация сообщества
│   │   ├── VideoAuditController     #   Модерация видео
│   │   └── InstallController        #   Мастер установки
│   ├── api/v1/controller/      # Публичный API v1
│   │   ├── AuthController
│   │   └── CaptchaController
│   ├── common/                 # Общие утилиты
│   │   ├── HashidsService
│   │   ├── SnowflakeService
│   │   ├── EncryptionService
│   │   ├── TechnicianWithdrawalService
│   │   └── WechatPayService
│   ├── middleware/             # Middleware
│   │   ├── Cors
│   │   ├── RateLimit
│   │   ├── ApiVersion
│   │   ├── AdminAuth
│   │   ├── AdminPermission
│   │   └── OperationLog
│   ├── model/                  # Модели данных (только 6 собственных: AdminPermission/AdminRole/AdminUser/OperationLog/OperationLogDetail/SystemConfig; остальные через psr-4 общие с service)
│   ├── queue/                  # Задачи очереди
│   └── process/                # Процессы
├── apps/
│   ├── flutter/                # Фронтенд админки Flutter Web
│   │   └── lib/app/
│   │       ├── pages/           #   Страницы (20)
│   │       │   ├── dashboard/   #   Дашборд
│   │       │   ├── login/       #   Вход
│   │       │   ├── user/        #   Управление пользователями
│   │       │   ├── member/      #   Управление участниками
│   │       │   ├── role/        #   Роли и права
│   │       │   ├── config/      #   Системная конфигурация
│   │       │   ├── log/         #   Журнал операций
│   │       │   ├── profile/     #   Личный кабинет
│   │       │   ├── technician/  #   Управление мастерами
│   │       │   ├── schedule/    #   Расписание
│   │       │   ├── service/     #   Управление услугами/товарами
│   │       │   ├── service_card/#   Конструктор карт
│   │       │   ├── order/       #   Управление заказами
│   │       │   ├── verification/#   Записи подтверждений
│   │       │   ├── coupon/      #   Купоны
│   │       │   ├── withdrawal/  #   Проверка вывода средств
│   │       │   ├── report/      #   Статистика и отчёты
│   │       │   ├── review/      #   Управление отзывами
│   │       │   ├── announcement/#   Объявления
│   │       │   └── faq/         #   FAQ
│   │       ├── services/        #   Слой API-сервисов
│   │       ├── layouts/         #   Макеты
│   │       └── theme/           #   Тема
│   ├── harmonyos/               # Управляющая часть HarmonyOS (ArkTS)
│   └── weixin/                  # Управляющая часть WeChat
├── config/                     # Файлы конфигурации
│   ├── route.php
│   ├── middleware.php
│   ├── database.php
│   ├── jwt.php
│   ├── snowflake.php
│   ├── hashids.php
│   ├── encryption.php
│   ├── encryptable.php
│   └── ...
├── database/
│   └── backup/                 # Скрипты резервного копирования (структура и сид-данные едины в docs/install.sql)
├── docs/                       # Документация админки
├── public/                     # Точки входа
├── runtime/                    # Рантайм
├── tests/                      # Тесты
├── vendor/                     # Зависимости
├── CLAUDE.md
├── composer.json
├── Dockerfile
└── docker-compose.yml
```

## service/ — Бизнес-API

```
service/
├── app/
│   ├── api/v1/controller/       # Публичный API v1 (26 контроллеров)
│   │   ├── AuthController          # Вход/регистрация/забыли пароль/обновление/смена роли
│   │   ├── CaptchaController       # SMS-коды подтверждения (ограничение Redis)
│   │   ├── CommonController        # Общая конфигурация/соглашения/регионы
│   │   ├── ContentController       # Карусель/объявления/статьи
│   │   ├── DocsController          # OpenAPI-документация (hg/apidoc)
│   │   ├── LbsController           # Ближайшие филиалы (Haversine)/обратное геокодирование
│   │   ├── GuestController         # Гостевой режим (чтение без входа, кэш Redis)
│   │   ├── SeckillController       # Акции распродажи/покупка (отдельный канал)
│   │   ├── PromotionController     # Групповые покупки (старый канал flash_sale отключён)
│   │   ├── ServiceController       # Категории услуг/позиции/товары/филиалы
│   │   ├── ServicePackageController # Пакеты услуг
│   │   ├── StoreManagerController  # Рабочее место управляющего (overview/orders/technicians/revenue)
│   │   ├── TechnicianController    # Публичная информация о мастерах
│   │   ├── BrowseHistoryController # История просмотров
│   │   ├── CalendarController      # Календарь записи (вид месяц/день)
│   │   ├── CommunityController     # Публикации сообщества
│   │   ├── CommunityCommentController # Комментарии сообщества
│   │   ├── FullReductionController # Акция «скидка при достижении суммы»
│   │   ├── PaymentNotifyController # Платёжные колбэки (WeChat/Alipay)
│   │   ├── PrintController         # Печать
│   │   ├── PrivacyController       # Конфиденциальность (экспорт данных/удаление)
│   │   ├── QueueController         # Электронная очередь
│   │   ├── VersionController       # Управление версиями APP/проверка обновлений
│   │   ├── VideoController         # Видео
│   │   ├── WechatController        # Всё, что связано с WeChat
│   │   └── WheelController         # Колесо удачи за бонусы
│   ├── user/v1/controller/      # Модуль пользователя v1 (14 контроллеров)
│   │   ├── ProfileController       # Личные данные/пароль/телефон/удаление/выход
│   │   ├── AddressController       # CRUD адресов (управление адресом по умолчанию)
│   │   ├── FavoriteController      # Избранное (услуги/мастера)
│   │   ├── FeedbackController      # Обратная связь (текст + фото)
│   │   ├── ReferralController      # Продвижение/QR-код/приглашённые пользователи
│   │   ├── CheckInController       # Ежедневная отметка
│   │   ├── DeviceController        # Управление устройствами пользователя
│   │   ├── GrowthController        # Уровни роста (обзор/records/levels)
│   │   ├── HealthProfileController # Медицинский профиль
│   │   ├── InvoiceController       # Электронные счета: заявка/список/детали
│   │   ├── InvoiceTitleController  # Библиотека реквизитов счёта
│   │   ├── NotifySettingController # Настройки уведомлений
│   │   ├── PointsTransferController# Передача бонусов
│   │   └── TicketController        # Тикеты поддержки
│   ├── technician/v1/controller/ # Модуль мастера v1 (10 контроллеров)
│   │   ├── ProfileController       # Анкета мастера/заявка на вступление
│   │   ├── ScheduleController      # Запрос/настройка расписания
│   │   ├── OrderController         # Список заказов мастера
│   │   ├── WorkController          # Рабочее место (today/records/start/complete)
│   │   ├── EarningController       # Обзор дохода + записи
│   │   ├── WithdrawController      # Заявка на вывод (ежемесячно config('withdraw.gate_day') числа, настраивается)
│   │   ├── ServiceRecordController # Записи услуг
│   │   ├── ExamController          # Онлайн-аттестация
│   │   ├── AttendanceController    # Отметки прихода/ухода
│   │   └── ReviewController        # Ответ мастера на отзыв
│   ├── order/v1/controller/     # Модуль заказов v1 (8 контроллеров + 9 трейтов)
│   │   ├── OrderController         # Оформление (блокировка мастера)/список/детали/отмена/оплата/возврат/подтверждение (агрегирующая точка входа, 38 строк, все методы из трейтов)
│   │   ├── OrderCreateTrait        # Создание заказа store/расчёт цены (475 строк)
│   │   ├── OrderQueryTrait         # Запросы заказов список/детали/логистика (205 строк)
│   │   ├── OrderPayTrait           # Оплата pay/оплата балансом/зачёт бонусами (415 строк)
│   │   ├── OrderCancelTrait        # Отмена заказа (272 строки)
│   │   ├── OrderRefundTrait        # Заявка на возврат (379 строк)
│   │   ├── OrderCompensateTrait    # Сканирование компенсаций возврата + возврат скидок/бонусов (345 строк)
│   │   ├── OrderVerifyTrait        # Подтверждение: комиссия/начисление бонусов (256 строк)
│   │   ├── OrderRescheduleTrait    # Перенос записи (181 строка)
│   │   ├── OrderNotifyTrait        # Уведомления: подписки/шаблоны/внутрисайтовые/WebSocket (195 строк)
│   │   └── OrderLockTrait          # Утилита распределённых блокировок (80 строк)
│   │   ├── AftersaleController     # Послепродажное обслуживание
│   │   ├── CartController          # Корзина
│   │   ├── IcsController           # Экспорт календаря ICS
│   │   ├── ReviewController        # Отзыв/дополнение к отзыву
│   │   ├── SignatureController     # Подпись
│   │   ├── TimelineController      # Таймлайн статусов заказа
│   │   └── WaitlistController      # Лист ожидания
│   ├── wallet/v1/controller/    # Модуль кошелька v1 (2 контроллера)
│   │   ├── WalletController        # Баланс/пополнение/история операций/оплата балансом
│   │   └── WalletTransferController# Переводы между пользователями
│   ├── marketing/v1/controller/ # Маркетинговый модуль v1 (7 контроллеров)
│   │   ├── CouponController        # Список купонов/получение/зачёт при заказе
│   │   ├── CardController          # Список карт участника/покупка/мои карты посещений my/use
│   │   ├── PointController         # Записи бонусов/возврат за покупки
│   │   ├── GiftCardController      # Подарочные карты/обмен redeem
│   │   ├── MemberBenefitController # Привилегии участника
│   │   ├── MemberCardController    # Определения карт участника
│   │   └── PointsExchangeController# Магазин обмена бонусов
│   ├── notification/v1/controller/ # Модуль уведомлений v1 (1 контроллер)
│   │   └── NotificationController  # Список уведомлений/отметка прочитанным
│   ├── common/                  # Общие возможности (BaseController и др.)
│   ├── middleware/              # Middleware
│   │   ├── ApiVersion              # Управление версиями API (заголовок API-Version)
│   │   ├── Auth                    # JWT-аутентификация + проверка статуса пользователя
│   │   ├── Cors                    # Обработка CORS
│   │   ├── Security                # Проверка безопасности (security-php)
│   │   └── TechnicianAuth          # Проверка роли мастера
│   └── model/                   # Модели данных (81)
│       ├── User.php → erik_user
│       ├── TechnicianProfile.php → erik_technician_profile
│       ├── Service.php → erik_service (ES: erik_services)
│       ├── Product.php → erik_product (ES: erik_products)
│       ├── Store.php → erik_store
│       ├── Order.php → erik_order (включая правила возврата/конечный автомат)
│       ├── Coupon.php → erik_coupon
│       ├── MemberCard.php → erik_member_card
│       ├── Notification.php → erik_notification
│       └── ... (всего 81 файл моделей; в admin ещё 6 собственных, итого 87)
├── config/                     # Файлы конфигурации
├── public/                     # Точки входа
├── runtime/                    # Рантайм
├── vendor/                     # Зависимости
├── start.php
├── composer.json
└── Dockerfile
```

## apps/ — Пользовательский фронтенд

### apps/wechat/ — WeChat Mini Program

```
apps/wechat/
├── app.js                      # Точка входа приложения
├── app.json                    # Глобальная конфигурация
├── app.wxss                    # Глобальные стили
├── pages/
│   ├── auth/                   # Аутентификация
│   │   ├── login               #   Вход
│   │   ├── register            #   Регистрация
│   │   ├── forget-password     #   Забыли пароль
│   │   └── agreement           #   Просмотр соглашения
│   ├── home/                   # Главная (карусель/объявления/категории/поиск)
│   ├── service/                # Услуги
│   │   ├── list                #   Список услуг
│   │   └── detail              #   Детали услуги
│   ├── order/                  # Заказы
│   │   ├── list                #   Список заказов
│   │   ├── detail              #   Детали заказа
│   │   └── confirm             #   Подтверждение заказа
│   ├── cart/                   # Корзина
│   ├── cards/                  # Карты участника (покупка/мои/использование карт посещений my/use)
│   ├── gift-cards/             # Подарочные карты (обмен redeem/зачисление)
│   ├── points/                 # Бонусы (записи/обмен)
│   ├── marketing/              # Маркетинг (купоны и др.)
│   ├── favorite/               # Избранное
│   ├── feedback/               # Обратная связь
│   ├── referral/               # Продвижение
│   ├── message/                # Сообщения
│   │   ├── list                #   Список сообщений
│   │   └── detail              #   Детали сообщения
│   ├── tech-work/              # Рабочее место мастера
│   │   ├── index               #   Главная рабочего места (today/records/start/complete)
│   │   ├── schedule            #   Расписание
│   │   ├── order-list          #   Заказы
│   │   ├── scan-verify         #   Подтверждение по QR
│   │   ├── member-list         #   Список участников
│   │   ├── member-detail       #   Детали участника
│   │   ├── earnings            #   Доход
│   │   ├── withdrawal          #   Вывод средств
│   │   ├── transaction-list    #   История операций
│   │   └── training            #   Обучение
│   ├── user/                   # Личный кабинет
│   │   ├── index               #   Личные данные
│   │   ├── settings            #   Настройки
│   │   └── switch-role         #   Смена роли
│   └── wallet/                 # Кошелёк (баланс/пополнение/история операций)
├── components/                 # Общие компоненты
│   ├── navbar
│   ├── tabbar
│   ├── service-card
│   ├── technician-card
│   ├── coupon-popup
│   └── lbs-selector
├── utils/                      # Утилиты
│   ├── api.js                  #   HTTP-запросы
│   ├── auth.js                 #   Управление аутентификацией
│   ├── location.js             #   LBS-позиционирование
│   └── constants.js            #   Константы
├── styles/                     # Общие стили
└── images/                     # Ресурсы изображений
```

### apps/flutter/ — Flutter APP

```
apps/flutter/
├── lib/
│   ├── main.dart               # Точка входа
│   ├── app.dart                # Конфигурация App/маршруты/тема
│   ├── pages/                  # Страницы (структура совпадает с мини-программой)
│   │   ├── auth/
│   │   ├── home/
│   │   ├── service/
│   │   ├── order/
│   │   ├── cart/
│   │   ├── technician/
│   │   ├── tech_work/
│   │   ├── user/
│   │   ├── marketing/
│   │   ├── message/
│   │   ├── store/
│   │   └── other/
│   ├── widgets/                # Общие компоненты
│   ├── services/               # API-сервисы
│   │   ├── api_service         #   HTTP (Dio)
│   │   ├── auth_service        #   Аутентификация
│   │   └── location_service    #   Позиционирование
│   ├── models/                 # Модели данных
│   ├── state/                  # Управление состоянием
│   └── utils/                  # Утилиты
├── android/                    # Проект Android
├── ios/                        # Проект iOS
├── pubspec.yaml
└── ...
```

## Цепочка выполнения middleware

### service/

```
Публичный API:  Cors → Security → RateLimit → Controller
Пользовательский API:  Cors → Security → RateLimit → Auth → Controller
API мастера:  Cors → Security → RateLimit → Auth → TechnicianAuth → Controller
Платёжные колбэки: Cors → Security → Controller
```

### admin/

```
Публичный API:  Cors → Security → RateLimit → Controller
Управленческий API:  Cors → Security → RateLimit → AdminAuth → AdminPermission → OperationLog → Controller
Проверка здоровья: Cors → Security → RateLimit → Controller
```

## Список таблиц БД

Все таблицы используют префикс `erik_`, первичный ключ BIGINT без автоинкремента (генерируется Snowflake).

| Домен | Таблица | Описание |
|----|------|------|
| Пользователи | erik_user | Единая таблица пользователей |
| Пользователи | erik_user_address | Адреса доставки |
| Мастера | erik_technician_profile | Анкета мастера |
| Мастера | erik_technician_schedule | Расписание мастера |
| Мастера | erik_technician_service | Услуги, которые может оказывать мастер |
| Мастера | erik_technician_earnings | Записи дохода мастера |
| Мастера | erik_technician_withdrawal | Записи вывода средств мастера |
| Мастера | erik_technician_attendance | Учёт рабочего времени мастера |
| Мастера | erik_technician_member_note | Анкеты участников |
| Услуги | erik_service_category | Категории услуг |
| Услуги | erik_service | Позиции услуг |
| Услуги | erik_product | Товары |
| Услуги | erik_store | Филиалы |
| Заказы | erik_order | Главная таблица заказов (колонка связи seckill_id, раунд 24) |
| Заказы | erik_order_item | Позиции заказа |
| Заказы | erik_order_payment | Записи об оплате |
| Заказы | erik_order_refund | Записи о возврате |
| Заказы | erik_order_review | Отзывы об услугах |
| Заказы | erik_order_verification | Записи подтверждений |
| Заказы | erik_order_reschedule | Записи о переносе записи (раунд 17) |
| Маркетинг | erik_coupon | Определения купонов |
| Маркетинг | erik_user_coupon | Купоны пользователей |
| Маркетинг | erik_user_coupon_transfer | Записи о передаче купонов (раунд 17) |
| Маркетинг | erik_user_points_transfer | Записи о передаче бонусов (раунд 19) |
| Маркетинг | erik_technician_tier_log | Журнал смены уровня мастера (раунд 17) |
| Маркетинг | erik_member_card | Определения карт участника |
| Маркетинг | erik_user_member_card | Карты участника пользователей |
| Маркетинг | erik_member_card_usage | Записи использования карт посещений |
| Маркетинг | erik_user_points | Записи бонусов |
| Маркетинг | erik_gift_card | Подарочные карты |
| Маркетинг | erik_user_referral | Продвижение пользователя |
| Маркетинг | erik_user_favorite | Избранное пользователя |
| Кошелёк | erik_user_wallet | Баланс кошелька пользователя |
| Кошелёк | erik_wallet_recharge | Записи пополнения кошелька |
| Кошелёк | erik_wallet_txn | Операции кошелька |
| Кошелёк | erik_wallet_transfer | Записи переводов между пользователями (раунд 19) |
| Пользователи | erik_user_notify_setting | Настройки уведомлений (раунд 19) |
| Контент | erik_banner | Карусель |
| Контент | erik_announcement | Объявления |
| Контент | erik_platform_agreement | Соглашения платформы |
| Контент | erik_faq | FAQ |
| Контент | erik_feedback | Обратная связь |
| Контент | erik_moment | Публикации |
| Контент | erik_notification | Уведомления |
| Финансы | erik_finance_transaction | Доходы и расходы |
| Финансы | erik_technician_commission_config | Конфигурация комиссии |
| Финансы | erik_withdrawal_account | Счета вывода |
| Финансы | erik_withdrawal_config | Конфигурация лимитов вывода |
| Система | erik_admin_user | Администраторы (создано) |
| Система | erik_admin_role | Роли (создано) |
| Система | erik_admin_permission | Права (создано) |
| Система | erik_admin_user_role | Связь пользователь-роль (создано) |
| Система | erik_admin_role_permission | Связь роль-право (создано) |
| Система | erik_system_config | Системная конфигурация (создано) |
| Система | erik_operation_log | Журнал операций (создано) |
| Пользователи | erik_user_growth | Записи роста (раунд 20) |
| Пользователи | erik_growth_level | Уровни роста (раунд 20) |
| Заказы | erik_invoice | Электронные счета (раунд 20) |
| Пользователи | erik_ticket | Тикеты поддержки (раунд 20) |
| Маркетинг | erik_referral_level2_reward | Записи комиссии 2-го уровня (раунд 20) |
| Пользователи | erik_invoice_title | Библиотека реквизитов счёта (раунд 21) |
| Пользователи | erik_browse_history | История просмотров (раунд 21) |
| Маркетинг | erik_full_reduction_activity | Акция «скидка при достижении суммы» (раунд 22) |
| Мастера | erik_technician_attendance | Учёт рабочего времени мастера (раунд 22) |
| Система | erik_push_log | Записи APP-пуша (раунд 22) |
| Финансы | erik_profit_sharing | Записи разделения средств WeChat (раунд 22) |
| Заказы | erik_order_status_log | Таймлайн статусов заказа (раунд 23) |
| Пользователи | erik_user_health_profile | Медицинский профиль пользователя (раунд 23) |
| Маркетинг | erik_lucky_wheel | Определения призов колеса (раунд 23) |
| Маркетинг | erik_wheel_record | Записи розыгрышей колеса (раунд 23) |
| Маркетинг | erik_seckill_activity | Акции распродажи (раунд 24) |
| Система | erik_app_version | Версии APP (раунд 24) |

### Дополнительный список (часть из 95 таблиц docs/install.sql, не вошедшая в список выше; полный авторитетный список — в install.sql)

| Домен | Таблица | Описание |
|----|------|------|
| Маркетинг | erik_card_transfer | Передача карт посещений |
| Пользователи | erik_check_in | Ежедневная отметка |
| Контент | erik_community_post | Публикации сообщества |
| Контент | erik_community_comment | Комментарии сообщества |
| Мастера | erik_exam | Аттестация |
| Мастера | erik_exam_question | Вопросы аттестации |
| Мастера | erik_exam_attempt | Ответы на аттестацию |
| Система | erik_operation_log_detail | Детали журнала операций |
| Заказы | erik_order_aftersale | Послепродажное обслуживание заказа |
| Маркетинг | erik_points_exchange_goods | Товары обмена бонусов |
| Маркетинг | erik_promotion | Акции групповых покупок |
| Маркетинг | erik_promotion_participant | Участники групповых покупок |
| Заказы | erik_queue_number | Электронная очередь |
| Услуги | erik_service_package | Пакеты услуг |
| Мастера | erik_service_record | Записи услуг |
| Контент | erik_share | Записи о перепостах |
| Заказы | erik_signature | Подпись |
| Мастера | erik_technician_tier_config | Конфигурация уровней мастера |
| Мастера | erik_training_course | Обучающие курсы |
| Мастера | erik_training_progress | Прогресс обучения |
| Пользователи | erik_user_device | Устройства пользователя |
| Маркетинг | erik_user_points_exchange | Записи обмена бонусов |
| Контент | erik_video_post | Видеопубликации |
| Заказы | erik_waitlist | Лист ожидания |

## Задел под внешние сервисы

| Сервис | Назначение | Точка интеграции |
|------|------|--------|
| WeChat Open Platform | Вход через WeChat/UnionID | WechatAuthService |
| WeChat Pay | Оплата/возврат/вывод средств | WechatPayService |
| SMS-провайдер | Коды подтверждения/уведомления | SmsService |
| Картографический сервис | LBS-позиционирование/навигация/расчёт расстояний | MapService |
