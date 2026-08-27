# সিকিউরিটি অডিট রিপোর্ট — অ্যাপয়েন্টমেন্ট সিস্টেম (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [한국어](../ko/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Deutsch](../de/SECURITY-AUDIT-REPORT.md) · [Français](../fr/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [Português](../pt/SECURITY-AUDIT-REPORT.md) · [हिन्दी](../hi/SECURITY-AUDIT-REPORT.md) · [العربية](../ar/SECURITY-AUDIT-REPORT.md) · [Bahasa Indonesia](../id/SECURITY-AUDIT-REPORT.md) · [日本語](../ja/SECURITY-AUDIT-REPORT.md)

**তারিখ**: ২০২৬-০৮-০৪
**অডিট সুযোগ**: service (অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম)、admin (ওপেন ম্যানেজমেন্ট ব্যাকএন্ড)
**PHP ভার্সন**: 8.3.7
**ফ্রেমওয়ার্ক**: webman v2

---

## 一、টেস্ট ফলাফল

| টেস্ট আইটেম | Service | Admin |
|--------|---------|-------|
| PHP সিনট্যাক্স চেক (ফুল) | পাস | পাস |
| PHPUnit ইউনিট টেস্ট | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| PHPStan স্ট্যাটিক অ্যানালাইসিস | ইনস্টল করা হয়নি (dev ডিপেন্ডেন্সি ডাউনলোড টাইমআউট) | ইনস্টল করা হয়নি (dev ডিপেন্ডেন্সি ডাউনলোড টাইমআউট) |

---

## 二、সিকিউরিটি প্রোটেকশন লেয়ার ওভারভিউ

```
请求 → Nginx (安全头+敏感文件保护) → Cors (CORS+安全头) → SecurityMiddleware (31种攻击检测) → RateLimit (Redis滑动窗口) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    IP黑名单 (5次攻击/60s → 封禁15min)
                                                                                    账号锁定 (5次失败/15min → 锁定15min)
```

---

## 三、ফিক্স করা সমস্যা

### 3.1 Service CORS-এ সিকিউরিটি রেসপন্স হেডার নেই → ফিক্স করা হয়েছে
**ফাইল**: `service/app/middleware/Cors.php`
- ৬টি সিকিউরিটি হেডার যোগ: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- এখন admin সিকিউরিটি হেডার কনফিগের সাথে সামঞ্জস্যপূর্ণ

### 3.2 Service-এ লগইন ফেইল লক নেই → ফিক্স করা হয়েছে
**ফাইল**: `service/app/api/v1/controller/AuthController.php`
- `login()` ও `loginByCode()` মেথডে Redis ফেইল কাউন্টিং যোগ
- ৫ বার ব্যর্থ/১৫ মিনিট লক → HTTP 429
- Redis ব্যর্থ হলে গ্রেসফুল ডিগ্রেড

### 3.3 CORS Origin হার্ডকোড `*` → ফিক্স করা হয়েছে
**ফাইল**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- `CORS_ALLOW_ORIGIN` এনভায়রনমেন্ট ভেরিয়েবল দিয়ে কনফিগ করা হয়েছে
- খালি রাখলে ডিফল্ট `*` (ব্যাকওয়ার্ড কম্প্যাটিবিলিটি)

### 3.4 Service-এ security-php ডিপেন্ডেন্সি নেই → ফিক্স করা হয়েছে
**অপারেশন**:
- composer.json-এ `allow-plugins.erikwang2013/security-php` যোগ
- `composer install --no-dev` চালিয়ে ডিপেন্ডেন্সি ইনস্টল
- কনফিগ ফাইল পাবলিশ করা হয়েছে `config/plugin/erikwang2013/security-php/app.php`
- CSRF Origin ডিটেক্টর (`csrf_origin`) সক্ষম করা হয়েছে (block মোড)

### 3.5 Service Nginx-এ Permissions-Policy নেই → ফিক্স করা হয়েছে
**ফাইল**: `service/docs/nginx.conf`
- `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;` যোগ

### 3.6 ইকোসিস্টেম কনফিগ পূরণ → ফিক্স করা হয়েছে
- `service/.env.example` ও `admin/.env.example`-এ `CORS_ALLOW_ORIGIN` যোগ
- `service/.env.docker` ও `admin/.env.docker`-এ `CORS_ALLOW_ORIGIN` যোগ

---

## 四、বর্তমান সিকিউরিটি প্রোটেকশন সম্পূর্ণ তালিকা

### 4.1 WAF লেয়ার — ৩১ ধরনের অ্যাটাক ডিটেক্টর

