# Спецификация системы сервиса предварительной записи

## Обзор

Трёхсторонняя система сервиса предварительной записи: пользовательская часть (мини-программа WeChat + Flutter APP) + рабочий стол мастера (переключение роли в том же APP) + админка (PC Web).

## Архитектурные решения

| Решение | Вариант |
|------|------|
| Архитектура бэкенда | `admin/` (API админки) + `service/` (бизнес-API), два сервиса разделяют MySQL/Redis |
| Мини-программа пользователя | Нативная мини-программа WeChat `apps/wechat/` |
| APP пользователя | Flutter `apps/flutter/` (iOS + Android) |
| Учётная запись пользователя | Единый аккаунт, роли клиента/мастера переключаемы |
| Связь мини-программы и APP | Функционально идентичны, различаются только платформой |
| Фронтенд админки | Расширение существующего Flutter Web (`admin/apps/flutter/`) |
| Бэкенд админки | Расширение существующего webman v2 (`admin/`) бизнес-модулями |
| Сторонние сервисы | WeChat-вход/оплата/SMS/карты — схема подключения зарезервирована |

## Схема системной архитектуры

```
┌──────────────────────────────────────────────────────────┐
│                    Слой пользовательских устройств         │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ Мини-программа   │  │ Flutter APP       │              │
│  │ WeChat           │  │ apps/flutter/     │              │
│  │ apps/wechat/     │  │ (iOS + Android)   │              │
│  │ (нативный WXML/  │  └────────┬─────────┘              │
│  │  WXSS)           │           │                         │
│  └────────┬─────────┘           │                         │
│           │ Функционально идентичны │                      │
│           └──────────┬──────────┘                        │
│                      │ Переключение роли клиента/мастера  │
├──────────────────────┼──────────────────────────────────┤
│              Шлюз бизнес-API                              │
│  ┌──────────────────┐  ┌──────────────────┐              │
│  │ service/ API      │  │ admin/ API        │              │
│  │ (webman v2)       │  │ (webman v2)       │              │
│  │ пользователи/     │  │ интерфейсы       │              │
│  │ заказы/оплата/    │  │ админки          │              │
│  │ мастера/филиалы…  │  │ (создано + расширено)│            │
│  └────────┬─────────┘  └────────┬─────────┘              │
│           │                      │                        │
│           └──────────┬───────────┘                        │
│                      │                                    │
├──────────────────────┼──────────────────────────────────┤
│                   Слой данных                             │
│  ┌────────┐ ┌────────┐ ┌────────┐ ┌────────────────┐    │
│  │ MySQL  │ │ Redis  │ │  ES    │ │ Сторонние       │    │
│  │ 8.0    │ │ кэш/   │ │ поиск  │ │ сервисы        │    │
│  │        │ │ лимит  │ │        │ │ WeChat/SMS/     │    │
│  │        │ │ частоты│ │        │ │ карты          │    │
│  │        │ │Session │ │        │ │ (подключение    │    │
│  │        │ │        │ │        │ │  зарезервировано)│    │
│  └────────┘ └────────┘ └────────┘ └────────────────┘    │
└──────────────────────────────────────────────────────────┘
```

## Ключевые таблицы БД

Все таблицы используют префикс `erik_`, первичный ключ BIGINT без автоинкремента (генерируется через Snowflake). Чувствительные поля шифруются/расшифровываются через трейт encryptable.

### Домен пользователей и идентичности

| Таблица | Описание | Ключевые поля |
|------|------|----------|
| `erik_user` | Единая таблица пользователей | phone, password, wx_openid, wx_unionid, avatar, nickname, user_type(customer/technician), status. Пользователь-мастер одновременно сохраняет функции клиента и может свободно переключать текущую активную роль |
| `erik_user_address` | Адрес пользователя | user_id, contact_name, contact_phone, province, city, district, detail, is_default |
| `erik_technician_profile` | Профиль мастера | user_id, real_name, gender, id_card, id_card_front, id_card_back, avatar, rating, order_count, status(pending/approved/rejected), intro |
| `erik_technician_schedule` | Расписание мастера | technician_id, date, time_slots(JSON), status |
| `erik_technician_service` | Услуги мастера | technician_id, service_id |
| `erik_technician_earnings` | Поток доходов мастера | technician_id, order_id, type(commission/bonus/penalty), amount, status |
| `erik_technician_withdrawal` | Записи вывода средств мастера | technician_id, amount, actual_amount, commission_fee, account_info, status, reviewed_at |
| `erik_technician_attendance` | Учёт рабочего времени мастера | technician_id, date, check_in_at, check_out_at, clean_photo |
| `erik_technician_member_note` | Карточка клиента | technician_id, user_id, content, written_at |

