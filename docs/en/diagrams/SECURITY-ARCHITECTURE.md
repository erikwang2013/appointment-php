# Security Architecture Diagram
> **Languages**: [中文](../../diagrams/SECURITY-ARCHITECTURE.md) · [한국어](../../ko/diagrams/SECURITY-ARCHITECTURE.md) · [Русский](../../ru/diagrams/SECURITY-ARCHITECTURE.md) · [Deutsch](../../de/diagrams/SECURITY-ARCHITECTURE.md) · [Français](../../fr/diagrams/SECURITY-ARCHITECTURE.md) · [Español](../../es/diagrams/SECURITY-ARCHITECTURE.md) · [Português](../../pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](../../hi/diagrams/SECURITY-ARCHITECTURE.md) · [العربية](../../ar/diagrams/SECURITY-ARCHITECTURE.md) · [বাংলা](../../bn/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](../../id/diagrams/SECURITY-ARCHITECTURE.md) · [日本語](../../ja/diagrams/SECURITY-ARCHITECTURE.md)

## 1. Defense in Depth

```mermaid
graph TB
    subgraph 边界防护["Layer 1: Perimeter Defense"]
        WAF["WAF / Nginx<br/>security response headers<br/>sensitive file protection<br/>TLS 1.3"]
    end

    subgraph 接入防护["Layer 2: Access Control"]
        CORS["Cors middleware<br/>CORS_ALLOW_ORIGIN whitelist<br/>* echoed · same-origin only when unconfigured<br/>6 security response headers<br/>OPTIONS preflight"]
    end

    subgraph 攻击检测["Layer 3: Attack Detection"]
        SEC["Security middleware<br/>erikwang2013/security-php<br/>31 attack detectors<br/>XSS / SQL injection / CSRF<br/>path traversal / file inclusion<br/>CSRF Origin detection (block)"]
        BLOCK["Auto ban<br/>5 attacks/60s<br/>→ IP blacklist 15min"]
    end

    subgraph 流量控制["Layer 4: Traffic Control"]
        RL["RateLimit middleware<br/>Redis sliding window + atomic Lua<br/>default: 60/min/IP<br/>login: 10/min<br/>register: 5/min<br/>captcha: 1/60s/phone"]
    end

    subgraph 身份认证["Layer 5: Identity & Authentication"]
        AUTH["Auth middleware<br/>JWT Bearer Token (7 days)<br/>JWT_SECRET_KEY mandatory<br/>refuses to start when missing/public default<br/>bcrypt password hashing<br/>token refresh + blacklist<br/>login lockout: 5 failures → 15min<br/>concurrency limit: max 3 tokens"]
        TECH_AUTH["TechnicianAuth<br/>technician profile validation<br/>approved status check"]
        ADMIN_AUTH["AdminAuth<br/>admin JWT auth<br/>token blacklist"]
    end

    subgraph 权限控制["Layer 6: Permission Control"]
        RBAC["AdminPermission<br/>RBAC role permission check<br/>Redis 60s cache<br/>user → role → permission"]
        POSTER["Poster verification<br/>erikwang2013/poster-php<br/>delete/review/withdraw<br/>random verification for sensitive ops"]
    end

    subgraph 数据安全["Layer 7: Data Security"]
        ENC_API["API-layer encryption<br/>erikwang2013/encryption<br/>sensitive field encrypt/decrypt"]
        ENC_DB["DB-layer encryption<br/>erikwang2013/encryptable<br/>model trait auto encrypt/decrypt<br/>only real_name/id_card etc. encrypted<br/>phone/wx_openid stored plaintext<br/>(login/dedupe depend on plaintext queries)"]
        HASHID["ID encrypt/decrypt<br/>erikwang2013/hashids<br/>hides real IDs externally<br/>recursive encode/decode"]
        SLOG["Security logging<br/>M3 exceptions uniformly masked<br/>generic message + Log::error<br/>no sensitive data in logs<br/>OperationLog 8-platform sources"]
    end

    subgraph 管理端防护["Layer 8: Admin-Side Protection"]
        EXCEL["Export protection<br/>safeCellValue()<br/>= + - @ / Tab/CR prefixes<br/>escaped with ' against formula injection"]
        UPLOAD["Upload validation<br/>finfo magic bytes<br/>MIME vs extension mismatch<br/>→ 422 rejected"]
        INSTALL["Install lock<br/>already installed (installed=1<br/>or admin exists)<br/>→ 404 disables install wizard"]
    end

    请求["HTTP Request"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"passed"| RL
    SEC -->|"attack detected"| BLOCK
    BLOCK -.->|"rejected"| 拒绝["HTTP 403/429<br/>attack logged"]
    RL -->|"passed"| AUTH
    RL -->|"limit exceeded"| 限流拒绝["HTTP 429<br/>Retry-After"]
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
    INSTALL --> 响应["HTTP Response<br/>data encrypted + encoded"]

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

## 2. Security Component Matrix

```mermaid
graph LR
    subgraph 组件["Security components"]
        C1["security-php<br/>━━━━━━━━<br/>31 attack detectors<br/>XSS/SQL injection/CSRF<br/>path traversal/file inclusion<br/>CSRF Origin detection"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>API-layer encrypt/decrypt<br/>key rotation supported"]
        C3["encryptable<br/>━━━━━━━━<br/>DB field auto encrypt/decrypt<br/>only real_name/id_card etc. encrypted<br/>phone/wx_openid stored plaintext<br/>VARCHAR(500) for encrypted expansion"]
        C4["hashids<br/>━━━━━━━━<br/>ID encode/decode<br/>recursive related processing<br/>hides real IDs externally"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>JWT_SECRET_KEY mandatory<br/>refuses to start when missing/default<br/>7 days + refresh + blacklist<br/>concurrency ≤3"]
        C6["poster-php<br/>━━━━━━━━<br/>random verification before ops<br/>delete/review/withdraw<br/>prevents misoperations"]
        C7["snowflake-php<br/>━━━━━━━━<br/>BIGINT distributed IDs<br/>non-increment against enumeration<br/>globally unique"]
    end

    subgraph 攻击面["Attack surfaces protected"]
        A1["Injection attacks<br/>SQL/command/LDAP"]
        A2["XSS/CSRF<br/>cross-site scripting/request forgery"]
        A3["Path traversal<br/>directory traversal/file inclusion"]
        A4["Brute force<br/>login/captcha brute force"]
        A5["Data leakage<br/>ID enumeration/sensitive fields"]
        A6["Privilege escalation<br/>horizontal/vertical"]
        A7["Concurrency abuse<br/>token flooding/endpoint hammering"]
    end

    C1 -.->|"defends"| A1
    C1 -.->|"defends"| A2
    C1 -.->|"defends"| A3
    C2 -.->|"defends"| A5
    C3 -.->|"defends"| A5
    C4 -.->|"defends"| A5
    C5 -.->|"defends"| A4
    C5 -.->|"defends"| A7
    C6 -.->|"defends"| A6
    C7 -.->|"defends"| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. Authentication & Authorization Flow

```mermaid
flowchart TD
    A["Client request"] --> B{"Has token?"}
    B -->|"No"| C["Return 401<br/>prompt login"]
    B -->|"Yes"| D["Parse JWT token"]
    D --> E{"Token valid?"}
    E -->|"Expired"| F{"Has Refresh Token?"}
    F -->|"Yes"| G["Refresh token<br/>old token added to blacklist"]
    F -->|"No"| C
    G --> H["Return new token"]
    E -->|"Valid"| I{"Blacklist check"}
    I -->|"Blacklisted"| C
    I -->|"Normal"| J["Query user info"]
    J --> K{"User exists and enabled?"}
    K -->|"No"| L["Return 403<br/>account disabled"]
    K -->|"Yes"| M{"Login failure count?"}
    M -->|"≥5 times/15min"| N["Return 429<br/>account locked"]
    M -->|"Normal"| O{"Concurrent token count?"}
    O -->|">3"| P["Old tokens invalidated<br/>added to blacklist"]
    O -->|"≤3"| Q{"Technician identity required?"}
    Q -->|"Yes"| R{"Technician profile approved?"}
    R -->|"No"| S["Return 403<br/>not a technician or under review"]
    R -->|"Yes"| T{"RBAC required?"}
    Q -->|"No"| T
    T -->|"Yes"| U{"Permission check"}
    U -->|"No permission"| V["Return 403<br/>no operation permission"]
    U -->|"Has permission"| W["Execute business logic"]
    T -->|"No"| W
    W --> X["Return response<br/>IDs encoded<br/>sensitive data encrypted"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. Data Security Flow

```mermaid
flowchart LR
    subgraph 输入["User input"]
        I1["Plaintext phone"]
        I2["Plaintext ID card"]
        I3["Plaintext OpenID"]
        I4["Plaintext name"]
    end

    subgraph API加密["API layer (encryption)"]
        E1["encrypt(id_card)<br/>→ ciphertext"]
        E2["encrypt(real_name)<br/>→ ciphertext"]
    end

    subgraph DB存储["DB layer storage"]
        D1["appointment_user.phone<br/>stored plaintext<br/>login/dedupe rely on plaintext queries"]
        D2["appointment_technician_profile<br/>.id_card VARCHAR(500)<br/>encryptable encrypted"]
        D3["appointment_user.wx_openid<br/>stored plaintext"]
        D4["appointment_user.real_name<br/>encryptable encrypted"]
    end

    subgraph ID处理["ID handling (hashids + snowflake)"]
        H1["Snowflake generates<br/>1860000000000001"]
        H2["Hashids encodes<br/>→ 'Kx9mP2vR'"]
        H3["API response<br/>id: 'Kx9mP2vR'"]
    end

    subgraph 输出["External output"]
        O1["IDs encoded<br/>not enumerable"]
        O2["Sensitive fields masked<br/>no plaintext in logs"]
        O3["Response headers carry security policy<br/>CSP/CORS/HSTS"]
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
