# مخطط البنية الأمنية
> **Languages**: [中文](../../diagrams/SECURITY-ARCHITECTURE.md) · [English](../../en/diagrams/SECURITY-ARCHITECTURE.md) · [한국어](../../ko/diagrams/SECURITY-ARCHITECTURE.md) · [Русский](../../ru/diagrams/SECURITY-ARCHITECTURE.md) · [Deutsch](../../de/diagrams/SECURITY-ARCHITECTURE.md) · [Français](../../fr/diagrams/SECURITY-ARCHITECTURE.md) · [Español](../../es/diagrams/SECURITY-ARCHITECTURE.md) · [Português](../../pt/diagrams/SECURITY-ARCHITECTURE.md) · [हिन्दी](../../hi/diagrams/SECURITY-ARCHITECTURE.md) · [বাংলা](../../bn/diagrams/SECURITY-ARCHITECTURE.md) · [Bahasa Indonesia](../../id/diagrams/SECURITY-ARCHITECTURE.md) · [日本語](../../ja/diagrams/SECURITY-ARCHITECTURE.md)

## 1. نظام الدفاع متعدد الطبقات

```mermaid
graph TB
    subgraph 边界防护["الطبقة الأولى: حماية الحدود"]
        WAF["WAF / Nginx<br/>ترويسات استجابة أمنية<br/>حماية الملفات الحساسة<br/>TLS 1.3"]
    end

    subgraph 接入防护["الطبقة الثانية: حماية الوصول"]
        CORS["وسيط Cors<br/>قائمة بيضاء CORS_ALLOW_ORIGIN<br/>إعادة عرض * · عدم الإعداد يعني نفس الأصل فقط<br/>6 ترويسات استجابة أمنية<br/>فحص مسبق OPTIONS"]
    end

    subgraph 攻击检测["الطبقة الثالثة: كشف الهجمات"]
        SEC["وسيط Security<br/>erikwang2013/security-php<br/>31 كاشف هجوم<br/>XSS / حقن SQL / CSRF<br/>اجتياز المسار / تضمين الملفات<br/>كشف أصل CSRF (block)"]
        BLOCK["حظر تلقائي<br/>5 هجمات / 60 ثانية<br/>← قائمة IP السوداء 15 دقيقة"]
    end

    subgraph 流量控制["الطبقة الرابعة: التحكم في الحركة"]
        RL["وسيط RateLimit<br/>نافذة منزلقة Redis + Lua ذرية<br/>الافتراضي: 60 مرة/دقيقة/IP<br/>تسجيل الدخول: 10 مرات/دقيقة<br/>التسجيل: 5 مرات/دقيقة<br/>رمز التحقق: مرة/60 ثانية/رقم هاتف"]
    end

    subgraph 身份认证["الطبقة الخامسة: مصادقة الهوية"]
        AUTH["وسيط Auth<br/>JWT Bearer Token (7 أيام)<br/>إلزامي الإعداد JWT_SECRET_KEY<br/>رفض الإقلاع عند غيابه/قيمة افتراضية عامة<br/>كلمات مرور bcrypt<br/>تحديث Token + قائمة سوداء<br/>قفل تسجيل الدخول: 5 فشلات ← 15 دقيقة<br/>حد التزامن: 3 Tokens كحد أقصى"]
        TECH_AUTH["TechnicianAuth<br/>التحقق من ملف الفني<br/>فحص حالة approved"]
        ADMIN_AUTH["AdminAuth<br/>مصادقة JWT لطرف الإدارة<br/>قائمة Token السوداء"]
    end

    subgraph 权限控制["الطبقة السادسة: التحكم في الصلاحيات"]
        RBAC["AdminPermission<br/>التحقق من صلاحيات الأدوار RBAC<br/>تخزين مؤقت Redis 60 ثانية<br/>مستخدم ← دور ← صلاحية"]
        POSTER["تحقق Poster<br/>erikwang2013/poster-php<br/>حذف/مراجعة/سحب<br/>تحقق عشوائي للعمليات الحساسة"]
    end

    subgraph 数据安全["الطبقة السابعة: أمان البيانات"]
        ENC_API["تشفير طبقة API<br/>erikwang2013/encryption<br/>تشفير/فك الحقول الحساسة"]
        ENC_DB["تشفير طبقة DB<br/>erikwang2013/encryptable<br/>تشفير/فك تلقائي عبر trait النموذج<br/>تشفير real_name/id_card إلخ فقط<br/>phone/wx_openid يجب تخزينهما نصًا صريحًا<br/>(تسجيل الدخول/فحص التكرار يعتمدان على استعلام نص صريح)"]
        HASHID["تشفير/فك المعرفات<br/>erikwang2013/hashids<br/>إخفاء المعرف الحقيقي خارجيًا<br/>تشفير/فك تكراري"]
        SLOG["سجل أمني<br/>إخفاء موحد للشواذ M3<br/>نص عام + Log::error<br/>البيانات الحساسة لا تدخل السجل<br/>OperationLog مصادر 8 أطراف"]
    end

    subgraph 管理端防护["الطبقة الثامنة: حماية طرف الإدارة"]
        EXCEL["حماية التصدير<br/>safeCellValue()<br/>بدء بـ = + - @ / Tab/CR<br/>بادئة ' للهروب من حقن الصيغ"]
        UPLOAD["تحقق الرفع<br/>finfo magic bytes<br/>عدم تطابق MIME مع الامتداد<br/>← رفض 422"]
        INSTALL["قفل التثبيت<br/>مثبّت بالفعل (installed=1<br/>أو وجود مدير)<br/>← 404 تعطيل معالج التثبيت"]
    end

    请求["HTTP Request"] --> WAF
    WAF --> CORS
    CORS --> SEC
    SEC -->|"اجتياز"| RL
    SEC -->|"رصد هجوم"| BLOCK
    BLOCK -.->|"رفض"| 拒绝["HTTP 403/429<br/>تسجيل سجل الهجوم"]
    RL -->|"اجتياز"| AUTH
    RL -->|"تجاوز الحد"| 限流拒绝["HTTP 429<br/>Retry-After"]
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
    INSTALL --> 响应["HTTP Response<br/>البيانات مشفرة + مرمزة"]

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

## 2. مصفوفة المكونات الأمنية

```mermaid
graph LR
    subgraph 组件["المكونات الأمنية"]
        C1["security-php<br/>━━━━━━━━<br/>31 نوع كشف هجوم<br/>XSS/حقن SQL/CSRF<br/>اجتياز المسار/تضمين الملفات<br/>كشف أصل CSRF"]
        C2["encryption<br/>━━━━━━━━<br/>AES-256-CBC<br/>تشفير/فك طبقة API<br/>دعم تدوير المفاتيح"]
        C3["encryptable<br/>━━━━━━━━<br/>تشفير/فك تلقائي لحقول DB<br/>تشفير real_name/id_card إلخ فقط<br/>phone/wx_openid نص صريح<br/>توافق تمدد التشفير VARCHAR(500)"]
        C4["hashids<br/>━━━━━━━━<br/>تشفير/فك المعرفات<br/>معالجة تكراري للارتباطات<br/>إخفاء المعرف الحقيقي خارجيًا"]
        C5["jwt-webman<br/>━━━━━━━━<br/>Bearer Token<br/>إلزامي الإعداد JWT_SECRET_KEY<br/>رفض الإقلاع عند غيابه/القيمة الافتراضية<br/>7 أيام + تحديث + قائمة سوداء<br/>التزامن ≤3"]
        C6["poster-php<br/>━━━━━━━━<br/>تحقق عشوائي قبل العملية<br/>حذف/مراجعة/سحب<br/>منع الأخطاء التشغيلية"]
        C7["snowflake-php<br/>━━━━━━━━<br/>معرف موزع BIGINT<br/>غير تلقائي الزيادة يمنع التصفح<br/>فريد عالميًا"]
    end

    subgraph 攻击面["أسطح الهجوم المحمية"]
        A1["هجمات الحقن<br/>SQL/أوامر/LDAP"]
        A2["XSS/CSRF<br/>سكربتات متقاطعة/تزوير الطلبات"]
        A3["اجتياز المسار<br/>عبور الدلائل/تضمين الملفات"]
        A4["هجوم التخمين<br/>تخمين تسجيل الدخول/تخمين رموز التحقق"]
        A5["تسرب البيانات<br/>تصفح المعرفات/الحقول الحساسة"]
        A6["تجاوز الصلاحيات<br/>أفقي/عمودي"]
        A7["إساءة التزامن<br/>تكاثر Tokens/إغراق الواجهات"]
    end

    C1 -.->|دفاع| A1
    C1 -.->|دفاع| A2
    C1 -.->|دفاع| A3
    C2 -.->|دفاع| A5
    C3 -.->|دفاع| A5
    C4 -.->|دفاع| A5
    C5 -.->|دفاع| A4
    C5 -.->|دفاع| A7
    C6 -.->|دفاع| A6
    C7 -.->|دفاع| A5

    classDef comp fill:#e8eaf6,stroke:#3949ab,stroke-width:2px,color:#1a237e
    classDef attack fill:#ffebee,stroke:#c62828,stroke-width:1px,color:#b71c1c

    class C1,C2,C3,C4,C5,C6,C7 comp
    class A1,A2,A3,A4,A5,A6,A7 attack