### Домен услуг и товаров

| Таблица | Описание | Ключевые поля |
|------|------|----------|
| `erik_service_category` | Категория услуг | name, icon, parent_id, sort, status |
| `erik_service` | Услуга | category_id, name, description, cover_image, images(JSON), price, duration, sales_volume, specs(JSON), status |
| `erik_product` | Товар | category_id, name, cover_image, price, stock, sales_volume, type, status |
| `erik_store` | Филиал | name, address, lat, lng, phone, business_hours(JSON), images, status |

### Домен заказов

| Таблица | Описание | Ключевые поля |
|------|------|----------|
| `erik_order` | Основная таблица заказов | order_no, user_id, technician_id, store_id, total_amount, discount_amount, paid_amount, status, service_time, cancel_reason, remark |
| `erik_order_item` | Позиции заказа | order_id, service_id, product_id, type, name, price, quantity, spec_info |
| `erik_order_payment` | Записи оплат | order_id, pay_type(wechat), transaction_id, amount, status, paid_at |
| `erik_order_refund` | Записи возвратов | order_id, payment_id, refund_no, amount, ratio, reason, status |
| `erik_order_review` | Отзывы об услуге | order_id, user_id, technician_id, rating, content, images |
| `erik_order_verification` | Записи списаний | order_id, code, verified_at, verified_by, location |

### Маркетинговый домен

| Таблица | Описание | Ключевые поля |
|------|------|----------|
| `erik_coupon` | Определение купона | name, type, amount, min_amount, total_qty, remain_qty, start_at, end_at, status |
| `erik_user_coupon` | Купон пользователя | user_id, coupon_id, status(available/used/expired), used_at |
| `erik_member_card` | Определение карты | name, type(month/vip/times), price, duration_days, total_times, services(JSON) |
| `erik_user_member_card` | Карта пользователя | user_id, card_id, start_at, end_at, total_times, used_times, status |
| `erik_member_card_usage` | Записи использования карт | user_card_id, order_id, service_id, used_at |
| `erik_user_points` | Поток баллов | user_id, type(earn/use), points, source, order_id |
| `erik_gift_card` | Подарочная карта | code, type, amount_or_gift, status, used_by, used_at |
| `erik_user_referral` | Приглашения пользователя | referrer_id, referred_user_id, reward_type, reward_amount, registered_at, first_order_at |

### Домен контента и уведомлений

| Таблица | Описание | Ключевые поля |
|------|------|----------|
| `erik_banner` | Карусель баннеров | position, image, jump_type(url/detail/none), jump_value, sort, status |
| `erik_announcement` | Объявление | content, status, published_at |
| `erik_platform_agreement` | Соглашения платформы | type(user_agreement/privacy_policy/service_agreement), title, content, version |
| `erik_faq` | Частые вопросы | title, content, sort |
| `erik_feedback` | Обратная связь | user_id, content, images, handler_reply, status(pending/handled) |
| `erik_moment` | Динамика «моменты» | content, images, published_at |
| `erik_notification` | Уведомления | user_id, type(order/system), title, content, is_read, created_at |

### Финансовый домен (сторона admin)

| Таблица | Описание | Ключевые поля |
|------|------|----------|
| `erik_finance_transaction` | Поток доходов/расходов | user_id, order_id, type, direction(income/expense), amount, actual_amount, commission, status |
| `erik_technician_commission_config` | Конфигурация комиссии | technician_id, commission_rate, settlement_cycle |
| `erik_withdrawal_account` | Счёт вывода | user_id, type(wechat), account_name, account_no |
| `erik_withdrawal_config` | Конфигурация лимитов вывода | min_amount, reserve_amount, round_to_hundred |

## Модули API Service

### Публичные API (без аутентификации)
- **AuthController** — вход/регистрация/восстановление пароля/гостевой режим/переключение роли
- **CaptchaController** — SMS-код подтверждения
- **WechatController** — авторизация WeChat/вход/колбэк оплаты
- **CommonController** — тексты соглашений/о нас/информация о версии

### Модуль пользователя `user/` (требуется аутентификация)
- **ProfileController** — личная информация/смена пароля/перепривязка телефона/удаление аккаунта
- **AddressController** — CRUD адресов доставки
- **FavoriteController** — избранное
- **FeedbackController** — обратная связь
- **ReferralController** — приглашения/список приглашённых

