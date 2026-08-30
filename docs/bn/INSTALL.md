# অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম — ইনস্টল গাইড
> **Languages**: [中文](../INSTALL.md) · [English](../en/INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

> বাংলা অনুবাদ · মূল: [中文](../INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## পরিবেশ প্রয়োজনীয়তা

| কম্পোনেন্ট | ন্যূনতম ভার্সন | বিবরণ |
|------|----------|------|
| PHP | 8.3+ | এক্সটেনশন: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | টেবিল প্রিফিক্স `appointment_`, ক্যারেক্টার সেট utf8mb4 |
| Redis | 6.0+ | ক্যাশ / রেট লিমিট / Session / ভেরিফিকেশন কোড স্টোরেজ |
| Composer | 2.x | PHP ডিপেন্ডেন্সি ম্যানেজমেন্ট |
| Elasticsearch | 8.x (অপশনাল) | ফুল-টেক্সট সার্চ, ইনস্টল না করলে মূল ফিচারে সমস্যা নেই |

---

## 一、Web ইনস্টল উইজার্ড (প্রস্তাবিত)

ম্যানেজমেন্ট ব্যাকএন্ড চালু করার পর ব্রাউজারে `/install` খুললে ওয়ান-ক্লিক ইনস্টল উইজার্ড:

```bash
# 1. ডিপেন্ডেন্সি ইনস্টল করে চালু করুন
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # ডিফল্ট পোর্ট 8787
```

ব্রাউজারে `http://localhost:8787/install` খুলুন, ৪ ধাপে সম্পন্ন:

1. **পরিবেশ চেক** — PHP ভার্সন, প্রয়োজনীয় এক্সটেনশন, ফাইল পারমিশন অটো ডিটেক্ট
2. **ডেটাবেস কনফিগ** — MySQL সংযোগ তথ্য পূরণ, টেস্ট কানেকশন ক্লিক
3. **অ্যাডমিন অ্যাকাউন্ট** — অ্যাপ্লিকেশন নাম, অ্যাডমিন ইউজারনেম ও পাসওয়ার্ড সেট
4. **ইনস্টল এক্সিকিউশন** — অটো SQL ইমপোর্ট → অ্যাডমিন তৈরি → .env কনফিগ লেখা

ইনস্টল সম্পন্নের পর সেট করা ইউজারনেম/পাসওয়ার্ড দিয়ে লগইন করুন। ইনস্টল সফলে `.install.lock` ফাইল লেখা হয়, `/install` API দ্বৈত যাচাই (ফাইল লক + isInstalled) পুনঃইনস্টল প্রতিরোধ করে; `.install.lock` `.gitignore` এ যোগ করা আছে। প্রোডাকশনে `admin/config/route.php` এর `/install` রাউট ডিলিট করার পরামর্শ দেওয়া হয়।

---

## 二、ম্যানুয়াল ইনস্টল

### 2.1 প্রজেক্ট ক্লোন

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 PHP ডিপেন্ডেন্সি ইনস্টল

```bash
# বিজনেস API সার্ভিস
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# ম্যানেজমেন্ট ব্যাকএন্ড
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 এনভায়রনমেন্ট ভেরিয়েবল কনফিগ

`service/.env` (বিজনেস API) এবং `admin/.env` (ম্যানেজমেন্ট ব্যাকএন্ড) এডিট করুন, নিচের মূল কনফিগ পরিবর্তন:

```bash
# ডেটাবেস কানেকশন
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service-এর জন্য appointment, admin-এর জন্য open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Redis কানেকশন
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT সিক্রেট — প্রোডাকশনে অবশ্যই ৬৪ অক্ষরের র্যান্ডম স্ট্রিংয়ে পরিবর্তন করুন
JWT_SECRET_KEY=your-64-char-random-string

# এনক্রিপশন কী — প্রোডাকশনে অবশ্যই পরিবর্তন করুন
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Hashids সল্ট — প্রোডাকশনে অবশ্যই পরিবর্তন করুন
HASHIDS_SALT=your-random-salt

# ডিবাগ মোড — প্রোডাকশনে অবশ্যই false করতে হবে
APP_DEBUG=false
```

> সম্পূর্ণ ভেরিয়েবল ব্যাখ্যা দেখুন `service/.env.example` এবং `admin/.env.example`।

### 1.4 ডেটাবেস তৈরি ও ইমপোর্ট

```bash
# ডেটাবেস তৈরি করুন (service এবং admin একই ডেটাবেস ব্যবহার করতে পারে, আলাদাও হতে পারে)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# ইউনিফাইড ইনস্টল স্ক্রিপ্ট ইমপোর্ট করুন (সব ৫৪+ টেবিল + পারমিশন ডেটা + ডেমো ডেটা)
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` সব মাইগ্রেশন ফাইল মার্জ করে তৈরি, মোট ২৭২৩ লাইন, ম্যানেজমেন্ট ব্যাকএন্ড ও বিজনেস সার্ভিসের সব টেবিল স্ট্রাকচার ও সিড ডেটা ধারণ করে। নতুন ইনস্টলে একবার এক্সিকিউট করুন; বিদ্যমান ডেটাবেসে পুনরায় এক্সিকিউট করলে প্রাইমারি কী/কলাম সংঘর্ষে বন্ধ হয়ে যেতে পারে, আপগ্রেড পরিস্থিতিতে আগে ব্যাকআপ নিন বা সংঘর্ষ ম্যানুয়ালি হ্যান্ডল করুন।

### 1.5 সার্ভিস চালু

```bash
# বিজনেস API সার্ভিস চালু করুন (ডিফল্ট পোর্ট 8787)
cd service/
php start.php start -d

# ম্যানেজমেন্ট ব্যাকএন্ড চালু করুন (ডিফল্ট পোর্ট 8787)
cd ../admin/
php start.php start -d
```

### 1.6 ইনস্টল যাচাই

```bash
# বিজনেস API
curl http://localhost:8787/api/common/config

# ম্যানেজমেন্ট ব্যাকএন্ড হেলথ চেক
curl http://localhost:8787/health

# ম্যানেজমেন্ট ব্যাকএন্ড লগইন (ডিফল্ট অ্যাকাউন্ট/পাসওয়ার্ড নিচে দেখুন)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 1.7 ডিফল্ট অ্যাকাউন্ট

| রোল | ইউজারনেম | পাসওয়ার্ড | বিবরণ |
|------|--------|------|------|
| সুপার অ্যাডমিন | `admin` | `admin123` | সব পারমিশন আছে |

> প্রথম লগইনের পর অবিলম্বে পাসওয়ার্ড পরিবর্তন করুন।

---

## 三、Docker ডিপ্লয়মেন্ট

### 2.1 বিজনেস API সার্ভিস

```bash
cd service/
cp .env.docker .env
# .env এডিট করুন, সিক্রেট ও পাসওয়ার্ড পরিবর্তন করুন
docker-compose up -d
```

অর্কেস্ট্রেশন: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 ম্যানেজমেন্ট ব্যাকএন্ড

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Docker পরিবেশে ডেটাবেস ইমপোর্ট

```bash
# install.sql কন্টেইনারে কপি করে এক্সিকিউট করুন
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## 四、ডেটাবেস স্ট্রাকচার ওভারভিউ

| ডোমেইন | টেবিল সংখ্যা | মূল টেবিল |
|----|------|--------|
| ম্যানেজমেন্ট ব্যাকএন্ড | ৮ | `appointment_admin_user`, `appointment_admin_role`, `appointment_admin_permission`, `appointment_operation_log` |
| ব্যবহারকারী ডোমেইন | ৪ | `appointment_user`, `appointment_user_address`, `appointment_user_favorite`, `appointment_user_device` |
| টেকনিশিয়ান ডোমেইন | ৮ | `appointment_technician_profile`, `appointment_technician_schedule`, `appointment_technician_earning`, `appointment_technician_withdrawal`, `appointment_technician_tier_config` |
| সার্ভিস ডোমেইন | ৪ | `appointment_service_category`, `appointment_service`, `appointment_service_package`, `appointment_service_record` |
| অর্ডার ডোমেইন | ৫ | `appointment_order`, `appointment_order_item`, `appointment_order_payment`, `appointment_order_refund`, `appointment_order_review` |
| মার্কেটিং ডোমেইন | ৮ | `appointment_coupon`, `appointment_member_card`, `appointment_gift_card`, `appointment_user_points`, `appointment_promotion` |
| কিউ | ১ | `appointment_queue_number` |
| কনটেন্ট ডোমেইন | ৫ | `appointment_banner`, `appointment_announcement`, `appointment_faq`, `appointment_feedback`, `appointment_platform_agreement` |
| কমিউনিটি ডোমেইন | ৩ | `appointment_post`, `appointment_comment`, `appointment_moment` |
| শাখা | ১ | `appointment_store` |
| ট্রেনিং | ২ | `appointment_training_course`, `appointment_training_progress` |
| পরীক্ষা | ৩ | `appointment_exam`, `appointment_exam_question`, `appointment_exam_attempt` |
| সিস্টেম | ৩ | `appointment_system_config`, `appointment_notification`, `appointment_signature` |
| **মোট** | **৫৫** | |

সব টেবিল `appointment_` প্রিফিক্স ব্যবহার, প্রাইমারি কী `id` BIGINT নন-অটোইনক্রিমেন্ট (snowflake-php দিয়ে অ্যাপ্লিকেশন লেয়ারে জেনারেট)।

---

## 五、টেস্ট চালানো

```bash
# বিজনেস API টেস্ট (২১ টেস্ট)
cd service/
php vendor/bin/phpunit

# ম্যানেজমেন্ট ব্যাকএন্ড টেস্ট (৫৯ টেস্ট)
cd admin/
php vendor/bin/phpunit

# স্ট্যাটিক অ্যানালাইসিস
php vendor/bin/phpstan analyse --level=5 app/

# কোড স্টাইল চেক
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## 六、থার্ড-পার্টি সার্ভিস কনফিগ

ম্যানেজমেন্ট ব্যাকএন্ডের "সিস্টেম কনফিগ" এ নিচের কনফিগ গ্রুপ পূরণ করুন:

| কনফিগ গ্রুপ | ব্যবহার | বাধ্যতামূলক |
|--------|------|------|
| `wechat_pay` | WeChat পেমেন্ট মার্চেন্ট নম্বর / API কী / সার্টিফিকেট | পেমেন্ট ফিচার লাগবে |
| `wechat_app` | WeChat মিনি-প্রোগ্রাম AppID / AppSecret | WeChat লগইন লাগবে |
| `sms` | SMS প্রোভাইডার (aliyun/tencent) + সিগনেচার/টেমপ্লেট | SMS ভেরিফিকেশন কোড লাগবে |
| `map_service` | ম্যাপ সার্ভিস (amap/tencent) + API Key | LBS ফিচার লাগবে |
| `storage` | অবজেক্ট স্টোরেজ (oss/cos) + AccessKey/Endpoint | ফাইল আপলোড লাগবে |

---

## 七、সাধারণ সমস্যা

**প্রশ্ন: চালুতে এরর `Class 'support\Model' not found`**
উত্তর: `composer dump-autoload` চালান।

**প্রশ্ন: ডেটাবেস সংযোগ ব্যর্থ `SQLSTATE[HY000] [2002]`**
উত্তর: `.env` এ `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` কনফিগ চেক করুন।

**প্রশ্ন: SQL ইমপোর্টে এনকোডিং এরর**
উত্তর: `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql` ব্যবহার করুন

**প্রশ্ন: Redis সংযোগ ব্যর্থ**
উত্তর: Redis চালু আছে কিনা নিশ্চিত করুন, `REDIS_HOST`/`REDIS_PORT` কনফিগ চেক করুন।

**প্রশ্ন: পোর্ট দখল**
উত্তর: `config/server.php` এর `listen` পোর্ট পরিবর্তন করুন।

**প্রশ্ন: ভেরিফিকেশন কোড দেখা যাচ্ছে না**
উত্তর: GD এক্সটেনশন ইনস্টল আছে কিনা নিশ্চিত করুন, `POSTER_CAPTCHA_STORAGE` কনফিগ সঠিক কিনা (লোকাল `file`, প্রোডাকশন `redis` ব্যবহার করা যায়)।

**প্রশ্ন: Elasticsearch কাজ করছে না**
উত্তর: ES অপশনাল কম্পোনেন্ট, `SCOUT_HOSTS` কনফিগ সঠিক ও ES সার্ভিস চালু আছে কিনা নিশ্চিত করুন।

---

## 八、ডিরেক্টরি স্ট্রাকচার

```
appointment-php/
├── admin/                    # ম্যানেজমেন্ট ব্যাকএন্ড (webman v2)
│   ├── app/                  # কন্ট্রোলার / মডেল / মিডলওয়্যার
│   ├── config/               # রাউট / ডেটাবেস / মিডলওয়্যার কনফিগ
│   ├── database/             # ব্যাকআপ স্ক্রিপ্ট (টেবিল স্ট্রাকচার ও সিড ডেটা docs/install.sql-এ)
│   ├── tests/                # PHPUnit টেস্ট (৫৯ টেস্ট)
│   ├── .env.example          # এনভায়রনমেন্ট ভেরিয়েবল টেমপ্লেট
│   ├── .env.docker           # Docker এনভায়রনমেন্ট ভেরিয়েবল
│   ├── Dockerfile            # Docker বিল্ড ফাইল
│   └── docker-compose.yml    # Docker অর্কেস্ট্রেশন
├── service/                  # বিজনেস API সার্ভিস (webman v2)
│   ├── app/                  # কন্ট্রোলার / মডেল / মিডলওয়্যার
│   ├── config/               # নিরাপত্তা / রাউট / ডেটাবেস কনফিগ
│   ├── seed.php              # ডেমো ডেটা সিড রানার (docs/install.sql-এর ডেমো ডেটা সেগমেন্ট পড়ে)
│   ├── tests/                # PHPUnit টেস্ট (২১ টেস্ট)
│   ├── .env.example          # এনভায়রনমেন্ট ভেরিয়েবল টেমপ্লেট
│   ├── .env.docker           # Docker এনভায়রনমেন্ট ভেরিয়েবল
│   ├── Dockerfile            # Docker বিল্ড ফাইল
│   └── docker-compose.yml    # Docker অর্কেস্ট্রেশন
├── docs/                     # ডকুমেন্টেশন
│   ├── INSTALL.md            # এই ইনস্টল গাইড
│   ├── install.sql           # ইউনিফাইড ডেটাবেস ইনস্টল স্ক্রিপ্ট (২৭২৩ লাইন)
│   ├── ARCHITECTURE.md       # আর্কিটেকচার ডিজাইন ডকুমেন্ট
│   ├── API.md                # API রেফারেন্স ডকুমেন্ট
│   └── AUDIT-REPORT.md       # অডিট রিপোর্ট
└── .github/workflows/        # CI/CD পাইপলাইন
    └── ci.yml
```
