# Схема архитектуры безопасности
> **Languages**: [中文](../../diagrams/SECURITY-ARCHITECTURE.md) · [English](../../en/diagrams/SECURITY-ARCHITECTURE.md) · [한국어](../../ko/diagrams/SECURITY-ARCHITECTURE.md) · [Deutsch](../../de/diagrams/SECURITY-ARCHITECTURE.md) · [Français](../../fr/diagrams/SECURITY-ARCHITECTURE.md) · [Español](../../es/diagrams/SECURITY-ARCHITECTURE.md) · [Português](../../pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](../../hi/diagrams/SECURITY-ARCHITECTURE.md) · [العربية](../../ar/diagrams/SECURITY-ARCHITECTURE.md) · [বাংলা](../../bn/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](../../id/diagrams/SECURITY-ARCHITECTURE.md) · [日本語](../../ja/diagrams/SECURITY-ARCHITECTURE.md)

## 1. Система эшелонированной обороны

```mermaid
graph TB
    subgraph 边界防护["Первый слой: защита границы"]
        WAF["WAF / Nginx<br/>безопасные заголовки ответов<br/>защита чувствительных файлов<br/>TLS 1.3"]
    end

    subgraph 接入防护["Второй слой: защита подключения"]
        CORS["Промежуточное ПО Cors<br/>белый список CORS_ALLOW_ORIGIN<br/>* эхо · без конфигурации только same-origin<br/>6 безопасных заголовков ответов<br/>предварительный запрос OPTIONS"]
    end

    subgraph 攻击检测["Третий слой: обнаружение атак"]
        SEC["Промежуточное ПО Security<br/>erikwang2013/security-php<br/>31 детектор атак<br/>XSS / SQL-инъекции / CSRF<br/>обход путей / включение файлов<br/>детекция CSRF Origin (block)"]
        BLOCK["Автоматическая блокировка<br/>5 атак/60с<br/>→ IP-чёрный список 15мин"]
    end

    subgraph 流量控制["Четвёртый слой: контроль трафика"]
        RL["Промежуточное ПО RateLimit<br/>Redis скользящее окно + атомарность Lua<br/>по умолчанию: 60 раз/мин/IP<br/>вход: 10 раз/мин<br/>регистрация: 5 раз/мин<br/>проверочный код: 1 раз/60с/телефон"]
    end

    subgraph 身份认证["Пятый слой: идентификация"]
        AUTH["Промежуточное ПО Auth<br/>JWT Bearer Token (7 дней)<br/>JWT_SECRET_KEY обязателен<br/>запуск отклоняется при отсутствии/публичном значении по умолчанию<br/>пароли хешируются bcrypt<br/>обновление Token + чёрный список<br/>блокировка входа: 5 сбоев → 15мин<br/>лимит сессий: максимум 3 Token"]
        TECH_AUTH["TechnicianAuth<br/>проверка профиля мастера<br/>проверка статуса approved"]
        ADMIN_AUTH["AdminAuth<br/>JWT-аутентификация админки<br/>чёрный список Token"]
    end

    subgraph 权限控制["Шестой слой: контроль прав"]
        RBAC["AdminPermission<br/>проверка ролей RBAC<br/>кэш Redis 60с<br/>пользователь → роль → право"]
        POSTER["Верификация Poster<br/>erikwang2013/poster-php<br/>удаление/проверка/вывод<br/>случайная верификация чувствительных операций"]
    end

    subgraph 数据安全["Седьмой слой: безопасность данных"]
        ENC_API["Шифрование уровня API<br/>erikwang2013/encryption<br/>шифрование/расшифровка чувствительных полей"]
        ENC_DB["Шифрование уровня БД<br/>erikwang2013/encryptable<br/>автошифрование трейтом Model<br/>шифруются только real_name/id_card и т.п.<br/>phone/wx_openid обязаны храниться открыто<br/>(вход/поиск дублей зависят от открытых запросов)"]
        HASHID["Шифрование ID<br/>erikwang2013/hashids<br/>скрытие реальных ID наружу<br/>рекурсивное кодирование/декодирование"]
        SLOG["Журнал безопасности<br/>M3 аномалии единообразно маскируются<br/>общий текст + Log::error<br/>чувствительные данные не попадают в журнал<br/>OperationLog 8 источников"]
    end

    subgraph 管理端防护["Восьмой слой: защита админки"]
        EXCEL["Защита экспорта<br/>safeCellValue()<br/>префиксы = + - @ / Tab/CR<br/>экранирование ' против инъекции формул"]
        UPLOAD["Проверка загрузок<br/>finfo magic bytes<br/>MIME и расширение не совпадают<br/>→ отказ 422"]
        INSTALL["Блокировка установки<br/>уже установлено (installed=1<br/>или есть администратор)<br/>→ 404 отключение мастера установки"]
    end

    请求["HTTP Request"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"пройдено"| RL
    SEC -->|"обнаружена атака"| BLOCK
    BLOCK -.->|"отказ"| 拒绝["HTTP 403/429<br/>запись журнала атак"]
    RL -->|"пройдено"| AUTH
    RL -->|"превышен лимит"| 限流拒绝["HTTP 429<br/>Retry-After"]
    AUTH --> TECH_AUTH
    AUTH --> ADMIN_AUTH
    TECH_AUTH --> RBAC
    ADMIN_AUTH --> RBAC
    RBAC --> POSTER
    POSTER --> ENC_API
    ENC_API --> ENC_DB
    ENC_DB --> HASHID
    HASHID --> SLOG
    SLOG --> EXCEL
    EXCEL --> UPLOAD
    UPLOAD --> INSTALL
    INSTALL --> 响应["HTTP Response<br/>данные зашифрованы + закодированы"]

    classDef layer1 fill:#e3f2fd,stroke:#1565c0,stroke-width:2px,color:#01579b
    classDef layer2 fill:#bbdefb,stroke:#1976d2,stroke-width:2px,color:#01579b
    classDef layer3 fill:#ffcdd2,stroke:#c62828,stroke-width:2px,color:#b71c1c
    classDef layer4 fill:#fff9c4,stroke:#f9a825,stroke-width:2px,color:#f57f17
    classDef layer5 fill:#c8e6c9,stroke:#2e7d32,stroke-width:2px,color:#1b5e20
    classDef layer6 fill:#e1bee7,stroke:#7b1fa2,stroke-width:2px,color:#4a148c
    classDef layer7 fill:#d7ccc8,stroke:#5d4037,stroke-width:2px,color:#3e2723
    classDef layer8 fill:#cfd8dc,stroke:#37474f,stroke-width:2px,color:#263238
    classDef reject fill:#ff5252,stroke:#b71c1c,stroke-width:2px,color:#fff

    class WAF layer1
    class CORS layer2
    class SEC,BLOCK layer3
    class RL layer4
    class AUTH,TECH_AUTH,ADMIN_AUTH layer5
    class RBAC,POSTER layer6
    class ENC_API,ENC_DB,HASHID,SLOG layer7
    class EXCEL,UPLOAD,INSTALL layer8
    class 拒绝,限流拒绝 reject
```

