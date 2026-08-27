# 安全架构图
> **多语言**：[English](en/diagrams/SECURITY-ARCHITECTURE.md) · [한국어](ko/diagrams/SECURITY-ARCHITECTURE.md) · [Русский](ru/diagrams/SECURITY-ARCHITECTURE.md) · [Deutsch](de/diagrams/SECURITY-ARCHITECTURE.md) · [Français](fr/diagrams/SECURITY-ARCHITECTURE.md) · [Español](es/diagrams/SECURITY-ARCHITECTURE.md) · [Português](pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](hi/diagrams/SECURITY-ARCHITECTURE.md) · [العربية](ar/diagrams/SECURITY-ARCHITECTURE.md) · [বাংলা](bn/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](id/diagrams/SECURITY-ARCHITECTURE.md) · [日本語](ja/diagrams/SECURITY-ARCHITECTURE.md)

## 1. 纵深防御体系

```mermaid
graph TB
    subgraph 边界防护["第一层：边界防护"]
        WAF["WAF / Nginx<br/>安全响应头<br/>敏感文件保护<br/>TLS 1.3"]
    end

    subgraph 接入防护["第二层：接入防护"]
        CORS["Cors 中间件<br/>CORS_ALLOW_ORIGIN 白名单<br/>* 回显 · 未配置仅同源<br/>6个安全响应头<br/>OPTIONS 预检"]
    end

    subgraph 攻击检测["第三层：攻击检测"]
        SEC["Security 中间件<br/>erikwang2013/security-php<br/>31种攻击检测器<br/>XSS / SQL注入 / CSRF<br/>路径遍历 / 文件包含<br/>CSRF Origin 检测(block)"]
        BLOCK["自动封禁<br/>5次攻击/60s<br/>→ IP黑名单 15min"]
    end

    subgraph 流量控制["第四层：流量控制"]
        RL["RateLimit 中间件<br/>Redis 滑动窗口 + Lua 原子化<br/>默认: 60次/min/IP<br/>登录: 10次/min<br/>注册: 5次/min<br/>验证码: 1次/60s/手机号"]
    end

    subgraph 身份认证["第五层：身份认证"]
        AUTH["Auth 中间件<br/>JWT Bearer Token (7天)<br/>JWT_SECRET_KEY 强制配置<br/>缺失/公开默认值拒绝启动<br/>密码 bcrypt 哈希<br/>Token 刷新 + 黑名单<br/>登录锁定: 5次失败→15min<br/>并发限制: 最多3个Token"]
        TECH_AUTH["TechnicianAuth<br/>技师档案校验<br/>approved 状态检查"]
        ADMIN_AUTH["AdminAuth<br/>Admin端JWT认证<br/>Token黑名单"]
    end

    subgraph 权限控制["第六层：权限控制"]
        RBAC["AdminPermission<br/>RBAC 角色权限校验<br/>Redis 60s 缓存<br/>用户→角色→权限"]
        POSTER["Poster验证<br/>erikwang2013/poster-php<br/>删除/审核/提现<br/>敏感操作随机验证"]
    end

    subgraph 数据安全["第七层：数据安全"]
        ENC_API["API层加密<br/>erikwang2013/encryption<br/>敏感字段加解密"]
        ENC_DB["DB层加密<br/>erikwang2013/encryptable<br/>Model trait自动加解密<br/>只加密 real_name/id_card 等<br/>phone/wx_openid 必须明文存储<br/>（登录/查重依赖明文查询）"]
        HASHID["ID加解密<br/>erikwang2013/hashids<br/>对外隐藏真实ID<br/>递归编码/解码"]
        SLOG["安全日志<br/>M3 异常统一脱敏<br/>通用文案 + Log::error<br/>敏感数据不入日志<br/>OperationLog 8端来源"]
    end

    subgraph 管理端防护["第八层：管理端防护"]
        EXCEL["导出防护<br/>safeCellValue()<br/>= + - @ / Tab/CR 开头<br/>前缀 ' 转义防公式注入"]
        UPLOAD["上传校验<br/>finfo magic bytes<br/>MIME 与扩展名不匹配<br/>→ 422 拒绝"]
        INSTALL["安装锁<br/>已安装(installed=1<br/>或存在管理员)<br/>→ 404 禁用安装向导"]
    end

    请求["HTTP Request"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"通过"| RL
    SEC -->|"检测到攻击"| BLOCK
    BLOCK -.->|"拒绝"| 拒绝["HTTP 403/429<br/>记录攻击日志"]
    RL -->|"通过"| AUTH
    RL -->|"超限"| 限流拒绝["HTTP 429<br/>Retry-After"]
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
    INSTALL --> 响应["HTTP Response<br/>数据已加密+编码"]

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

## 2. 安全组件矩阵

```mermaid
graph LR
    subgraph 组件["安全组件"]
        C1["security-php<br/>━━━━━━━━<br/>31种攻击检测<br/>XSS/SQL注入/CSRF<br/>路径遍历/文件包含<br/>CSRF Origin检测"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>API层加解密<br/>密钥轮换支持"]
        C3["encryptable<br/>━━━━━━━━<br/>DB字段自动加解密<br/>只加密 real_name/id_card 等<br/>phone/wx_openid 明文存储<br/>VARCHAR(500) 加密膨胀兼容"]
        C4["hashids<br/>━━━━━━━━<br/>ID编码/解码<br/>递归处理关联<br/>对外隐藏真实ID"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>JWT_SECRET_KEY 强制配置<br/>缺失/默认值拒绝启动<br/>7天+刷新+黑名单<br/>并发≤3个"]
        C6["poster-php<br/>━━━━━━━━<br/>操作前随机验证<br/>删除/审核/提现<br/>防误操作"]
        C7["snowflake-php<br/>━━━━━━━━<br/>BIGINT分布式ID<br/>非自增防遍历<br/>全局唯一"]
    end

    subgraph 攻击面["防护攻击面"]
        A1["注入攻击<br/>SQL/命令/LDAP"]
        A2["XSS/CSRF<br/>跨站脚本/请求伪造"]
        A3["路径遍历<br/>目录穿越/文件包含"]
        A4["暴力破解<br/>登录爆破/验证码爆破"]
        A5["数据泄露<br/>ID遍历/敏感字段"]
        A6["越权操作<br/>水平/垂直越权"]
        A7["并发滥用<br/>Token泛滥/刷接口"]
    end

    C1 -.->|防御| A1
    C1 -.->|防御| A2
    C1 -.->|防御| A3
    C2 -.->|防御| A5
    C3 -.->|防御| A5
    C4 -.->|防御| A5
    C5 -.->|防御| A4
    C5 -.->|防御| A7
    C6 -.->|防御| A6
    C7 -.->|防御| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. 认证与授权流程

```mermaid
flowchart TD
    A["客户端请求"] --> B{"是否有Token?"}
    B -->|"无"| C["返回 401<br/>提示登录"]
    B -->|"有"| D["解析JWT Token"]
    D --> E{"Token有效?"}
    E -->|"过期"| F{"Refresh Token?"}
    F -->|"是"| G["刷新Token<br/>旧Token加入黑名单"]
    F -->|"否"| C
    G --> H["返回新Token"]
    E -->|"有效"| I{"黑名单检查"}
    I -->|"已拉黑"| C
    I -->|"正常"| J["查询用户信息"]
    J --> K{"用户存在且启用?"}
    K -->|"否"| L["返回 403<br/>账号已禁用"]
    K -->|"是"| M{"登录失败次数?"}
    M -->|"≥5次/15min"| N["返回 429<br/>账号已锁定"]
    M -->|"正常"| O{"并发Token数?"}
    O -->|">3个"| P["旧Token自动失效<br/>加入黑名单"]
    O -->|"≤3个"| Q{"需要技师身份?"}
    Q -->|"是"| R{"技师档案approved?"}
    R -->|"否"| S["返回 403<br/>非技师或审核中"]
    R -->|"是"| T{"需要RBAC?"}
    Q -->|"否"| T
    T -->|"是"| U{"权限校验"}
    U -->|"无权限"| V["返回 403<br/>无操作权限"]
    U -->|"有权限"| W["执行业务逻辑"]
    T -->|"否"| W
    W --> X["返回响应<br/>ID已编码<br/>敏感数据已加密"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. 数据安全流转

```mermaid
flowchart LR
    subgraph 输入["用户输入"]
        I1["明文手机号"]
        I2["明文身份证"]
        I3["明文OpenID"]
        I4["明文姓名"]
    end

    subgraph API加密["API层 (encryption)"]
        E1["encrypt(id_card)<br/>→ ciphertext"]
        E2["encrypt(real_name)<br/>→ ciphertext"]
    end

    subgraph DB存储["DB层存储"]
        D1["appointment_user.phone<br/>明文存储<br/>登录/查重依赖明文查询"]
        D2["appointment_technician_profile<br/>.id_card VARCHAR(500)<br/>encryptable 加密"]
        D3["appointment_user.wx_openid<br/>明文存储"]
        D4["appointment_user.real_name<br/>encryptable 加密"]
    end

    subgraph ID处理["ID处理 (hashids + snowflake)"]
        H1["Snowflake生成<br/>1860000000000001"]
        H2["Hashids编码<br/>→ 'Kx9mP2vR'"]
        H3["API响应<br/>id: 'Kx9mP2vR'"]
    end

    subgraph 输出["对外输出"]
        O1["ID已编码<br/>不可遍历"]
        O2["敏感字段已脱敏<br/>日志不含明文"]
        O3["响应头含安全策略<br/>CSP/CORS/HSTS"]
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