| মোড | ডিটেক্টর | সংখ্যা |
|------|--------|------|
| **block** (ইন্টারসেপ্ট 403) | XSS, SQL ইনজেকশন, কমান্ড ইনজেকশন, পাথ ট্রাভার্সাল, ফাইল আপলোড, SSRF, XXE, ডিসিরিয়ালাইজেশন, LDAP ইনজেকশন, ইমেইল হেডার ইনজেকশন, Open Redirect, JWT অ্যাটাক, Host হেডার অ্যাটাক, Request Smuggling, GraphQL ইনজেকশন, XPATH ইনজেকশন, JNDI/Log4Shell, SSI ইনজেকশন, CSV ইনজেকশন, ডেটা লিক, Prototype Pollution, WebSocket হাইজ্যাকিং, CORS বাইপাস, DNS Rebinding, HTTP মেথড ভ্যালিডেশন, রিকোয়েস্ট বডি সাইজ (10MB), Content-Type হোয়াইটলিস্ট, CSRF Origin | 28 |
| **log** (শুধু রেকর্ড) | রেসপন্স হেডার ইনজেকশন, SSTI, NoSQL ইনজেকশন | 3 |

### 4.2 অথেনটিকেশন ও অথরাইজেশন

| মেকানিজম | Service | Admin |
|------|---------|-------|
| JWT অথেনটিকেশন | Auth মিডলওয়্যার | AdminAuth মিডলওয়্যার |
| JWT ব্ল্যাকলিস্ট | লগআউটে যোগ | লগআউট + সেশন লিমিট ওভারে যোগ |
| RBAC পারমিশন | — | method.path ফরম্যাট, Redis 60s ক্যাশ |
| অ্যাকাউন্ট লক | 5 বার/15 মিনিট (Redis) | 5 বার/15 মিনিট (Redis) |
| কনকারেন্ট সেশন সীমা | — | সর্বোচ্চ ৩টি Token |
| পাসওয়ার্ড হ্যাশ | bcrypt | bcrypt |

### 4.3 রেট লিমিট

| রাউট | Service | Admin |
|------|---------|-------|
| ডিফল্ট | 60 বার/মিনিট/IP | 60 বার/মিনিট/IP |
| লগইন | 10 বার/মিনিট | — |
| রেজিস্ট্রেশন | 5 বার/মিনিট | — |
| SMS/পাসওয়ার্ড ভুলে গেলে | 5 বার/মিনিট | — |

### 4.4 ডেটা সিকিউরিটি

| ব্যবস্থা | Service | Admin |
|------|---------|-------|
| ডেটাবেস ফিল্ড এনক্রিপশন | AES-256-CBC (৬টি মডেল) | AES-256-CBC |
| API ট্রান্সমিশন এনক্রিপশন | AES-256-CBC | AES-256-CBC |
| ID অবফাসকেশন (Hashids) | সব বহিরাগত ID | সব বহিরাগত ID |
| Snowflake ID | নন-অটোইনক্রিমেন্ট BIGINT | নন-অটোইনক্রিমেন্ট BIGINT |
| সেনসিটিভ ফিল্ড মাস্কিং | ফোন নম্বর মাস্ক | এক্সপোর্ট ডেটা মাস্ক |

---

## 五、পেন্ডিং প্রস্তাব

### 5.1 প্রস্তাব: security-php স্টোরেজ Redis-এ পরিবর্তন (প্রোডাকশন)
**বর্তমান**: দুটি সার্ভিসই `file` টাইপ স্টোরেজ (লোকাল JSON ফাইল)
**রিস্ক**: মাল্টি-ইনস্ট্যান্স ডিপ্লয়মেন্টে IP ব্ল্যাকলিস্ট শেয়ার হয় না, অ্যাটাকার ইনস্ট্যান্স স্যুইচ করে বাইপাস করতে পারে
**প্রস্তাব**: প্রোডাকশনে `storage.type`-এ `redis` সেট করুন

### 5.2 প্রস্তাব: Session Cookie সিকিউরিটি অ্যাট্রিবিউট
**বর্তমান**: `secure: false`, `same_site: ''`
**রিস্ক**: Cookie HTTP দিয়ে ট্রান্সমিট হতে পারে, CSRF প্রোটেকশন দুর্বল
**প্রস্তাব**: প্রোডাকশনে `secure: true`, `same_site: 'Lax'` সেট করুন

### 5.3 প্রস্তাব: PHPStan dev ডিপেন্ডেন্সি ইনস্টল
**বর্তমান**: `composer install --dev` নেটওয়ার্ক টাইমআউটে ব্যর্থ
**অপারেশন**: `composer install --dev` বা `composer require --dev phpstan/phpstan`

