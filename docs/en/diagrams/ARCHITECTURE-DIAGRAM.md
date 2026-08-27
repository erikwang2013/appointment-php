# System Architecture Diagram
> **Languages**: [中文](../../diagrams/ARCHITECTURE-DIAGRAM.md) · [한국어](../../ko/diagrams/ARCHITECTURE-DIAGRAM.md) · [Русский](../../ru/diagrams/ARCHITECTURE-DIAGRAM.md) · [Deutsch](../../de/diagrams/ARCHITECTURE-DIAGRAM.md) · [Français](../../fr/diagrams/ARCHITECTURE-DIAGRAM.md) · [Español](../../es/diagrams/ARCHITECTURE-DIAGRAM.md) · [Português](../../pt/diagrams/ARCHITECTURE-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/ARCHITECTURE-DIAGRAM.md) · [العربية](../../ar/diagrams/ARCHITECTURE-DIAGRAM.md) · [বাংলা](../../bn/diagrams/ARCHITECTURE-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/ARCHITECTURE-DIAGRAM.md) · [日本語](../../ja/diagrams/ARCHITECTURE-DIAGRAM.md)

```mermaid
graph TB
    subgraph 用户终端层["User Client Layer"]
        WX["WeChat Mini Program<br/>apps/wechat/<br/>native WXML/WXSS/JS"]
        APP["Flutter APP<br/>apps/flutter/<br/>iOS + Android<br/>GetX + Dio"]
    end

    subgraph 业务服务层["Business Service Layer :8787"]
        direction TB
        MW1["Middleware chain<br/>Cors → Security → RateLimit"]
        subgraph API模块["API route modules"]
            PUB["Public API<br/>api/<br/>login/register/captcha"]
            USER["User module<br/>user/<br/>profile/address/favorites"]
            TECH["Technician module<br/>technician/<br/>schedule/workbench/verify/earnings/withdraw"]
            SVC["Service module<br/>service/<br/>categories/items/search"]
            ORD["Order module<br/>order/<br/>cart/place order/pay/refund/verify"]
            MKT["Marketing module<br/>marketing/<br/>coupons/member cards (session cards)/points<br/>gift cards/member benefits"]
            WALLET["Wallet module<br/>wallet/<br/>balance/top-up/transactions<br/>balance payment"]
            CTN["Content module<br/>content/<br/>banners/announcements/notifications"]
            LBS["LBS module<br/>lbs/<br/>cities/nearby stores"]
            CACHE["Redis list cache<br/>svc:* prefix setex 300s<br/>categories/items/products/technicians/content<br/>card & marketing list endpoints<br/>admin write path clearSvcCache() invalidation"]
            RES["Response contract<br/>success/paginate code=0<br/>error codes non-zero<br/>aligned with Mini Program conventions"]
        end
    end

    subgraph 管理后台层["Admin Layer :8787"]
        MW2["Middleware chain<br/>Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN_API["Admin API<br/>admin/controller/<br/>dashboard/users/technicians/stores/services<br/>orders/coupons/member cards/withdrawals/reviews<br/>reports/finance/content/settings"]
        FLUTTER_WEB["Flutter Web frontend<br/>admin/apps/flutter/<br/>PC admin dashboard UI"]
        MODEL["Shared models<br/>admin/app/model<br/>39 symlinks<br/>→ service/app/model same implementation"]
    end

    subgraph 数据层["Data Layer"]
        MySQL[("MySQL 8.0<br/>55+ tables · appointment_ prefix<br/>BIGINT Snowflake primary keys")]
        Redis[("Redis<br/>cache/rate limit/Session<br/>queue/technician locks<br/>svc:* list cache")]
        ES[("Elasticsearch<br/>full-text search<br/>webman-scout auto sync")]
    end

    subgraph 外部服务["Third-Party Services"]
        WXPAY["WeChat Pay<br/>unified order/refund/withdraw"]
        SMS["SMS service<br/>Aliyun/Tencent"]
        MAP["Map service<br/>AMap/Tencent<br/>reverse geocode/navigation"]
        OSS["Object storage<br/>local/OSS/COS/CDN"]
        SUBMSG["WeChat subscribe messages<br/>WechatTemplateMessageService<br/>sendSubscribeMessage<br/>3 order-event scenarios"]
    end

    subgraph 安全组件["Security Component Layer"]
        SEC["Security-PHP<br/>31 attack detectors"]
        JWT["JWT auth<br/>7-day validity + blacklist"]
        ENC["Dual-layer encryption<br/>API layer + DB layer"]
        POSTER["Operation verification<br/>random verification for sensitive ops"]
    end

    WX -->|"HTTP API<br/>functionally equivalent"| MW1
    APP -->|"HTTP API<br/>functionally equivalent"| MW1
    MW1 --> API模块

    FLUTTER_WEB -->|"HTTP API"| MW2
    MW2 --> ADMIN_API

    API模块 --> MySQL
    API模块 --> Redis
    API模块 --> ES
    ADMIN_API --> MySQL
    ADMIN_API --> Redis
    ADMIN_API --> ES

    安全组件 -.->|"protection"| 业务服务层
    安全组件 -.->|"protection"| 管理后台层

    API模块 -.->|"calls"| 外部服务
    ADMIN_API -.->|"calls"| 外部服务

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
