# تقرير المراجعة الأمنية — نظام الحجز (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md) · [English](../en/SECURITY-AUDIT-REPORT.md) · [한국어](../ko/SECURITY-AUDIT-REPORT.md) · [Русский](../ru/SECURITY-AUDIT-REPORT.md) · [Deutsch](../de/SECURITY-AUDIT-REPORT.md) · [Français](../fr/SECURITY-AUDIT-REPORT.md) · [Español](../es/SECURITY-AUDIT-REPORT.md) · [Português](../pt/SECURITY-AUDIT-REPORT.md) · [हिन्दी](../hi/SECURITY-AUDIT-REPORT.md) · [বাংলা](../bn/SECURITY-AUDIT-REPORT.md) · [Bahasa Indonesia](../id/SECURITY-AUDIT-REPORT.md) · [日本語](../ja/SECURITY-AUDIT-REPORT.md)

**التاريخ**: 2026-08-04
**نطاق المراجعة**: service (نظام خدمات الحجز)، admin (لوحة الإدارة المفتوحة)
**إصدار PHP**: 8.3.7
**الإطار**: webman v2

---

## 一、نتائج الاختبار

| بند الاختبار | Service | Admin |
|--------|---------|-------|
| فحص بناء جملة PHP (الكامل) | ناجح | ناجح |
| اختبارات PHPUnit | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| التحليل الساكن PHPStan | غير مثبت (انتهاء مهلة تنزيل تبعيات dev) | غير مثبت (انتهاء مهلة تنزيل تبعيات dev) |

---

## 二、نظرة عامة على طبقات الحماية الأمنية

```
الطلب → Nginx (ترويسات الأمان+حماية الملفات الحساسة) → Cors (CORS+ترويسات الأمان) → SecurityMiddleware (31 كاشف هجوم) → RateLimit (نافذة منزلقة Redis) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    القائمة السوداء IP (5 هجمات/60s → حظر 15min)
                                                                                    قفل الحساب (5 إخفاقات/15min → قفل 15min)
```

---

## 三、المشكلات المُصلحة

### 3.1 CORS في Service تنقصه ترويسات الاستجابة الأمنية → أُصلح
**الملف**: `service/app/middleware/Cors.php`
- إضافة 6 ترويسات أمان: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- الآن متطابقة مع إعدادات الترويسات الأمنية في admin

### 3.2 Service ينقصه قفل إخفاقات تسجيل الدخول → أُصلح
**الملف**: `service/app/api/v1/controller/AuthController.php`
- إضافة عدّاد إخفاقات Redis لدالتي `login()` و `loginByCode()`
- 5 إخفاقات/15 دقيقة قفل → HTTP 429
- انحدار أنيق عند عطل Redis

### 3.3 CORS Origin مشفرة `*` → أُصلح
**الملفات**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- التغيير إلى الإعداد عبر متغير البيئة `CORS_ALLOW_ORIGIN`
- عند تركها فارغة الافتراضي `*` (توافق رجعي)

### 3.4 Service ينقصه اعتماد security-php → أُصلح
**الإجراءات**:
- إضافة `allow-plugins.erikwang2013/security-php` إلى composer.json
- تشغيل `composer install --no-dev` لتثبيت الاعتماد
- نشر ملف الإعدادات إلى `config/plugin/erikwang2013/security-php/app.php`
- تفعيل كاشف أصل CSRF (`csrf_origin`) (وضع block)

### 3.5 Nginx في Service ينقصه Permissions-Policy → أُصلح
**الملف**: `service/docs/nginx.conf`
- إضافة `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;`

### 3.6 استكمال إعدادات النظام البيئي → أُصلح
- `service/.env.example` و `admin/.env.example` إضافة `CORS_ALLOW_ORIGIN`
- `service/.env.docker` و `admin/.env.docker` إضافة `CORS_ALLOW_ORIGIN`

---

## 四、القائمة الكاملة للحماية الأمنية الحالية

### 4.1 طبقة WAF — 31 كاشف هجوم

| الوضع | الكاشف | العدد |
|------|--------|------|
| **block** (اعتراض 403) | XSS, حقن SQL, حقن الأوامر, اجتياز المسار, رفع الملفات, SSRF, XXE, إلغاء التسلسل, حقن LDAP, حقن ترويسة البريد, إعادة التوجيه المفتوحة, هجمات JWT, هجمات ترويسة المضيف, تهريب الطلبات, حقن GraphQL, حقن XPATH, JNDI/Log4Shell, حقن SSI, حقن CSV, تسريب البيانات, تلوث النموذج الأولي, اختطاف WebSocket, تجاوز CORS, إعادة ربط DNS, فحص أساليب HTTP, حجم جسم الطلب (10MB), القائمة البيضاء Content-Type, أصل CSRF | 28 |
| **log** (تسجيل فقط) | حقن ترويسة الاستجابة, SSTI, حقن NoSQL | 3 |

### 4.2 المصادقة والتفويض

| الآلية | Service | Admin |
|------|---------|-------|
| مصادقة JWT | وسيط Auth | وسيط AdminAuth |
| القائمة السوداء JWT | عند تسجيل الخروج | عند تسجيل الخروج + تجاوز حد الجلسات |
| صلاحيات RBAC | — | صيغة method.path، تخزين Redis 60s |
| قفل الحساب | 5 مرات/15 دقيقة (Redis) | 5 مرات/15 دقيقة (Redis) |
| حد الجلسات المتزامنة | — | 3 Tokens كحد أقصى |
| تجزئة كلمات المرور | bcrypt | bcrypt |

### 4.3 تحديد المعدل