```

## 3. تدفق المصادقة والتفويض

```mermaid
flowchart TD
    A["طلب العميل"] --> B{"هل يوجد Token؟"}
    B -->|"لا"| C["إرجاع 401<br/>مطالبة بتسجيل الدخول"]
    B -->|"نعم"| D["تحليل JWT Token"]
    D --> E{"Token صالح؟"}
    E -->|"منتهٍ"| F{"Refresh Token؟"}
    F -->|"نعم"| G["تحديث Token<br/>إضافة القديم إلى القائمة السوداء"]
    F -->|"لا"| C
    G --> H["إرجاع Token جديد"]
    E -->|"صالح"| I{"فحص القائمة السوداء"}
    I -->|"محظور"| C
    I -->|"طبيعي"| J["استعلام معلومات المستخدم"]
    J --> K{"المستخدم موجود ومفعّل؟"}
    K -->|"لا"| L["إرجاع 403<br/>الحساب معطل"]
    K -->|"نعم"| M{"عدد مرات فشل تسجيل الدخول؟"}
    M -->|"≥5 مرات/15 دقيقة"| N["إرجاع 429<br/>الحساب مقفل"]
    M -->|"طبيعي"| O{"عدد Tokens المتزامنة؟"}
    O -->|">3"| P["انتهاء تلقائي للقديم<br/>إضافة إلى القائمة السوداء"]
    O -->|"≤3"| Q{"هل يتطلب هوية فني؟"}
    Q -->|"نعم"| R{"ملف الفني approved؟"}
    R -->|"لا"| S["إرجاع 403<br/>ليس فنيًا أو قيد المراجعة"]
    R -->|"نعم"| T{"هل يتطلب RBAC؟"}
    Q -->|"لا"| T
    T -->|"نعم"| U{"التحقق من الصلاحية"}
    U -->|"بلا صلاحية"| V["إرجاع 403<br/>لا صلاحية للعملية"]
    U -->|"بصلاحية"| W["تنفيذ منطق الأعمال"]
    T -->|"لا"| W
    W --> X["إرجاع الاستجابة<br/>المعرفات مرمزة<br/>البيانات الحساسة مشفرة"]

    style C fill:#ffcdd2,stroke:#c62828,color:#333
    style L fill:#ffcdd2,stroke:#c62828,color:#333
    style N fill:#ffcdd2,stroke:#c62828,color:#333
    style S fill:#ffcdd2,stroke:#c62828,color:#333
    style V fill:#ffcdd2,stroke:#c62828,color:#333
    style W fill:#c8e6c9,stroke:#2e7d32,color:#333
    style X fill:#c8e6c9,stroke:#2e7d32,color:#333
