# نظام خدمات الحجز — دليل التثبيت
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

حقوق الطبع (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## متطلبات البيئة

| المكوّن | الحد الأدنى | الوصف |
|------|----------|------|
| PHP | 8.3+ | الإضافات: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | بادئة الجداول `appointment_`، مجموعة الأحرف utf8mb4 |
| Redis | 6.0+ | التخزين المؤقت / تحديد المعدل / Session / تخزين رموز التحقق |
| Composer | 2.x | إدارة تبعيات PHP |
| Elasticsearch | 8.x (اختياري) | البحث بالنص الكامل، عدم تثبيته لا يؤثر على الوظائف الأساسية |

---

## 一、معالج التثبيت عبر الويب (موصى به)

بعد تشغيل لوحة الإدارة، افتح `/install` في المتصفح للدخول إلى معالج التثبيت بنقرة واحدة:

```bash
# 1. تثبيت التبعيات وتشغيلها
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # المنفذ الافتراضي 8787
```

افتح `http://localhost:8787/install` في المتصفح وأكمل 4 خطوات:

1. **فحص البيئة** — يكتشف تلقائيًا إصدار PHP والإضافات المطلوبة وأذونات الملفات
2. **إعداد قاعدة البيانات** — أدخل معلومات اتصال MySQL وانقر لاختبار الاتصال
3. **حساب المسؤول** — اضبط اسم التطبيق واسم مستخدم وكلمة مرور المسؤول
4. **تنفيذ التثبيت** — استيراد SQL تلقائيًا → إنشاء المسؤول → كتابة إعدادات .env

بعد اكتمال التثبيت سجّل الدخول باسم المستخدم وكلمة المرور اللذين حددتهما. يكتب التثبيت الناجح ملف `.install.lock`، وتقوم واجهة `/install` بتحقق مزدوج (قفل الملف + isInstalled) لمنع إعادة التثبيت؛ وقد أُضيف `.install.lock` إلى `.gitignore`. يُنصح بحذف مسار `/install` من `admin/config/route.php` في بيئة الإنتاج.

---

## 二、التثبيت اليدوي

### 2.1 استنساخ المشروع

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 تثبيت تبعيات PHP

```bash
# خدمة واجهات الأعمال
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# لوحة الإدارة
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 إعداد متغيرات البيئة

عدّل `service/.env` (واجهات الأعمال) و `admin/.env` (لوحة الإدارة)، وغيّر الإعدادات الرئيسية التالية:

```bash
# اتصال قاعدة البيانات
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service يستخدم appointment، وadmin يستخدم open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# اتصال Redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# مفتاح JWT — في بيئة الإنتاج غيّره حتمًا إلى سلسلة عشوائية من 64 حرفًا
JWT_SECRET_KEY=your-64-char-random-string

# مفتاح التشفير — في بيئة الإنتاج غيّره حتمًا
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# ملح Hashids — في بيئة الإنتاج غيّره حتمًا
HASHIDS_SALT=your-random-salt

# وضع التصحيح — في بيئة الإنتاج يجب ضبطه على false
APP_DEBUG=false
```

> الشرح الكامل للمتغيرات موجود في `service/.env.example` و `admin/.env.example`.

### 1.4 إنشاء قاعدة البيانات واستيرادها

```bash
# إنشاء قاعدة البيانات (يمكن أن تستخدم service وadmin قاعدة واحدة أو قاعدتين منفصلتين)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# استيراد سكربت التثبيت الموحد (يشمل جميع الجداول 54+ + بيانات الصلاحيات + البيانات التجريبية)
mysql -u root -p appointment < ../install.sql
mysql -u root -p open_admin < ../install.sql
```

> يتكوّن `../install.sql` من دمج جميع ملفات الهجرة، بإجمالي 2723 سطرًا، ويتضمن جميع جداول لوحة الإدارة وخدمات الأعمال وبيانات البذر. يُنفَّذ مرة واحدة للتثبيت الجديد؛ وإعادة تنفيذه على قاعدة موجودة ستتوقف بسبب تعارض المفاتيح الأساسية/الأعمدة، وفي سيناريو الترقية يُرجى عمل نسخة احتياطية أولًا أو معالجة التعارضات يدويًا.

### 1.5 تشغيل الخدمات

```bash
# تشغيل خدمة واجهات الأعمال (المنفذ الافتراضي 8787)
cd service/
php start.php start -d

# تشغيل لوحة الإدارة (المنفذ الافتراضي 8787)
cd ../admin/
php start.php start -d
```

### 1.6 التحقق من التثبيت

```bash
# واجهات الأعمال
curl http://localhost:8787/api/common/config

# فحص صحة لوحة الإدارة
curl http://localhost:8787/health

# تسجيل دخول لوحة الإدارة (الحساب الافتراضي أدناه)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 الحسابات الافتراضية

| الدور | اسم المستخدم | كلمة المرور | الوصف |
|------|--------|------|------|
| المشرف الأعلى | `admin` | `admin123` | يملك جميع الصلاحيات |

> يُرجى تغيير كلمة المرور فورًا بعد أول تسجيل دخول.

---

## 三、النشر عبر Docker

### 2.1 خدمة واجهات الأعمال

```bash
cd service/
cp .env.docker .env
# عدّل .env وغيّر المفاتيح وكلمات المرور
docker-compose up -d
```

الترتيب: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 لوحة الإدارة

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 استيراد قاعدة البيانات في بيئة Docker

```bash
# انسخ install.sql إلى الحاوية ونفّذه
docker cp ../install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## 四、نظرة عامة على بنية قاعدة البيانات

| المجال | عدد الجداول | الجداول الأساسية |
|----|------|--------|
| لوحة الإدارة | 8 | `appointment_admin_user`, `appointment_admin_role`, `appointment_admin_permission`, `appointment_operation_log` |
| مجال المستخدم | 4 | `appointment_user`, `appointment_user_address`, `appointment_user_favorite`, `appointment_user_device` |
| مجال الفني | 8 | `appointment_technician_profile`, `appointment_technician_schedule`, `appointment_technician_earning`, `appointment_technician_withdrawal`, `appointment_technician_tier_config` |
| مجال الخدمة | 4 | `appointment_service_category`, `appointment_service`, `appointment_service_package`, `appointment_service_record` |
| مجال الطلب | 5 | `appointment_order`, `appointment_order_item`, `appointment_order_payment`, `appointment_order_refund`, `appointment_order_review` |
| مجال التسويق | 8 | `appointment_coupon`, `appointment_member_card`, `appointment_gift_card`, `appointment_user_points`, `appointment_promotion` |
| الطابور | 1 | `appointment_queue_number` |
| مجال المحتوى | 5 | `appointment_banner`, `appointment_announcement`, `appointment_faq`, `appointment_feedback`, `appointment_platform_agreement` |
| مجال المجتمع | 3 | `appointment_post`, `appointment_comment`, `appointment_moment` |
| المتاجر | 1 | `appointment_store` |
| التدريب | 2 | `appointment_training_course`, `appointment_training_progress` |
| الامتحانات | 3 | `appointment_exam`, `appointment_exam_question`, `appointment_exam_attempt` |
| النظام | 3 | `appointment_system_config`, `appointment_notification`, `appointment_signature` |
| **الإجمالي** | **55** | |

جميع الجداول تستخدم البادئة `appointment_`، والمفتاح الأساسي `id` من نوع BIGINT غير تلقائي (يولَّد من snowflake-php على مستوى التطبيق).

---

## 五、تشغيل الاختبارات

```bash
# اختبارات واجهات الأعمال (21 test)
cd service/
php vendor/bin/phpunit

# اختبارات لوحة الإدارة (59 tests)
cd admin/
php vendor/bin/phpunit

# التحليل الساكن
php vendor/bin/phpstan analyse --level=5 app/

# فحص نمط الكود
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## 六、إعداد الخدمات الخارجية

أدخل مجموعات الإعدادات التالية في «إعدادات النظام» بلوحة الإدارة:

| مجموعة الإعدادات | الاستخدام | إلزامية |
|--------|------|------|
| `wechat_pay` | رقم تاجر WeChat Pay / مفتاح API / الشهادة | مطلوبة لوظيفة الدفع |
| `wechat_app` | AppID / AppSecret لبرنامج WeChat الصغير | مطلوبة لتسجيل دخول WeChat |
| `sms` | مزود الرسائل (aliyun/tencent) + التوقيع/القالب | مطلوبة لرموز التحقق بالرسائل |
| `map_service` | خدمة الخرائط (amap/tencent) + API Key | مطلوبة لوظائف LBS |
| `storage` | التخزين الكائني (oss/cos) + AccessKey/Endpoint | مطلوب لرفع الملفات |

---

## 七、الأسئلة الشائعة

**س: خطأ عند التشغيل `Class 'support\Model' not found`**
ج: نفّذ `composer dump-autoload`.

**س: فشل الاتصال بقاعدة البيانات `SQLSTATE[HY000] [2002]`**
ج: تحقق من إعدادات `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` في `.env`.

**س: خطأ ترميز عند استيراد SQL**
ج: استخدم `mysql -u root -p --default-character-set=utf8mb4 < ../install.sql`

**س: فشل الاتصال بـ Redis**
ج: تأكد من تشغيل Redis وتحقق من إعدادات `REDIS_HOST`/`REDIS_PORT`.

**س: المنفذ مشغول**
ج: عدّل منفذ `listen` في `config/server.php`.

**س: رمز التحقق لا يظهر**
ج: تأكد من تثبيت إضافة GD ومن صحة إعداد `POSTER_CAPTCHA_STORAGE` (محليًا يمكن `file`، وفي الإنتاج `redis`).

**س: Elasticsearch لا يعمل**
ج: ES مكوّن اختياري، تأكد من صحة إعداد `SCOUT_HOSTS` ومن تشغيل خدمة ES.

---

## 八、بنية الدليل

```
appointment-php/
├── admin/                    # لوحة الإدارة (webman v2)
│   ├── app/                  # المتحكمات / النماذج / الوسائط
│   ├── config/               # إعدادات المسارات / قاعدة البيانات / الوسائط
│   ├── database/             # سكربتات النسخ الاحتياطي (بنية الجداول وبيانات البذر موحدة في docs/install.sql)
│   ├── tests/                # اختبارات PHPUnit (59 tests)
│   ├── .env.example          # قالب متغيرات البيئة
│   ├── .env.docker           # متغيرات بيئة Docker
│   ├── Dockerfile            # ملف بناء Docker
│   └── docker-compose.yml    # ترتيب Docker
├── service/                  # خدمة واجهات الأعمال (webman v2)
│   ├── app/                  # المتحكمات / النماذج / الوسائط
│   ├── config/               # إعدادات الأمان / المسارات / قاعدة البيانات
│   ├── seed.php              # مشغّل بذر البيانات التجريبية (يقرأ مقطع البيانات التجريبية من docs/install.sql)
│   ├── tests/                # اختبارات PHPUnit (21 tests)
│   ├── .env.example          # قالب متغيرات البيئة
│   ├── .env.docker           # متغيرات بيئة Docker
│   ├── Dockerfile            # ملف بناء Docker
│   └── docker-compose.yml    # ترتيب Docker
├── docs/                     # الوثائق
│   ├── INSTALL.md            # دليل التثبيت هذا
│   ├── install.sql           # سكربت تثبيت قاعدة البيانات الموحد (2723 سطرًا)
│   ├── ARCHITECTURE.md       # وثيقة التصميم المعماري
│   ├── API.md                # وثيقة مرجع API
│   └── AUDIT-REPORT.md       # تقرير المراجعة
└── .github/workflows/        # خط أنابيب CI/CD
    └── ci.yml
```
