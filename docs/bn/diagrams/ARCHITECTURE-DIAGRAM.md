# সিস্টেম আর্কিটেকচার ডায়াগ্রাম
> **Languages**: [中文](../../diagrams/ARCHITECTURE-DIAGRAM.md) · [English](../../en/diagrams/ARCHITECTURE-DIAGRAM.md) · [한국어](../../ko/diagrams/ARCHITECTURE-DIAGRAM.md) · [Русский](../../ru/diagrams/ARCHITECTURE-DIAGRAM.md) · [Deutsch](../../de/diagrams/ARCHITECTURE-DIAGRAM.md) · [Français](../../fr/diagrams/ARCHITECTURE-DIAGRAM.md) · [Español](../../es/diagrams/ARCHITECTURE-DIAGRAM.md) · [Português](../../pt/diagrams/ARCHITECTURE-DIAGRAM.md) · [हिन्दी](../../hi/diagrams/ARCHITECTURE-DIAGRAM.md) · [العربية](../../ar/diagrams/ARCHITECTURE-DIAGRAM.md) · [Bahasa Indonesia](../../id/diagrams/ARCHITECTURE-DIAGRAM.md) · [日本語](../../ja/diagrams/ARCHITECTURE-DIAGRAM.md)

> বাংলা অনুবাদ · মূল: [中文](../../diagrams/ARCHITECTURE-DIAGRAM.md)

