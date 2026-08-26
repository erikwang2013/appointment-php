# আর্কিটেকচার ডিজাইন

> বাংলা অনুবাদ · মূল: [中文](../ARCHITECTURE-DESIGN.md)

## লেয়ারড আর্কিটেকচার

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

## মিডলওয়্যার ডিজাইন

### এক্সিকিউশন চেইন

```
Cors → Security(31种攻击检测) → RateLimit → Auth(JWT+用户状态)
    → [TechnicianAuth(技师身份)] → [AdminPermission(RBAC)] → [OperationLog(8端来源)]
    → Controller
```

### মিডলওয়্যার দায়িত্ব

| মিডলওয়্যার | সুযোগ | ফাংশন |
|--------|--------|------|
| Cors | গ্লোবাল | OPTIONS প্রি-ফ্লাইট + CORS রেসপন্স হেডার |
| Security | গ্লোবাল | erikwang2013/security-php, ৩১ ধরনের আক্রমণ ডিটেকশন |
| RateLimit | গ্লোবাল | Redis স্লাইডিং উইন্ডো + Lua অ্যাটমিক |
| Auth | রাউট গ্রুপ | JWT পার্স + ব্যবহারকারী অস্তিত্ব/স্ট্যাটাস যাচাই |
| TechnicianAuth | রাউট গ্রুপ | টেকনিশিয়ান প্রোফাইল কোয়েরি + approved স্ট্যাটাস যাচাই |
| AdminAuth | রাউট গ্রুপ | অ্যাডমিন JWT অথেনটিকেশন + ব্ল্যাকলিস্ট |
| AdminPermission | রাউট গ্রুপ | RBAC পারমিশন যাচাই, Redis ৬০ সেকেন্ড ক্যাশ |
| OperationLog | রাউট গ্রুপ | অপারেশন লগ + ৮ প্রান্ত উৎস অটো ডিটেকশন |

### রেট লিমিট নীতি

| API | সীমা |
|------|------|
| ডিফল্ট | ৬০ বার/মিনিট/IP |
| লগইন | ১০ বার/মিনিট |
| রেজিস্ট্রেশন | ৫ বার/মিনিট |
| ভেরিফিকেশন কোড | ১ বার/৬০ সেকেন্ড/ফোন |

## ডেটাবেস ডিজাইন নীতি

### প্রাইমারি কী নীতি

- সব প্রাইমারি কী: BIGINT UNSIGNED NOT NULL, নন-অটোইনক্রিমেন্ট
- `erikwang2013/snowflake-php` দিয়ে অ্যাপ্লিকেশন লেয়ারে জেনারেট
- Model: `$incrementing = false`, `$keyType = 'string'`

### টেবিল প্রিফিক্স

ইউনিফাইড `erik_` প্রিফিক্স, `config/database.php` এ কনফিগ। Model আসল টেবিল নাম লেখে, ORM অটো প্রিফিক্স যোগ করে।

### সংবেদনশীল ফিল্ড এনক্রিপশন

`erikwang2013/encryptable` trait ব্যবহার:

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

এনক্রিপ্টেড ফিল্ড VARCHAR দৈর্ঘ্য ৫০০ সেট করা (এনক্রিপ্টেড ডেটা বিস্তৃত হয়)।

### সফট ডিলিট ও টাইমস্ট্যাম্প

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- সব টেবিলে `created_at` + `updated_at`

## API ID এনক্রিপ্ট/ডিক্রিপ্ট মেকানিজম

### রিকোয়েস্ট: decodeIds()

ফ্রন্টএন্ড hashids এনকোডেড ID পাঠায় → কন্ট্রোলার `$this->decodeIds($request->all())` ডিকোড করে।

### রেসপন্স: encodeIds()

DB কোয়েরি ফলাফলের ID → `BaseController::success()` অটো `encodeIds()` কল করে এনকোড → hashids স্ট্রিং রিটার্ন।

### নিয়ম

অ্যারের ভেতরে `id` নামের কী বা `_id` দিয়ে শেষ হওয়া ফিল্ড রিকার্সিভ প্রসেস।

## সিকিউরিটি ডিজাইন

### গভীর প্রতিরক্ষা

```
WAF → Cors → Security(31种检测) → RateLimit → Auth(JWT+状态)
    → [身份校验] → [RBAC] → Controller(Model加密) → 响应
```

### অথেনটিকেশন সিকিউরিটি

- পাসওয়ার্ড: bcrypt হ্যাশ
- JWT: ৭ দিন বৈধতা + রিফ্রেশ + ব্ল্যাকলিস্ট
- লক: ৫ বার ব্যর্থতা → ১৫ মিনিট
- কনকারেন্সি: সর্বোচ্চ ৩টি Token

### ডেটা সিকিউরিটি

- API স্তর: erikwang2013/encryption
- DB স্তর: erikwang2013/encryptable trait
- লগ: সংবেদনশীল ডেটা লগে নেই

### অপারেশন সিকিউরিটি

- erikwang2013/poster-php: ডিলিট/অডিট/উত্তোলনের আগে ভেরিফিকেশন
- Security মিডলওয়্যার: XSS/SQL ইনজেকশন/CSRF/পাথ ট্রাভার্সাল ডিটেকশন

## Elasticsearch ইন্টিগ্রেশন

`erikwang2013/webman-scout` অটো মডেল ES-এ সিঙ্ক:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Excel/PDF এক্সপোর্ট

- Excel: PhpSpreadsheet, সংবেদনশীল ফিল্ড অটো মাস্কিং
- PDF: Dashboard প্যানেল ভিজুয়ালাইজড এক্সপোর্ট

## ৮ প্রান্ত উৎস ডিটেকশন

OperationLog User-Agent পার্স করে:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / 其他 → web
```

## TDD টেস্ট

| প্রজেক্ট | টেস্ট সংখ্যা | স্ট্যাটাস |
|------|--------|------|
| admin/ | ৬০ | ✅ পাস |
| service/ | ২১ | ✅ পাস |
| মোট | ৮১ | ✅ |

টেস্ট কভারেজ: রিফান্ড রুল / অর্ডার স্ট্যাটাস / Hashids / কিউ সিস্টেম / এনক্রিপশন / ভেরিফিকেশন কোড