## 2. Матрица компонентов безопасности

```mermaid
graph LR
    subgraph 组件["Компоненты безопасности"]
        C1["security-php<br/>━━━━━━━━<br/>31 детектор атак<br/>XSS/SQL-инъекции/CSRF<br/>обход путей/включение файлов<br/>детекция CSRF Origin"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>шифрование уровня API<br/>поддержка ротации ключей"]
        C3["encryptable<br/>━━━━━━━━<br/>автошифрование полей БД<br/>шифруются только real_name/id_card и т.п.<br/>phone/wx_openid хранятся открыто<br/>совместимость с расширением VARCHAR(500)"]
        C4["hashids<br/>━━━━━━━━<br/>кодирование/декодирование ID<br/>рекурсивная обработка связей<br/>скрытие реальных ID наружу"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>JWT_SECRET_KEY обязателен<br/>отказ запуска при отсутствии/значении по умолчанию<br/>7 дней + обновление + чёрный список<br/>параллельно ≤3"]
        C6["poster-php<br/>━━━━━━━━<br/>случайная верификация перед операцией<br/>удаление/проверка/вывод<br/>защита от ошибок"]
        C7["snowflake-php<br/>━━━━━━━━<br/>распределённый BIGINT ID<br/>без автоинкремента против перебора<br/>глобальная уникальность"]
    end

    subgraph 攻击面["Защищаемые поверхности атак"]
        A1["Инъекции<br/>SQL/команды/LDAP"]
        A2["XSS/CSRF<br/>межсайтовые скрипты/подделка запросов"]
        A3["Обход путей<br/>выход из каталога/включение файлов"]
        A4["Перебор<br/>подбор входа/кодов"]
        A5["Утечка данных<br/>перебор ID/чувствительные поля"]
        A6["Несанкционированный доступ<br/>горизонтальный/вертикальный"]
        A7["Злоупотребление конкурентностью<br/>наплыв Token/дёрганье интерфейсов"]
    end

    C1 -.->|защита| A1
    C1 -.->|защита| A2
    C1 -.->|защита| A3
    C2 -.->|защита| A5
    C3 -.->|защита| A5
    C4 -.->|защита| A5
    C5 -.->|защита| A4
    C5 -.->|защита| A7
    C6 -.->|защита| A6
    C7 -.->|защита| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. Процесс аутентификации и авторизации

```mermaid
flowchart TD
    A["Запрос клиента"] --> B{"Есть Token?"}
    B -->|"нет"| C["Возврат 401<br/>предложение войти"]
    B -->|"есть"| D["Разбор JWT Token"]
    D --> E{"Token действителен?"}
    E -->|"истёк"| F{"Есть Refresh Token?"}
    F -->|"да"| G["Обновление Token<br/>старый Token в чёрный список"]
    F -->|"нет"| C
    G --> H["Возврат нового Token"]
    E -->|"действителен"| I{"Проверка чёрного списка"}
    I -->|"в списке"| C
    I -->|"нормально"| J["Запрос данных пользователя"]
    J --> K{"Пользователь существует и активен?"}
    K -->|"нет"| L["Возврат 403<br/>аккаунт отключён"]
    K -->|"да"| M{"Число неудачных входов?"}
    M -->|"≥5 раз/15мин"| N["Возврат 429<br/>аккаунт заблокирован"]
    M -->|"нормально"| O{"Число параллельных Token?"}
    O -->|">3"| P["Старые Token автоматически недействительны<br/>в чёрный список"]
    O -->|"≤3"| Q{"Нужна роль мастера?"}
    Q -->|"да"| R{"Профиль мастера approved?"}
    R -->|"нет"| S["Возврат 403<br/>не мастер или на проверке"]
    R -->|"да"| T{"Нужен RBAC?"}
    Q -->|"нет"| T
    T -->|"да"| U{"Проверка прав"}
    U -->|"нет прав"| V["Возврат 403<br/>нет права на операцию"]
    U -->|"есть права"| W["Выполнение бизнес-логики"]
    T -->|"нет"| W
    W --> X["Возврат ответа<br/>ID закодированы<br/>чувствительные данные зашифрованы"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. Поток безопасности данных

```mermaid
flowchart LR
    subgraph 输入["Ввод пользователя"]
        I1["Открытый номер телефона"]
        I2["Открытое удостоверение личности"]
        I3["Открытый OpenID"]
        I4["Открытое имя"]
    end

    subgraph API加密["Уровень API (encryption)"]
        E1["encrypt(id_card)<br/>→ ciphertext"]
        E2["encrypt(real_name)<br/>→ ciphertext"]
    end

    subgraph DB存储["Хранение на уровне БД"]
        D1["erik_user.phone<br/>открытое хранение<br/>вход/поиск дублей зависят от открытых запросов"]
        D2["erik_technician_profile<br/>.id_card VARCHAR(500)<br/>шифрование encryptable"]
        D3["erik_user.wx_openid<br/>открытое хранение"]
        D4["erik_user.real_name<br/>шифрование encryptable"]
    end

    subgraph ID处理["Обработка ID (hashids + snowflake)"]
        H1["Генерация Snowflake<br/>1860000000000001"]
        H2["Кодирование Hashids<br/>→ 'Kx9mP2vR'"]
        H3["Ответ API<br/>id: 'Kx9mP2vR'"]
    end

    subgraph 输出["Внешний вывод"]
        O1["ID закодированы<br/>перебор невозможен"]
        O2["Чувствительные поля маскированы<br/>журналы без открытого текста"]
        O3["Заголовки ответа с политикой безопасности<br/>CSP/CORS/HSTS"]
    end

    I1 --> D1
    I2 --> E1 --> D2
    I3 --> D3
    I4 --> E2 --> D4
    D1 --> H1 --> H2 --> H3
    H3 --> O1
    D1 --> O2
    O1 --> O3

    classDef input fill:#e3f2fd,stroke:#1565c0,color:#333
    classDef encrypt fill:#fff3e0,stroke:#f57c00,color:#333
    classDef db fill:#fce4ec,stroke:#c62828,color:#333
    classDef id fill:#e8f5e9,stroke:#2e7d32,color:#333
    classDef output fill:#f3e5f5,stroke:#7b1fa2,color:#333

    class I1,I2,I3,I4 input
    class E1,E2 encrypt
    class D1,D2,D3,D4 db
    class H1,H2,H3 id
    class O1,O2,O3 output
```
