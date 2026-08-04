# Appointment Service System

A three-platform appointment service management platform: WeChat Mini Program + Flutter App (same-account role switching) + PC Admin Dashboard.

> **Status**: All complete | 104 Controllers | 58 Models | 80 Tests | 55+ Tables | 242 Routes

## Project Structure

```
appointment-php/
├── admin/              # Admin dashboard (webman v2 + Flutter Web)
├── service/            # Business API service (webman v2)
├── apps/               # Client-side frontend apps
│   ├── wechat/         #   WeChat Mini Program (native)
│   └── flutter/        #   Flutter App (iOS + Android)
└── docs/               # Documentation
```

## Quick Start

### Requirements

- PHP 8.3+
- MySQL 8.0+
- Redis
- Composer

### Web Installer (Recommended)

```bash
cd admin/
cp .env.example .env
composer install
php start.php start -d
```

Open `http://localhost:8787/install` in browser and follow the wizard to configure database and admin account.

### Manual Installation

```bash
# 1. Install dependencies
cd service/ && cp .env.example .env && composer install
cd ../admin/ && cp .env.example .env && composer install

# 2. Import unified database script (55 tables + demo data)
mysql -u root -p < docs/install.sql

# 3. Start services
cd service/ && php start.php start -d   # Business API → :8788
cd ../admin/ && php start.php start -d  # Admin API → :8787
```

### Docker Deployment

```bash
cd admin/ && cp .env.docker .env && docker-compose up -d
cd ../service/ && cp .env.docker .env && docker-compose up -d
```

## Tech Stack

| Layer | Technology | Description |
|-------|------------|-------------|
| Backend Framework | webman v2 (PHP 8.3+) | High-performance persistent-memory HTTP service |
| Database | MySQL 8.0 | Table prefix `erik_` |
| Cache | Redis | Cache / Rate limiting / Session / Queue |
| Search | Elasticsearch | Full-text search (via webman-scout) |
| Admin Frontend | Flutter Web | PC admin dashboard style |
| Client App | Flutter | iOS + Android |
| Client Mini Program | Native WeChat Mini Program | WXML / WXSS / JS |
| ID Generation | erikwang2013/snowflake-php | BIGINT non-auto-increment primary keys |
| API ID Encode/Decode | erikwang2013/hashids | Hide real IDs from external exposure |
| JWT Authentication | erikwang2013/jwt-webman | Bearer Token |
| Sensitive Data Encryption | erikwang2013/encryption + encryptable | Dual-layer API + DB encryption |
| Security Protection | erikwang2013/security-php | 31 attack detectors |
| Action Verification | erikwang2013/poster-php | Random verification for sensitive actions |
| Country Flags | erikwang2013/season | Flag icons |
| ES Sync | erikwang2013/webman-scout | Automatic model synchronization |

## Architecture

```mermaid
graph TB
    subgraph terminal["Clients"]
        WX["WeChat Mini Program<br/>apps/wechat/"]
        APP["Flutter App<br/>apps/flutter/"]
    end

    subgraph api["Business API :8788"]
        MW1["Cors → Security → RateLimit → Auth"]
        API["Public · User · Tech · Service · Order · Marketing · Content · LBS"]
    end

    subgraph admin["Admin Dashboard :8787"]
        MW2["Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN["Dashboard · User · Tech · Store · Service · Order · Coupon · Finance · Content · Settings"]
        FW["Flutter Web Frontend"]
    end

    subgraph data["Data Layer"]
        MySQL[("MySQL 8.0<br/>55+ tables erik_")]
        Redis[("Redis<br/>Cache/RateLimit/Lock")]
        ES[("Elasticsearch<br/>Full-text Search")]
    end

    subgraph external["3rd-Party Services"]
        WXPAY["WeChat Pay"]
        SMS["SMS Service"]
        MAP["Map Service"]
        OSS["Object Storage"]
    end

    subgraph security["Security Components"]
        SEC["security-php<br/>31 attack detectors"]
        JWT["jwt-webman<br/>Token + Blacklist"]
        ENC["encryption<br/>API + DB dual-layer"]
    end

    WX & APP -->|"HTTP API"| MW1 --> API
    FW -->|"HTTP API"| MW2 --> ADMIN
    API & ADMIN --> MySQL & Redis & ES
    security -.->|protects| api & admin
    API & ADMIN -.->|calls| external

    classDef client fill:#e1f5fe,stroke:#0288d1,color:#01579b
    classDef service fill:#fff3e0,stroke:#f57c00,color:#e65100
    classDef admin fill:#e8f5e9,stroke:#388e3c,color:#1b5e20
    classDef dat fill:#fce4ec,stroke:#c62828,color:#880e4f
    classDef ext fill:#f3e5f5,stroke:#7b1fa2,color:#4a148c
    classDef sec fill:#fff8e1,stroke:#f9a825,color:#f57f17

    class WX,APP client
    class MW1,API service
    class MW2,ADMIN,FW admin
    class MySQL,Redis,ES dat
    class WXPAY,SMS,MAP,OSS ext
    class SEC,JWT,ENC sec
```