### 5.4 সতর্কতা: প্রোডাকশন ডিপ্লয়মেন্টের আগে সব সিক্রেট কী পরিবর্তন করুন
`.env.docker`-এর প্লেসহোল্ডার সিক্রেট কী প্রোডাকশন ডিপ্লয়মেন্টের আগে র্যান্ডম জেনারেটেড মান দিয়ে বদলাতে হবে:
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## 六、ডকুমেন্টেশন আউটপুট

| ডকুমেন্ট | পাথ |
|------|------|
| Service সিকিউরিটি আর্কিটেকচার | `service/docs/SECURITY.md` |
| Admin সিকিউরিটি আর্কিটেকচার | `admin/docs/SECURITY.md` |
| এই অডিট রিপোর্ট | `docs/SECURITY-AUDIT-REPORT.md` |

---

## 七、অডিট উপসংহার

**সিকিউরিটি প্রোটেকশন সার্বিক রেটিং: ভালো**

- ডিফেন্স-ইন-ডেপথ লেয়ার সম্পূর্ণ (Nginx → WAF → Rate Limit → Auth → RBAC)
- ৩১ ধরনের অ্যাটাক ডিটেক্টর গ্লোবাল কভারেজ, ২৮টি ইন্টারসেপ্ট মোড
- JWT + ব্ল্যাকলিস্ট + অ্যাকাউন্ট লক + IP ব্ল্যাকলিস্ট মাল্টি-লেয়ার অথেনটিকেশন প্রোটেকশন
- ডেটা লেয়ার AES-256-CBC এনক্রিপশন + Hashids অবফাসকেশন
- service এন্ডের সিকিউরিটি রেসপন্স হেডার নেই, লগইন লক নেই, WAF প্যাকেজ নেই — এই ৩টি মূল সমস্যা ফিক্স করা হয়েছে
- প্রস্তাবগুলো প্রোডাকশন এনভায়রনমেন্ট কনফিগ অপটিমাইজেশন, সিকিউরিটি ভুল নয়

---

## 八、২০২৬-০৮-২৬ ফিক্স রাউন্ড (সিকিউরিটি হার্ডেনিং)

| আইটেম | ফিক্স বিবরণ |
|----|---------|
| অর্ডার টেম্পারিং রোধ | OrderController::store() অর্ডার আইটেমের দাম সবসময় ডেটাবেস রেকর্ড অনুযায়ী (service→appointment_service、product→appointment_product), ক্লায়েন্টের দাম হিসাবে ধরা হয় না; অজানা target_type ৪২২; target_id অবশ্যই hashid (raw id ডিকোড 0 → ৪২২「商品不存在或已下架」); গ্রুপ বাই/সেকিল দামও DB অনুযায়ী |
| সেকিল স্টক কাটা ইউনিফাইড | স্টক ইউনিফাইডভাবে /api/order store() ট্রানজেকশনে রো লক দিয়ে কাটা; SeckillController::buy আর আগে থেকে স্টক কাটে না (Redis অ্যাক্টিভিটি লক + client_token আইডেমপোটেন্সি ধরে রাখে); সরাসরি /api/order এ seckill_id দিলেও স্টক কাটে |
| টেকনিশিয়ান উত্তোলন | আবেদনের সময় ব্যালেন্স থেকে ইন-ফ্লাইট (pending/approved) রিজার্ভ কাটা; অনুমোদন ট্রান্সফারের আগে রি-ভেরিফাই settled−withdrawn−ইন-ফ্লাইট ≥ উত্তোলন পরিমাণ; কনকারেন্ট অনুমোদনে ডাবল পেমেন্ট হয় না |
| পেমেন্ট কলব্যাক | উইচ্যাট কলব্যাক total_fee অর্ডারের পরিশোধযোগ্য পরিমাণের সাথে কঠোর তুলনা, মিল না হলে রিজেক্ট; Alipay কলব্যাক লগ মাস্কিং (buyer_id/seller_id ইত্যাদি নেই) |
| /install সুরক্ষা | ইনস্টল সফলে .install.lock লেখা, install ইন্টারফেস ডাবল ভেরিফিকেশন (ফাইল লক + isInstalled); .gitignore-এ .install.lock ইগনোর |
| ডিপেন্ডেন্সি কনভারজেন্স | webman-scout ইউনিফাইড 2.0.5 (service/admin); opensearch-project/opensearch-php ^2.6 যোগ; dompdf/security-php/webman-database সুনির্দিষ্ট ভার্সন লক ("*" ওয়াইল্ডকার্ড বাদ) |
| ইঞ্জিনিয়ারিং | service/app/common/StorageService.php ডিলিট (ডেড কোড); admin/app/common/-এ TechnicianWithdrawalService/WechatPayService যোগ (admin আলাদা ডিপ্লয়মেন্টে service কোডের উপর নির্ভর করে না); দুই অ্যাপের phpstan.neon মেরামত চালানো যায় (php -d memory_limit=2G) |