| المسار | Service | Admin |
|------|---------|-------|
| الافتراضي | 60 مرة/دقيقة/IP | 60 مرة/دقيقة/IP |
| تسجيل الدخول | 10 مرات/دقيقة | — |
| التسجيل | 5 مرات/دقيقة | — |
| الرسائل/نسيان كلمة المرور | 5 مرات/دقيقة | — |

### 4.4 أمان البيانات

| الإجراء | Service | Admin |
|------|---------|-------|
| تشفير حقول قاعدة البيانات | AES-256-CBC (6 نماذج) | AES-256-CBC |
| تشفير نقل API | AES-256-CBC | AES-256-CBC |
| إخفاء المعرّفات (Hashids) | جميع المعرّفات الخارجية | جميع المعرّفات الخارجية |
| معرّفات Snowflake | BIGINT غير تلقائي | BIGINT غير تلقائي |
| تنقية الحقول الحساسة | تنقية أرقام الهواتف | تنقية بيانات التصدير |

---

## 五、التوصيات المعلقة

### 5.1 توصية: تخزين security-php بالتحويل إلى Redis (بيئة الإنتاج)
**الحالي**: كلا الخدمتين تستخدمان تخزين نوع `file` (ملفات JSON محلية)
**الخطر**: في النشر متعدد النسخ لا تشارك القائمة السوداء IP، ويمكن للمهاجم تجاوزها بالتبديل بين النسخ
**التوصية**: في بيئة الإنتاج غيّر `storage.type` إلى `redis`

### 5.2 توصية: سمات أمان Session Cookie
**الحالي**: `secure: false`, `same_site: ''`
**الخطر**: يمكن نقل Cookie عبر HTTP، وتضعف حماية CSRF
**التوصية**: في بيئة الإنتاج اضبط `secure: true`, `same_site: 'Lax'`

### 5.3 توصية: تثبيت اعتماد PHPStan للتطوير
**الحالي**: `composer install --dev` فشل بسبب انتهاء مهلة الشبكة
**الإجراء**: `composer install --dev` أو `composer require --dev phpstan/phpstan`

### 5.4 تذكير: تعديل جميع المفاتيح قبل النشر للإنتاج
يجب استبدال مفاتيح العناصر النائبة في `.env.docker` بقيم مولدة عشوائيًا قبل النشر للإنتاج:
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## 六、مخرجات التوثيق

| الوثيقة | المسار |
|------|------|
| البنية الأمنية لـ Service | `service/docs/SECURITY.md` |
| البنية الأمنية لـ Admin | `admin/docs/SECURITY.md` |
| تقرير المراجعة هذا | `docs/SECURITY-AUDIT-REPORT.md` |

---

## 七、استنتاج المراجعة

**التقييم الإجمالي للحماية الأمنية: جيد**

- طبقات الدفاع العميق مكتملة (Nginx → WAF → Rate Limit → Auth → RBAC)
- 31 كاشف هجوم بتغطية شاملة، 28 منها بوضع الاعتراض
- حماية مصادقة متعددة الطبقات: JWT + قائمة سوداء + قفل حساب + قائمة سوداء IP
- تشفير AES-256-CBC لطبقة البيانات + إخفاء Hashids
- أُصلحت ثلاث مشكلات رئيسية في طرف service: نقص ترويسات الاستجابة الأمنية، نقص قفل تسجيل الدخول، نقص حزمة WAF
- البنود المقترحة هي تحسينات إعدادات لبيئة الإنتاج، وليست ثغرات أمنية

---

## 八、جولة الإصلاح 2026-08-26 (تقوية الأمان)

| البند | محتوى الإصلاح |
|----|---------|
| منع تلاعب الطلب | أسعار بنود OrderController::store() تُحسب دائمًا من سجلات قاعدة البيانات (service→appointment_service، product→appointment_product)، وأسعار العميل لا تدخل في الحساب؛ target_type غير معروف 422؛ target_id يجب أن يكون hashid (raw id يُفك إلى 0 → 422 «المنتج غير موجود أو غير متاح»)؛ أسعار المجموعة/البيع المفاجئ كذلك حسب DB |
| توحيد خصم مخزون البيع المفاجئ | المخزون يُخصم موحدًا داخل معاملة /api/order store() بقفل صف؛ SeckillController::buy لم يعد يخصم مسبقًا (يُبقي قفل نشاط Redis + قطعية client_token)؛ استدعاء /api/order مباشرة مع seckill_id يخصم المخزون أيضًا |
| سحب الفني | عند التقديم يُخصم الرصيد كاحتياطي عالق (pending/approved)؛ قبل الموافقة على التحويل إعادة فحص settled−withdrawn−العالق ≥ مبلغ السحب؛ الموافقات المتزامنة لن تدفع مرتين |
| استدعاء الدفع | مقارنة صارمة بين total_fee في استدعاء WeChat والمبلغ المستحق للطلب، عدم التطابق رفض؛ تنقية سجلات استدعاء Alipay (دون buyer_id/seller_id إلخ) |
| حماية /install | بعد نجاح التثبيت يُكتب .install.lock، وواجهة install بتحقق مزدوج (قفل ملف + isInstalled)؛ .gitignore يتجاهل .install.lock |
| تقليص الاعتماديات | توحيد webman-scout 2.0.5 (service/admin)؛ إضافة opensearch-project/opensearch-php ^2.6؛ قفل دقيق لإصدارات dompdf/security-php/webman-database (إزالة حرف البدل "*") |
| الهندسة | حذف service/app/common/StorageService.php (كود ميت)؛ إضافة TechnicianWithdrawalService/WechatPayService في admin/app/common/ (نشر admin المستقل لا يعتمد على كود service)؛ إصلاح phpstan.neon للتطبيقين ليكون قابلاً للتشغيل (php -d memory_limit=2G) |
