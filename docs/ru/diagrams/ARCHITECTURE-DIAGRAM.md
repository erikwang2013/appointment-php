# Схема системной архитектуры
> **Languages**: [中文](../../diagrams/ARCHITECTURE-DIAGRAM.md) · [English](../../en/diagrams/ARCHITECTURE-DIAGRAM.md) · [한국어](../../ko/diagrams/ARCHITECTURE-DIAGRAM.md) · [Deutsch](../../de/diagrams/ARCHITECTURE-DIAGRAM.md) · [Français](../../fr/diagrams/ARCHITECTURE-DIAGRAM.md) · [Español](../../es/diagrams/ARCHITECTURE-DIAGRAM.md) · [Português](../../pt/diagrams/ARCHITECTURE-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/ARCHITECTURE-DIAGRAM.md) · [العربية](../../ar/diagrams/ARCHITECTURE-DIAGRAM.md) · [বাংলা](../../bn/diagrams/ARCHITECTURE-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/ARCHITECTURE-DIAGRAM.md) · [日本語](../../ja/diagrams/ARCHITECTURE-DIAGRAM.md)

```mermaid
graph TB
    subgraph 用户终端层["Слой пользовательских устройств"]
        WX["Мини-программа WeChat<br/>apps/wechat/<br/>нативный WXML/WXSS/JS"]
        APP["Flutter APP<br/>apps/flutter/<br/>iOS + Android<br/>GetX + Dio"]
    end

    subgraph 业务服务层["Слой бизнес-сервисов :8787"]
        direction TB
        MW1["Цепочка промежуточного ПО<br/>Cors → Security → RateLimit"]
        subgraph API模块["Модули маршрутов API"]
            PUB["Публичные API<br/>api/<br/>вход/регистрация/проверочный код"]
            USER["Модуль пользователя<br/>user/<br/>профиль/адреса/избранное"]
            TECH["Модуль мастера<br/>technician/<br/>расписание/рабочий стол/списание/доходы/вывод"]
            SVC["Модуль услуг<br/>service/<br/>категории/проекты/поиск"]
            ORD["Модуль заказов<br/>order/<br/>корзина/оформление/оплата/возврат/списание"]
            MKT["Модуль маркетинга<br/>marketing/<br/>купоны/карты(по количеству услуг)/баллы<br/>подарочные карты/привилегии"]
            WALLET["Модуль кошелька<br/>wallet/<br/>остаток/пополнение/операции<br/>оплата с баланса"]
            CTN["Модуль контента<br/>content/<br/>карусель/объявления/уведомления"]
            LBS["Модуль LBS<br/>lbs/<br/>города/филиалы рядом"]
            CACHE["Кэш списков Redis<br/>префикс svc:* setex 300с<br/>категории/проекты/товары/мастера/контент<br/>карты/списки маркетинга<br/>на путях записи admin clearSvcCache() инвалидация"]
            RES["Контракт ответов<br/>success/paginate code=0<br/>код ошибки ≠ 0<br/>совпадает с договорённостями с мини-программой"]
        end
    end

    subgraph 管理后台层["Слой админки :8787"]
        MW2["Цепочка промежуточного ПО<br/>Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN_API["API админки<br/>admin/controller/<br/>панель/пользователи/мастера/филиалы/услуги<br/>заказы/купоны/карты/выводы/отзывы<br/>отчёты/финансы/контент/настройки"]
        FLUTTER_WEB["Фронтенд Flutter Web<br/>admin/apps/flutter/<br/>интерфейс админки PC"]
        MODEL["Общие модели<br/>admin/app/model<br/>39 symlink<br/>→ service/app/model одна реализация"]
    end

    subgraph 数据层["Слой данных"]
        MySQL[("MySQL 8.0<br/>55+ таблиц · префикс appointment_<br/>BIGINT Snowflake первичный ключ")]
        Redis[("Redis<br/>кэш/лимит частоты/Session<br/>очереди/блокировки мастера<br/>кэш списков svc:*")]
        ES[("Elasticsearch<br/>полнотекстовый поиск<br/>webman-scout автосинхронизация")]
    end

    subgraph 外部服务["Сторонние сервисы"]
        WXPAY["WeChat Pay<br/>единый заказ/возврат/вывод"]
        SMS["SMS-сервис<br/>Aliyun/Tencent"]
        MAP["Картографический сервис<br/>Amap/Tencent<br/>обратное гео/навигация"]
        OSS["Объектное хранилище<br/>локально/OSS/COS/CDN"]
        SUBMSG["Подписные сообщения WeChat<br/>WechatTemplateMessageService<br/>sendSubscribeMessage<br/>3 сценария событий заказа"]
    end

    subgraph 安全组件["Слой компонентов безопасности"]
        SEC["Security-PHP<br/>обнаружение 31 типа атак"]
        JWT["JWT-аутентификация<br/>срок 7 дней + чёрный список"]
        ENC["Двухуровневое шифрование<br/>уровень API + уровень БД"]
        POSTER["Верификация операций<br/>случайная проверка чувствительных операций"]
    end

    WX -->|"HTTP API<br/>функционально эквивалентны"| MW1
    APP -->|"HTTP API<br/>функционально эквивалентны"| MW1
    MW1 --> API模块

    FLUTTER_WEB -->|"HTTP API"| MW2
    MW2 --> ADMIN_API

    API模块 --> MySQL
    API模块 --> Redis
    API模块 --> ES
    ADMIN_API --> MySQL
    ADMIN_API --> Redis
    ADMIN_API --> ES

    安全组件 -.->|защита| 业务服务层
    安全组件 -.->|защита| 管理后台层

    API模块 -.->|вызов| 外部服务
    ADMIN_API -.->|вызов| 外部服务

    classDef terminal fill:#e1f5fe,stroke:#0288d1,stroke-width:2px,color:#01579b
    classDef service fill:#fff3e0,stroke:#f57c00,stroke-width:2px,color:#e65100
    classDef admin fill:#e8f5e9,stroke:#388e3c,stroke-width:2px,color:#1b5e20
    classDef data fill:#fce4ec,stroke:#c62828,stroke-width:2px,color:#880e4f
    classDef external fill:#f3e5f5,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef security fill:#fff8e1,stroke:#f9a825,stroke-width:2px,color:#f57f17

    class WX,APP terminal
    class MW1,API模块,PUB,USER,TECH,SVC,ORD,MKT,WALLET,CTN,LBS,CACHE,RES service
    class MW2,ADMIN_API,FLUTTER_WEB,MODEL admin
    class MySQL,Redis,ES data
    class WXPAY,SMS,MAP,OSS,SUBMSG external
    class SEC,JWT,ENC,POSTER security
```
