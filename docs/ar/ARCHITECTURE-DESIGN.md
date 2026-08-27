# تصميم البنية
> **Languages**: [中文](../ARCHITECTURE-DESIGN.md) · [English](../en/ARCHITECTURE-DESIGN.md) · [한국어](../ko/ARCHITECTURE-DESIGN.md) · [Русский](../ru/ARCHITECTURE-DESIGN.md) · [Deutsch](../de/ARCHITECTURE-DESIGN.md) · [Français](../fr/ARCHITECTURE-DESIGN.md) · [Español](../es/ARCHITECTURE-DESIGN.md) · [Português](../pt/ARCHITECTURE-DESIGN.md) · [हिन्दी](../hi/ARCHITECTURE-DESIGN.md) · [বাংলা](../bn/ARCHITECTURE-DESIGN.md) · [Bahasa Indonesia](../id/ARCHITECTURE-DESIGN.md) · [日本語](../ja/ARCHITECTURE-DESIGN.md)

## البنية الطبقية

```
┌─────────────────────────────────────────┐
│              表现层 (Presentation)        │
│  微信小程序 / Flutter APP / Flutter Web   │
├─────────────────────────────────────────┤
│              路由层 (Route)               │
│  config/route.php — 路由分组 + 中间件绑定  │
├─────────────────────────────────────────┤
│            中间件层 (Middleware)           │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│            控制器层 (Controller)           │
│  BaseController → 各业务Controller        │
├─────────────────────────────────────────┤
│             服务层 (Service)              │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│             模型层 (Model)                │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│              数据层 (Data)                │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## تصميم الوسائط

### سلسلة التنفيذ

```
Cors → Security(31种攻击检测) → RateLimit → Auth(JWT+用户状态)
    → [TechnicianAuth(技师身份)] → [AdminPermission(RBAC)] → [OperationLog(8端来源)]
    → Controller
```

### مهام الوسائط

| الوسيط | النطاق | الوظيفة |
|--------|--------|------|
| Cors | عام | فحص مسبق OPTIONS + ترويسات استجابة CORS |
| Security | عام | erikwang2013/security-php، اكتشاف 31 نوع هجوم |
| RateLimit | عام | نافذة منزلقة Redis + Lua ذرّي |
| Auth | مجموعة مسارات | تحليل JWT + التحقق من وجود/حالة المستخدم |
| TechnicianAuth | مجموعة مسارات | الاستعلام عن ملف الفني + التحقق من حالة approved |
| AdminAuth | مجموعة مسارات | مصادقة JWT للوحة الإدارة + القائمة السوداء |
| AdminPermission | مجموعة مسارات | التحقق من صلاحيات RBAC، تخزين Redis 60 ثانية |
| OperationLog | مجموعة مسارات | سجلات العمليات + اكتشاف تلقائي لـ 8 مصادر |

### إستراتيجية تحديد المعدل

| الواجهة | الحد |
|------|------|
| الافتراضي | 60 مرة/دقيقة/IP |
| تسجيل الدخول | 10 مرات/دقيقة |
| التسجيل | 5 مرات/دقيقة |
| رمز التحقق | مرة/60 ثانية/رقم الهاتف |

## مبادئ تصميم قاعدة البيانات

### إستراتيجية المفاتيح الأساسية

- جميع المفاتيح الأساسية: BIGINT UNSIGNED NOT NULL، غير تلقائية
- مولدة على مستوى التطبيق من `erikwang2013/snowflake-php`
- Model: `$incrementing = false`, `$keyType = 'string'`

### بادئة الجداول

بادئة موحدة `appointment_`، تُكوَّن في `config/database.php`. يكتب Model اسم الجدول الأصلي ويضيف ORM البادئة تلقائيًا.

### تشفير الحقول الحساسة

باستخدام trait `erikwang2013/encryptable`:

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

طول الحقول المشفرة VARCHAR مضبوط على 500 (تضخم البيانات المشفرة).

### الحذف الناعم والطوابع الزمنية

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- جميع الجداول تحتوي على `created_at` + `updated_at`

## آلية تشفير/فك تشفير معرّفات API

### الطلب: decodeIds()

يرسل الطرف الأمامي معرّفات مشفرة hashids ← يستدعي المتحكم `$this->decodeIds($request->all())` لفك التشفير.

### الاستجابة: encodeIds()

معرّفات نتائج استعلام قاعدة البيانات ← `BaseController::success()` يستدعي تلقائيًا `encodeIds()` للترميز ← يعيد سلاسل hashids.

### القواعد

معالجة تكرارية للحقول التي اسم مفتاحها `id` أو تنتهي بـ `_id` في المصفوفة.

## التصميم الأمني

### الدفاع العميق

```
WAF → Cors → Security(31种检测) → RateLimit → Auth(JWT+状态)
    → [身份校验] → [RBAC] → Controller(Model加密) → 响应
```

### أمان المصادقة

- كلمات المرور: bcrypt hash
- JWT: صلاحية 7 أيام + تحديث + قائمة سوداء
- القفل: 5 إخفاقات ← 15 دقيقة
- التزامن: 3 رموز كحد أقصى

### أمان البيانات

- طبقة API: erikwang2013/encryption
- طبقة قاعدة البيانات: trait erikwang2013/encryptable
- السجلات: البيانات الحساسة لا تدخل السجلات

### أمان العمليات

- erikwang2013/poster-php: تحقق قبل الحذف/المراجعة/السحب
- وسيط Security: اكتشاف XSS/حقن SQL/CSRF/اجتياز المسار

## تكامل Elasticsearch

`erikwang2013/webman-scout` يزامن النماذج إلى ES تلقائيًا:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'appointment_services'; }
}
```

## تصدير Excel/PDF

- Excel: PhpSpreadsheet، إخفاء تلقائي للحقول الحساسة
- PDF: تصدير مرئي للوحات Dashboard

## اكتشاف مصادر 8 واجهات

يحلل OperationLog عبر User-Agent:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / 其他 → web
```

## اختبارات TDD

| المشروع | عدد الاختبارات | الحالة |
|------|--------|------|
| admin/ | 60 | ✅ ناجح |
| service/ | 21 | ✅ ناجح |
| الإجمالي | 81 | ✅ |

تغطية الاختبارات: قواعد الاسترداد / حالات الطلب / Hashids / نظام الطابور / التشفير / رمز التحقق