```

## 4. تدفق أمان البيانات

```mermaid
flowchart LR
    subgraph 输入["إدخال المستخدم"]
        I1["رقم هاتف نص صريح"]
        I2["رقم هوية نص صريح"]
        I3["OpenID نص صريح"]
        I4["اسم نص صريح"]
    end

    subgraph API加密["طبقة API (encryption)"]
        E1["encrypt(id_card)<br/>← ciphertext"]
        E2["encrypt(real_name)<br/>← ciphertext"]
    end

    subgraph DB存储["تخزين طبقة DB"]
        D1["appointment_user.phone<br/>تخزين نص صريح<br/>تسجيل الدخول/فحص التكرار يعتمدان على استعلام نص صريح"]
        D2["appointment_technician_profile<br/>.id_card VARCHAR(500)<br/>تشفير عبر encryptable"]
        D3["appointment_user.wx_openid<br/>تخزين نص صريح"]
        D4["appointment_user.real_name<br/>تشفير عبر encryptable"]
    end

    subgraph ID处理["معالجة المعرفات (hashids + snowflake)"]
        H1["توليد Snowflake<br/>1860000000000001"]
        H2["تشفير Hashids<br/>← 'Kx9mP2vR'"]
        H3["استجابة API<br/>id: 'Kx9mP2vR'"]
    end

    subgraph 输出["الإخراج الخارجي"]
        O1["المعرفات مرمزة<br/>غير قابلة للتصفح"]
        O2["الحقول الحساسة مخفية<br/>السجل لا يتضمن نصًا صريحًا"]
        O3["الترويسات تتضمن سياسات أمنية<br/>CSP/CORS/HSTS"]
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