### Модуль мастера `technician/` (требуется роль мастера + промежуточное ПО TechnicianAuth)
- **ProfileController** — профиль мастера/заявка на вступление
- **ScheduleController** — настройка расписания
- **OrderController** — записанные без списания/завершённые/списание по QR-коду
- **MemberController** — мои клиенты/карточки клиентов
- **EarningsController** — доходы/средства в пути
- **WithdrawalController** — вывод средств
- **AttendanceController** — учёт рабочего времени/фотографии чистоты

### Модуль услуг `service/`
- **CategoryController** — категории услуг
- **ItemController** — списки и детали услуг/товаров
- **SearchController** — поиск
- **StoreController** — список/детали филиалов

### Модуль заказов `order/` (требуется аутентификация)
- **CartController** — корзина
- **OrderController** — оформление/список/детали/отмена заказов
- **PaymentController** — оплата/возврат
- **VerificationController** — списание по QR-коду
- **ReviewController** — отзывы

### Маркетинговый модуль `marketing/` (требуется аутентификация)
- **CouponController** — список купонов/получение/использование
- **MemberCardController** — карты/карты по количеству услуг
- **PointsController** — баллы
- **GiftCardController** — подарочные карты

### Модуль контента `content/`
- **BannerController** — карусель баннеров
- **AnnouncementController** — объявления
- **NotificationController** — уведомления

### Модуль LBS
- **LocationController** — геолокация/переключение города/филиалы рядом

### Общие возможности `common/`
- SnowflakeService — генерация ID
- HashidsService — шифрование/расшифровка ID
- EncryptionService — шифрование/расшифровка чувствительных данных
- WechatPayService — оплата WeChat (зарезервировано)
- WechatAuthService — вход WeChat (зарезервировано)
- SmsService — SMS-сервис (зарезервировано)
- MapService — картографический сервис (зарезервировано)

### Промежуточное ПО
- Auth — JWT-аутентификация (общий с admin пакет erikwang2013/jwt-webman)
- TechnicianAuth — проверка роли мастера
- RateLimit — ограничение частоты (общее с admin)

## Расширение админки Admin

На существующем фреймворке добавляются контроллеры:

### Управление мастерами
- **TechnicianController** — список мастеров/поиск/экспорт/проверка/управление расписанием/настройка услуг/прогресс обучения

### Расширение управления пользователями
- **MemberController** — список клиентов/настройка уровней/статистика покупок

### Управление филиалами
- **StoreController** — CRUD филиалов/блокировка и разблокировка

### Управление услугами
- **ServiceController** — список услуг/CRUD/дизайн карт
- **ServiceCategoryController** — управление категориями
- **ProductController** — список товаров/CRUD

### Управление магазином
- **MallOrderController** — заказы магазина/отгрузка/послепродажное обслуживание/отзывы
- **SalesStatsController** — статистика продаж

### Управление заказами
- **AppointmentOrderController** — ожидающие использования заказы/отмена/подтверждение завершения

### Акции с купонами
- **CouponController** — CRUD купонов/выдача

### Управление финансами
- **FinanceController** — распределение по заказам/поток доходов и расходов
- **WithdrawalController** — проверка/завершение выводов мастеров
- **CommissionController** — настройка комиссии/премии и штрафы/запрос остатка
- **WithdrawalAccountController** — управление счетами вывода
- **WithdrawalConfigController** — конфигурация лимитов вывода

### Управление контентом
- **BannerController** — CRUD баннеров
- **AnnouncementController** — CRUD объявлений
- **FaqController** — CRUD FAQ
- **FeedbackController** — обработка обратной связи
- **MomentController** — проверка динамики «моменты»
- **AgreementController** — редактирование соглашений (пользовательское/конфиденциальность/сервисное)
- **AboutController** — настройка «о нас»

### Настройки
- **SystemMessageController** — настройка системных сообщений
- **AdminUserController** — управление подаккаунтами (на основе существующего RBAC)

### Расширение Dashboard
- Карточки статистики в реальном времени: число пользователей/всего заказов/число мастеров/число сервисных заказов
- Линейные графики: число заказов/сумма/новые пользователи в день/активность
- Быстрая навигация: кнопки модулей с ожидающими обработки задачами
- Внутренние сообщения: уведомления о новых заказах/возвратах

## Структура страниц пользовательской части

Мини-программа WeChat и Flutter APP функционально идентичны.

### auth/ — аутентификация
- login — вход (телефон/код/WeChat/гостевой вход)
- register — регистрация (телефон + код + пароль + реферальный код)
- forget-password — восстановление пароля
- agreement — просмотр соглашений

### home/ — главная
- index — главная (карусель + объявления + категории услуг + рекомендации)
- search — страница поиска

### service/ — услуги
- list — список услуг (фильтр по категориям)
- detail — детали услуги (базовая информация + отзывы + запись сразу)
- product-list — список товаров