## Core Flows

### Appointment Booking

```mermaid
flowchart TD
    A["Browse Services"] --> B["Select Store/Tech/Time"]
    B --> C["Confirm Order<br/>Coupon/Notes/Agreement"]
    C --> D{"Redis Lock Tech<br/>SETNX 3min"}
    D -->|"OK"| E["Create Order pending"]
    D -->|"Conflict"| F["Tech Busy"]
    G["WeChat Pay"]
    E --> G
    G -->|"Success"| H["Order paid<br/>Notify User+Tech"]
    G -->|"Failed"| I["Keep pending<br/>Auto-cancel 15min"]
    H --> J["Tech Confirm → serving"]
    J --> K["Complete → Verify"]
    K --> L["completed → Review"]
    L --> M["reviewed ✅"]

    style A fill:#e3f2fd,stroke:#1565c0,color:#01579b
    style M fill:#c8e6c9,stroke:#2e7d32,color:#1b5e20
    style F fill:#ffcdd2,stroke:#c62828,color:#b71c1c
```

### Payment & Refund

```mermaid
flowchart LR
    subgraph pay["Payment"]
        P1["Create Payment"] --> P2["WeChat Unified Order"] --> P3["Invoke Pay"] --> P4["Callback Verify"] --> P5["paid"]
    end
    subgraph refund["Refund Rules"]
        R1["Request Refund"] --> R2{"Judge"}
        R2 -->|">6h / ≤15min"| R3["Refund 100%"]
        R2 -->|"≤6h"| R4["Refund 90%"]
        R2 -->|"Started"| R5["Refund 80%"]
        R2 -->|"Confirmed"| R6["No Refund"]
        R3 & R4 & R5 --> R7["2-Level Approval<br/>Manager→Finance"] --> R8["refunded"]
    end

    style P5 fill:#c8e6c9,stroke:#2e7d32,color:#1b5e20
    style R6 fill:#ffcdd2,stroke:#c62828,color:#b71c1c
    style R8 fill:#c8e6c9,stroke:#2e7d32,color:#1b5e20
```

## Order Lifecycle

```mermaid
stateDiagram-v2
    [*] --> pending: Submit Order

    pending --> paid: Payment OK
    pending --> cancelled: Timeout/Cancel

    paid --> confirmed: Tech Confirm
    paid --> cancelled: Cancel (by rules)
    paid --> refunding: Request Refund

    confirmed --> serving: Service Start
    serving --> completed: Verify Complete
    serving --> refunding: Anomaly Refund(80%)

    completed --> reviewed: User Review

    refunding --> refunded: Approved
    refunding --> paid: Rejected

    reviewed --> [*]
    cancelled --> [*]
    refunded --> [*]

    note right of pending: Redis lock tech 3min
    note right of refunding: Manager→Finance approval
```

## Security Architecture

### Defense-in-Depth (7 Layers)