```mermaid
graph TB
    subgraph 用户终端层["ইউজার টার্মিনাল লেয়ার"]
        WX["WeChat মিনি-প্রোগ্রাম<br/>apps/wechat/<br/>নেটিভ WXML/WXSS/JS"]
        APP["Flutter APP<br/>apps/flutter/<br/>iOS + Android<br/>GetX + Dio"]
    end

    subgraph 业务服务层["বিজনেস সার্ভিস লেয়ার :8787"]
        direction TB
        MW1["মিডলওয়্যার চেইন<br/>Cors → Security → RateLimit"]
        subgraph API模块["API রাউট মডিউল"]
            PUB["পাবলিক API<br/>api/<br/>লগইন/রেজিস্ট্রেশন/ভেরিফিকেশন কোড"]
            USER["ইউজার মডিউল<br/>user/<br/>প্রোফাইল/ঠিকানা/ফেভারিট"]
            TECH["টেকনিশিয়ান মডিউল<br/>technician/<br/>শিডিউল/ওয়ার্কবেঞ্চ/ভেরিফিকেশন/আয়/উত্তোলন"]
            SVC["সার্ভিস মডিউল<br/>service/<br/>ক্যাটাগরি/আইটেম/সার্চ"]
            ORD["অর্ডার মডিউল<br/>order/<br/>কার্ট/অর্ডার/পেমেন্ট/রিফান্ড/ভেরিফিকেশন"]
            MKT["মার্কেটিং মডিউল<br/>marketing/<br/>কুপন/মেম্বার কার্ড(টাইমস কার্ড)/পয়েন্ট<br/>গিফট কার্ড/মেম্বার সুবিধা"]
            WALLET["ওয়ালেট মডিউল<br/>wallet/<br/>ব্যালেন্স/রিচার্জ/লেনদেন লগ<br/>ব্যালেন্স পেমেন্ট"]
            CTN["কনটেন্ট মডিউল<br/>content/<br/>ব্যানার/ঘোষণা/নোটিফিকেশন"]
            LBS["LBS মডিউল<br/>lbs/<br/>সিটি/কাছের শাখা"]
            CACHE["Redis তালিকা ক্যাশ<br/>svc:* প্রিফিক্স setex 300s<br/>ক্যাটাগরি/আইটেম/প্রোডাক্ট/টেকনিশিয়ান/কনটেন্ট<br/>কার্ড আইটেম/মার্কেটিং তালিকা ইন্টারফেস<br/>admin লেখার পথে clearSvcCache() ইনভালিডেশন"]
            RES["রেসপন্স কন্ট্রাক্ট<br/>success/paginate code=0<br/>এরর কোড নন-0<br/>মিনি-প্রোগ্রাম কনভেনশনের সাথে সামঞ্জস্যপূর্ণ"]
        end
    end

    subgraph 管理后台层["ম্যানেজমেন্ট ব্যাকএন্ড লেয়ার :8787"]
        MW2["মিডলওয়্যার চেইন<br/>Cors → Security → RateLimit → AdminAuth → RBAC → OperationLog"]
        ADMIN_API["ম্যানেজমেন্ট API<br/>admin/controller/<br/>ড্যাশবোর্ড/ইউজার/টেকনিশিয়ান/শাখা/সার্ভিস<br/>অর্ডার/কুপন/মেম্বার কার্ড/উত্তোলন/রিভিউ<br/>রিপোর্ট/ফাইন্যান্স/কনটেন্ট/সেটিং"]
        FLUTTER_WEB["Flutter Web ফ্রন্টএন্ড<br/>admin/apps/flutter/<br/>PC ম্যানেজমেন্ট ব্যাকএন্ড UI"]
        MODEL["মডেল শেয়ারিং<br/>admin/app/model<br/>39টি symlink<br/>→ service/app/model একই ইমপ্লিমেন্টেশন"]
    end

    subgraph 数据层["ডেটা লেয়ার"]
        MySQL[("MySQL 8.0<br/>55+ টেবিল · appointment_ প্রিফিক্স<br/>BIGINT Snowflake প্রাইমারি কি")]
        Redis[("Redis<br/>ক্যাশ/রেট লিমিট/Session<br/>কিউ/টেকনিশিয়ান লক<br/>svc:* তালিকা ক্যাশ")]
        ES[("Elasticsearch<br/>ফুল-টেক্সট সার্চ<br/>webman-scout স্বয়ংক্রিয় সিঙ্ক")]
    end

    subgraph 外部服务["থার্ড-পার্টি সার্ভিস"]
        WXPAY["WeChat পেমেন্ট<br/>ইউনিফাইড অর্ডার/রিফান্ড/উত্তোলন"]
        SMS["SMS সার্ভিস<br/>Aliyun/Tencent"]
        MAP["ম্যাপ সার্ভিস<br/>Gaode/Tencent<br/>রিভার্স জিওকোডিং/নেভিগেশন"]
        OSS["অবজেক্ট স্টোরেজ<br/>লোকাল/OSS/COS/CDN"]
        SUBMSG["WeChat সাবস্ক্রিপশন মেসেজ<br/>WechatTemplateMessageService<br/>sendSubscribeMessage<br/>অর্ডার ইভেন্ট 3টি সিনারিও"]
    end

    subgraph 安全组件["সিকিউরিটি কম্পোনেন্ট লেয়ার"]
        SEC["Security-PHP<br/>31 ধরনের অ্যাটাক ডিটেকশন"]
        JWT["JWT অথেনটিকেশন<br/>7 দিনের মেয়াদ + ব্ল্যাকলিস্ট"]
        ENC["দ্বৈত-স্তর এনক্রিপশন<br/>API স্তর + DB স্তর"]
        POSTER["অপারেশন ভেরিফিকেশন<br/>সংবেদনশীল অপারেশনে র্যান্ডম ভেরিফিকেশন"]
    end

    WX -->|"HTTP API<br/>ফাংশন-সমতুল্য"| MW1
    APP -->|"HTTP API<br/>ফাংশন-সমতুল্য"| MW1
    MW1 --> API模块

    FLUTTER_WEB -->|"HTTP API"| MW2
    MW2 --> ADMIN_API

    API模块 --> MySQL
    API模块 --> Redis
    API模块 --> ES
    ADMIN_API --> MySQL
    ADMIN_API --> Redis
    ADMIN_API --> ES

    安全组件 -.->|"সুরক্ষা"| 业务服务层
    安全组件 -.->|"সুরক্ষা"| 管理后台层

    API模块 -.->|"কল"| 外部服务
    ADMIN_API -.->|"কল"| 外部服务

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