### order/ — заказы
- confirm — подтверждение заказа (филиал/мастер/время/купон/примечание/соглашение)
- payment — страница оплаты
- payment-success — оплата успешна
- list — все заказы (фильтр по вкладкам статусов)
- detail — детали заказа
- review — отзыв об услуге
- verification — списание по QR-коду

### cart/ — корзина
- index — список корзины

### technician/ — мастера (взгляд клиента)
- list — список мастеров (сортировка по расстоянию от ближнего к дальнему)
- detail — детали мастера (отзывы/доступные услуги/запись сразу)
- apply — заявка мастера на вступление

### tech-work/ — рабочий стол мастера (роль мастера)
- index — главная рабочего стола (заказы за сегодня/обзор доходов)
- schedule — настройка расписания
- order-list — мои заказы (записанные без списания/завершённые)
- scan-verify — списание по QR-коду
- member-list — мои клиенты
- member-detail — детали клиента/редактирование карточки
- earnings — мои доходы
- withdrawal — вывод средств
- transaction-list — детализация операций
- attendance — учёт рабочего времени/загрузка фото чистоты
- training — профессиональное обучение

### user/ — личный кабинет
- index — личная информация (аватар/никнейм/карта/избранное/купон)
- settings — настройки (смена пароля/перепривязка телефона/соглашения/обновление/удаление аккаунта/выход)
- switch-role — переключение роли (клиент ↔ мастер)

### marketing/ — маркетинг
- coupon-list — список купонов
- member-card — мои карты
- points — мои баллы
- gift-card — мои подарочные карты
- referral — приглашения (описание + плакат с QR-кодом + список приглашённых)

### Другие страницы
- message/ — список сообщений/детали
- store/list, store/detail — список филиалов (сортировка LBS)/детали (навигация)
- other/about — о нас
- other/feedback — обратная связь
- other/official-account — подписка на официальный аккаунт

### Общие компоненты
- navbar, tabbar, service-card, technician-card
- coupon-popup, lbs-selector, empty-state, loading

### Логика переключения роли
- Нижняя навигация клиента: главная / услуги / корзина / заказы / мой
- Нижняя навигация мастера: рабочий стол / заказы / клиенты / доходы / мой
- Страница «мой» предоставляет вход переключения роли
- Пользователь, ещё не ставший мастером, при переключении на роль мастера направляется на страницу заявки

## Описание процессов покупки

В системе два разных процесса покупки:

### Процесс записи на услугу (прямое оформление, без корзины)
- Страница деталей услуги → подтверждение заказа (выбор филиала/мастера/времени) → оплата → списание
- Эксклюзивность ресурса мастера: при входе на страницу подтверждения заказа мастер блокируется на 3 минуты
- Используется для очных услуг: массаж, салоны красоты и т.п.

### Процесс покупки товара (режим корзины)
- Список товаров → добавить в корзину → подтверждение в корзине → отправка заказа → оплата → отгрузка/получение
- Поддержка изменения количества, удаления товара
- Используется для физических товаров и продажи купонов/карт

## Ключевые бизнес-правила

### Механизм блокировки мастера
- Один и тот же мастер не может быть записан несколькими пользователями одновременно
- При входе пользователя на страницу подтверждения заказа мастер блокируется через Redis SETNX на 3 минуты
- При выходе со страницы записи или по таймауту блокировка освобождается автоматически

### Правила возврата
| Условие | Доля возврата |
|------|----------|
| В течение 15 минут после заказа или расстояние до начала >6 часов | 100% |
| Расстояние до начала ≤6 часов | 90% |
| Уже начато, но услуга не подтверждена | 80% |
| После подтверждения начала услуги | 0% (возврата нет) |

### Правила скидок
- В непиковые часы (10–12 ч / 17–18 ч / после 21:00) — скидка 10%
- Запись за 30 минут заранее — скидка 5% (не суммируется с купонами)

### Вывод средств мастера
- Вывод возможен 20-го числа каждого месяца, зачисление T+1 рабочий день
- Поддержка вывода на WeChat-кошелёк
- Заказы без списания/без расчёта автоматически подтверждаются системой в течение 3 дней
- В течение 24 часов необходимо заполнить карточку клиента, иначе комиссия не выплачивается

### Награда постоянным клиентам
- Вторая покупка у того же мастера за 30 дней → запись бонуса
- После услуги загрузить фотографию чистоты

### Правила баллов
- Обмен 1:100 на подарочную карту (настраивается в админке)
- После успешной регистрации приглашённого и оформления заказа начисляются заданные баллы (настраивается в админке)