```mermaid
graph TB
    subgraph L1["① Perimeter"]
        WAF["WAF/Nginx · TLS 1.3 · Security Headers"]
    end
    subgraph L2["② Access"]
        CORS["Cors · CORS_ALLOW_ORIGIN · OPTIONS Preflight"]
    end
    subgraph L3["③ Attack Detection"]
        SEC["security-php · 31 Detectors<br/>XSS/SQLi/CSRF/Path Traversal"]
        BLOCK["Auto-ban: 5 attacks/60s → IP blacklist 15min"]
    end
    subgraph L4["④ Rate Limiting"]
        RL["RateLimit · Redis Sliding Window + Lua<br/>Default 60/min · Login 10 · Captcha 1/60s"]
    end
    subgraph L5["⑤ Authentication"]
        AUTH["JWT(7d+refresh+blacklist) · bcrypt<br/>Lock 5 fails/15min · Max 3 Tokens"]
    end
    subgraph L6["⑥ Authorization"]
        RBAC["RBAC(Redis cache) · poster-php verification"]
    end
    subgraph L7["⑦ Data Security"]
        DATA["encryption API · encryptable DB<br/>hashids ID encode · No PII in logs"]
    end

    请求["Request"] --> WAF --> CORS --> SEC
    SEC -->|"Pass"| RL --> AUTH --> RBAC --> DATA --> 响应["Response"]
    SEC -->|"Attack"| BLOCK -.-> 拒绝["403/429"]

    classDef layer1 fill:#e3f2fd,stroke:#1565c0,color:#01579b
    classDef layer2 fill:#bbdefb,stroke:#1976d2,color:#01579b
    classDef layer3 fill:#ffcdd2,stroke:#c62828,color:#b71c1c
    classDef layer4 fill:#fff9c4,stroke:#f9a825,color:#f57f17
    classDef layer5 fill:#c8e6c9,stroke:#2e7d32,color:#1b5e20
    classDef layer6 fill:#e1bee7,stroke:#7b1fa2,color:#4a148c
    classDef layer7 fill:#d7ccc8,stroke:#5d4037,color:#3e2723

    class WAF layer1
    class CORS layer2
    class SEC,BLOCK layer3
    class RL layer4
    class AUTH layer5
    class RBAC layer6
    class DATA layer7
```

> More diagrams: [Flowcharts](docs/diagrams/FLOWCHART.md) (withdrawal/role switch) | [Function Map](docs/diagrams/FUNCTION-DIAGRAM.md) | [All Lifecycles](docs/diagrams/LIFECYCLE-DIAGRAM.md) | [Full Security Architecture](docs/diagrams/SECURITY-ARCHITECTURE.md)

## Documentation

| Document | Description |
|----------|-------------|
| [Architecture](docs/ARCHITECTURE.md) | System architecture, platform relationships, technical components, data flow |
| [Features](docs/FEATURES.md) | Complete feature list: client / technician / admin dashboard |
| [Architecture Design](docs/ARCHITECTURE-DESIGN.md) | Layered design, middleware chain, database design, security design |
| [Feature Design](docs/FEATURE-DESIGN.md) | Core business flows, business rules, state machines, refund rules |
| [API Reference](docs/API.md) | Business API + Admin API with request/response examples + OpenAPI endpoint |
| [Installation](docs/INSTALL.md) | Requirements, Docker deployment, environment variables, third-party config, FAQ |
| [Usage Guide](docs/USAGE.md) | Admin configuration, client/technician operations, API examples, refund rules |
| [Project Structure](docs/STRUCTURE.md) | Full directory layout, middleware execution chain, database table list |
| [Design Spec](docs/superpowers/specs/2026-05-26-appointment-system-design.md) | System design specification |
| [Implementation Plan](docs/superpowers/plans/2026-05-26-appointment-system-plan.md) | Phased implementation plan |

## Support

If this project helps you, your support is welcome and appreciated!

<table>
  <tr>
    <td align="center" width="50%">
      <img src="docs/weixinpay.png" alt="WeChat Pay" width="130" height="130"><br>
      <b>WeChat Pay</b>
    </td>
    <td align="center" width="50%">
      <img src="docs/alipay.png" alt="Alipay" width="130" height="130"><br>
      <b>Alipay</b>
    </td>
  </tr>
</table>

## License

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
