# সিকিউরিটি আর্কিটেকচার ডায়াগ্রাম
> **Languages**: [中文](../../diagrams/SECURITY-ARCHITECTURE.md) · [English](../../en/diagrams/SECURITY-ARCHITECTURE.md) · [한국어](../../ko/diagrams/SECURITY-ARCHITECTURE.md) · [Русский](../../ru/diagrams/SECURITY-ARCHITECTURE.md) · [Deutsch](../../de/diagrams/SECURITY-ARCHITECTURE.md) · [Français](../../fr/diagrams/SECURITY-ARCHITECTURE.md) · [Español](../../es/diagrams/SECURITY-ARCHITECTURE.md) · [Português](../../pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](../../hi/diagrams/SECURITY-ARCHITECTURE.md) · [العربية](../../ar/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](../../id/diagrams/SECURITY-ARCHITECTURE.md) · [日本語](../../ja/diagrams/SECURITY-ARCHITECTURE.md)

> বাংলা অনুবাদ · মূল: [中文](../../diagrams/SECURITY-ARCHITECTURE.md)

## 1. ডিফেন্স-ইন-ডেপথ সিস্টেম

```mermaid
graph TB
    subgraph 边界防护["স্তর ১: বাউন্ডারি প্রোটেকশন"]
        WAF["WAF / Nginx<br/>সিকিউরিটি রেসপন্স হেডার<br/>সংবেদনশীল ফাইল প্রোটেকশন<br/>TLS 1.3"]
    end

    subgraph 接入防护["স্তর ২: অ্যাক্সেস প্রোটেকশন"]
        CORS["Cors মিডলওয়্যার<br/>CORS_ALLOW_ORIGIN হোয়াইটলিস্ট<br/>* ইকো · কনফিগ না থাকলে শুধু সম-অরিজিন<br/>৬টি সিকিউরিটি রেসপন্স হেডার<br/>OPTIONS প্রি-ফ্লাইট"]
    end

    subgraph 攻击检测["স্তর ৩: অ্যাটাক ডিটেকশন"]
        SEC["Security মিডলওয়্যার<br/>erikwang2013/security-php<br/>৩১ ধরনের অ্যাটাক ডিটেক্টর<br/>XSS / SQL ইনজেকশন / CSRF<br/>পাথ ট্রাভার্সাল / ফাইল ইনক্লুশন<br/>CSRF Origin ডিটেকশন(block)"]
        BLOCK["স্বয়ংক্রিয় ব্লক<br/>৬০ সেকেন্ডে ৫ বার অ্যাটাক<br/>→ IP ব্ল্যাকলিস্ট ১৫ মিনিট"]
    end

    subgraph 流量控制["স্তর ৪: ট্রাফিক কন্ট্রোল"]
        RL["RateLimit মিডলওয়্যার<br/>Redis স্লাইডিং উইন্ডো + Lua অ্যাটমিক<br/>ডিফল্ট: প্রতি মিনিটে ৬০ বার/IP<br/>লগইন: প্রতি মিনিটে ১০ বার<br/>রেজিস্ট্রেশন: প্রতি মিনিটে ৫ বার<br/>ভেরিফিকেশন কোড: প্রতি ৬০ সেকেন্ডে ১ বার/ফোন"]
    end

    subgraph 身份认证["স্তর ৫: আইডেন্টিটি অথেনটিকেশন"]
        AUTH["Auth মিডলওয়্যার<br/>JWT Bearer Token (৭ দিন)<br/>JWT_SECRET_KEY বাধ্যতামূলক কনফিগ<br/>অনুপস্থিত/পাবলিক ডিফল্ট মানে স্টার্ট নিষেধ<br/>পাসওয়ার্ড bcrypt হ্যাশ<br/>Token রিফ্রেশ + ব্ল্যাকলিস্ট<br/>লগইন লক: ৫ বার ব্যর্থ → ১৫ মিনিট<br/>কনকারেন্সি সীমা: সর্বোচ্চ ৩টি Token"]
        TECH_AUTH["TechnicianAuth<br/>টেকনিশিয়ান প্রোফাইল ভেরিফিকেশন<br/>approved স্ট্যাটাস চেক"]
        ADMIN_AUTH["AdminAuth<br/>Admin প্রান্তের JWT অথেনটিকেশন<br/>Token ব্ল্যাকলিস্ট"]
    end

    subgraph 权限控制["স্তর ৬: পারমিশন কন্ট্রোল"]
        RBAC["AdminPermission<br/>RBAC রোল পারমিশন ভেরিফিকেশন<br/>Redis ৬০ সেকেন্ড ক্যাশ<br/>ইউজার → রোল → পারমিশন"]
        POSTER["Poster ভেরিফিকেশন<br/>erikwang2013/poster-php<br/>ডিলিট/অডিট/উত্তোলন<br/>সংবেদনশীল অপারেশনে র্যান্ডম ভেরিফিকেশন"]
    end

    subgraph 数据安全["স্তর ৭: ডেটা সিকিউরিটি"]
        ENC_API["API স্তর এনক্রিপশন<br/>erikwang2013/encryption<br/>সংবেদনশীল ফিল্ড এনক্রিপশন/ডিক্রিপশন"]
        ENC_DB["DB স্তর এনক্রিপশন<br/>erikwang2013/encryptable<br/>Model trait অটো এনক্রিপশন/ডিক্রিপশন<br/>শুধু real_name/id_card প্রভৃতি এনক্রিপ্ট হয়<br/>phone/wx_openid প্লেইনটেক্সটে সংরক্ষণ বাধ্যতামূলক<br/>(লগইন/ডুপ্লিকেট চেক প্লেইনটেক্সট কোয়েরিতে নির্ভর করে)"]
        HASHID["ID এনক্রিপশন/ডিক্রিপশন<br/>erikwang2013/hashids<br/>বাইরে থেকে আসল ID লুকানো<br/>রিকার্সিভ এনকোড/ডিকোড"]
        SLOG["সিকিউরিটি লগ<br/>M3 অস্বাভাবিকতা ইউনিফাইড মাস্কিং<br/>সাধারণ টেক্সট + Log::error<br/>সংবেদনশীল ডেটা লগে যায় না<br/>OperationLog ৮ প্রান্তের সোর্স"]
    end

    subgraph 管理端防护["স্তর ৮: ম্যানেজমেন্ট প্রান্ত প্রোটেকশন"]
        EXCEL["এক্সপোর্ট প্রোটেকশন<br/>safeCellValue()<br/>= + - @ / Tab/CR শুরু<br/>প্রিফিক্স ' এস্কেপ ফর্মুলা ইনজেকশন প্রতিরোধ"]
        UPLOAD["আপলোড ভেরিফিকেশন<br/>finfo magic bytes<br/>MIME ও এক্সটেনশন মেলবন্ধ না হলে<br/>→ 422 প্রত্যাখ্যান"]
        INSTALL["ইনস্টল লক<br/>ইনস্টল করা থাকলে (installed=1<br/>বা অ্যাডমিন আছে)<br/>→ 404 ইনস্টল উইজার নিষ্ক্রিয়"]
    end

    请求["HTTP Request"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"পাস"| RL
    SEC -->|"অ্যাটাক ডিটেক্টেড"| BLOCK
    BLOCK -.->|"প্রত্যাখ্যান"| 拒绝["HTTP 403/429<br/>অ্যাটাক লগ রেকর্ড"]
    RL -->|"পাস"| AUTH
    RL -->|"সীমা অতিক্রম"| 限流拒绝["HTTP 429<br/>Retry-After"]
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
    INSTALL --> 响应["HTTP Response<br/>ডেটা এনক্রিপ্টেড + এনকোডেড"]

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

## 2. সিকিউরিটি কম্পোনেন্ট ম্যাট্রিক্স

```mermaid
graph LR
    subgraph 组件["সিকিউরিটি কম্পোনেন্ট"]
        C1["security-php<br/>━━━━━━━━<br/>৩১ ধরনের অ্যাটাক ডিটেকশন<br/>XSS/SQL ইনজেকশন/CSRF<br/>পাথ ট্রাভার্সাল/ফাইল ইনক্লুশন<br/>CSRF Origin ডিটেকশন"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>API স্তর এনক্রিপশন/ডিক্রিপশন<br/>কি রোটেশন সাপোর্ট"]
        C3["encryptable<br/>━━━━━━━━<br/>DB ফিল্ড অটো এনক্রিপশন/ডিক্রিপশন<br/>শুধু real_name/id_card প্রভৃতি এনক্রিপ্ট<br/>phone/wx_openid প্লেইনটেক্সট সংরক্ষণ<br/>VARCHAR(500) এনক্রিপশন-ব্লোট সামঞ্জস্য"]
        C4["hashids<br/>━━━━━━━━<br/>ID এনকোড/ডিকোড<br/>সম্পর্ক রিকার্সিভ প্রসেসিং<br/>বাইরে থেকে আসল ID লুকানো"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>JWT_SECRET_KEY বাধ্যতামূলক কনফিগ<br/>অনুপস্থিত/ডিফল্ট মানে স্টার্ট নিষেধ<br/>৭ দিন + রিফ্রেশ + ব্ল্যাকলিস্ট<br/>কনকারেন্সি ≤৩টি"]
        C6["poster-php<br/>━━━━━━━━<br/>অপারেশনের আগে র্যান্ডম ভেরিফিকেশন<br/>ডিলিট/অডিট/উত্তোলন<br/>ভুল অপারেশন প্রতিরোধ"]
        C7["snowflake-php<br/>━━━━━━━━<br/>BIGINT ডিস্ট্রিবিউটেড ID<br/>নন-অটো-ইনক্রিমেন্ট ট্রাভার্সাল প্রতিরোধ<br/>গ্লোবাল ইউনিক"]
    end

    subgraph 攻击面["প্রোটেকশন অ্যাটাক সারফেস"]
        A1["ইনজেকশন অ্যাটাক<br/>SQL/কমান্ড/LDAP"]
        A2["XSS/CSRF<br/>ক্রস-সাইট স্ক্রিপ্টিং/রিকোয়েস্ট ফোর্জারি"]
        A3["পাথ ট্রাভার্সাল<br/>ডিরেক্টরি ট্রাভার্সাল/ফাইল ইনক্লুশন"]
        A4["ব্রুট ফোর্স<br/>লগইন ব্রুট/ভেরিফিকেশন কোড ব্রুট"]
        A5["ডেটা লিক<br/>ID ট্রাভার্সাল/সংবেদনশীল ফিল্ড"]
        A6["অনথরাইজড অপারেশন<br/>হরাইজন্টাল/ভার্টিকাল প্রিভিলেজ এসকেলেশন"]
        A7["কনকারেন্সি অপব্যবহার<br/>Token ফ্লাড/ইন্টারফেস স্প্যাম"]
    end

    C1 -.->|"প্রতিরোধ"| A1
    C1 -.->|"প্রতিরোধ"| A2
    C1 -.->|"প্রতিরোধ"| A3
    C2 -.->|"প্রতিরোধ"| A5
    C3 -.->|"প্রতিরোধ"| A5
    C4 -.->|"প্রতিরোধ"| A5
    C5 -.->|"প্রতিরোধ"| A4
    C5 -.->|"প্রতিরোধ"| A7
    C6 -.->|"প্রতিরোধ"| A6
    C7 -.->|"প্রতিরোধ"| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. অথেনটিকেশন ও অথরাইজেশন ফ্লো

```mermaid
flowchart TD
    A["ক্লায়েন্ট রিকোয়েস্ট"] --> B{"Token আছে?"}
    B -->|"নেই"| C["401 রিটার্ন<br/>লগইন প্রম্পট"]
    B -->|"আছে"| D["JWT Token পার্স"]
    D --> E{"Token বৈধ?"}
    E -->|"মেয়াদ শেষ"| F{"Refresh Token?"}
    F -->|"হ্যাঁ"| G["Token রিফ্রেশ<br/>পুরনো Token ব্ল্যাকলিস্টে"]
    F -->|"না"| C
    G --> H["নতুন Token রিটার্ন"]
    E -->|"বৈধ"| I{"ব্ল্যাকলিস্ট চেক"}
    I -->|"ব্ল্যাকলিস্টেড"| C
    I -->|"স্বাভাবিক"| J["ইউজার তথ্য কোয়েরি"]
    J --> K{"ইউজার আছে ও সক্রিয়?"}
    K -->|"না"| L["403 রিটার্ন<br/>অ্যাকাউন্ট নিষ্ক্রিয়"]
    K -->|"হ্যাঁ"| M{"লগইন ব্যর্থ সংখ্যা?"}
    M -->|"১৫ মিনিটে ≥৫ বার"| N["429 রিটার্ন<br/>অ্যাকাউন্ট লকড"]
    M -->|"স্বাভাবিক"| O{"কনকারেন্ট Token সংখ্যা?"}
    O -->|">৩টি"| P["পুরনো Token অটো অকার্যকর<br/>ব্ল্যাকলিস্টে যোগ"]
    O -->|"≤৩টি"| Q{"টেকনিশিয়ান আইডেন্টিটি দরকার?"}
    Q -->|"হ্যাঁ"| R{"টেকনিশিয়ান প্রোফাইল approved?"}
    R -->|"না"| S["403 রিটার্ন<br/>টেকনিশিয়ান নয় বা অডিট চলছে"]
    R -->|"হ্যাঁ"| T{"RBAC দরকার?"}
    Q -->|"না"| T
    T -->|"হ্যাঁ"| U{"পারমিশন ভেরিফিকেশন"}
    U -->|"পারমিশন নেই"| V["403 রিটার্ন<br/>অপারেশন পারমিশন নেই"]
    U -->|"পারমিশন আছে"| W["বিজনেস লজিক এক্সিকিউশন"]
    T -->|"না"| W
    W --> X["রেসপন্স রিটার্ন<br/>ID এনকোডেড<br/>সংবেদনশীল ডেটা এনক্রিপ্টেড"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. ডেটা সিকিউরিটি ফ্লো

```mermaid
flowchart LR
    subgraph 输入["ইউজার ইনপুট"]
        I1["প্লেইনটেক্সট ফোন"]
        I2["প্লেইনটেক্সট আইডি কার্ড"]
        I3["প্লেইনটেক্সট OpenID"]
        I4["প্লেইনটেক্সট নাম"]
    end

    subgraph API加密["API স্তর (encryption)"]
        E1["encrypt(id_card)<br/>→ ciphertext"]
        E2["encrypt(real_name)<br/>→ ciphertext"]
    end

    subgraph DB存储["DB স্তর স্টোরেজ"]
        D1["appointment_user.phone<br/>প্লেইনটেক্সট সংরক্ষণ<br/>লগইন/ডুপ্লিকেট চেক প্লেইনটেক্সট কোয়েরিতে নির্ভর"]
        D2["appointment_technician_profile<br/>.id_card VARCHAR(500)<br/>encryptable এনক্রিপ্টেড"]
        D3["appointment_user.wx_openid<br/>প্লেইনটেক্সট সংরক্ষণ"]
        D4["appointment_user.real_name<br/>encryptable এনক্রিপ্টেড"]
    end

    subgraph ID处理["ID প্রসেসিং (hashids + snowflake)"]
        H1["Snowflake জেনারেট<br/>1860000000000001"]
        H2["Hashids এনকোড<br/>→ 'Kx9mP2vR'"]
        H3["API রেসপন্স<br/>id: 'Kx9mP2vR'"]
    end

    subgraph 输出["বহিরাগত আউটপুট"]
        O1["ID এনকোডেড<br/>ট্রাভার্স করা যায় না"]
        O2["সংবেদনশীল ফিল্ড মাস্কড<br/>লগে প্লেইনটেক্সট নেই"]
        O3["রেসপন্স হেডারে সিকিউরিটি পলিসি<br/>CSP/CORS/HSTS"]
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
