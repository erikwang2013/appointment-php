# অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম — ইনস্টল গাইড

> বাংলা অনুবাদ · মূল: [中文](../INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## পরিবেশ প্রয়োজনীয়তা

| কম্পোনেন্ট | ন্যূনতম ভার্সন | বিবরণ |
|------|----------|------|
| PHP | 8.3+ | এক্সটেনশন: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | টেবিল প্রিফিক্স `erik_`, ক্যারেক্টার সেট utf8mb4 |
| Redis | 6.0+ | ক্যাশ / রেট লিমিট / Session / ভেরিফিকেশন কোড স্টোরেজ |
| Composer | 2.x | PHP ডিপেন্ডেন্সি ম্যানেজমেন্ট |
| Elasticsearch | 8.x (অপশনাল) | ফুল-টেক্সট সার্চ, ইনস্টল না করলে মূল ফিচারে সমস্যা নেই |

---

## 一、Web ইনস্টল উইজার্ড (প্রস্তাবিত)

ম্যানেজমেন্ট ব্যাকএন্ড চালু করার পর ব্রাউজারে `/install` খুললে ওয়ান-ক্লিক ইনস্টল উইজার্ড:

```bash
# 1. 安装依赖并启动
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # 默认端口 8787
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
# 业务 API 服务
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# 管理后台
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 1.3 এনভায়রনমেন্ট ভেরিয়েবল কনফিগ

`service/.env` (বিজনেস API) এবং `admin/.env` (ম্যানেজমেন্ট ব্যাকএন্ড) এডিট করুন, নিচের মূল কনফিগ পরিবর্তন:

```bash
# 数据库连接
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service 用 appointment，admin 用 open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Redis 连接
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT 密钥 — 生产环境务必修改为 64 位随机字符串
JWT_SECRET_KEY=your-64-char-random-string

# 加密密钥 — 生产环境务必修改
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Hashids 盐值 — 生产环境务必修改
HASHIDS_SALT=your-random-salt

# 调试模式 — 生产环境必须设为 false
APP_DEBUG=false
```

> সম্পূর্ণ ভেরিয়েবল ব্যাখ্যা দেখুন `service/.env.example` এবং `admin/.env.example`।

### 1.4 ডেটাবেস তৈরি ও ইমপোর্ট

```bash
# 创建数据库（service 和 admin 可使用同一数据库，也可分开）
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 导入统一安装脚本（包含全部 54+ 张表 + 权限数据 + 演示数据）
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` সব মাইগ্রেশন ফাইল মার্জ করে তৈরি, মোট ২৭২৩ লাইন, ম্যানেজমেন্ট ব্যাকএন্ড ও বিজনেস সার্ভিসের সব টেবিল স্ট্রাকচার ও সিড ডেটা ধারণ করে। নতুন ইনস্টলে একবার এক্সিকিউট করুন; বিদ্যমান ডেটাবেসে পুনরায় এক্সিকিউট করলে প্রাইমারি কী/কলাম সংঘর্ষে বন্ধ হয়ে যেতে পারে, আপগ্রেড পরিস্থিতিতে আগে ব্যাকআপ নিন বা সংঘর্ষ ম্যানুয়ালি হ্যান্ডল করুন।

### 1.5 সার্ভিস চালু

```bash
# 启动业务 API 服务（默认端口 8787）
cd service/
php start.php start -d

# 启动管理后台（默认端口 8787）
cd ../admin/
php start.php start -d
```

### 1.6 ইনস্টল যাচাই

```bash
# 业务 API
curl http://localhost:8787/api/common/config

# 管理后台健康检查
curl http://localhost:8787/health

# 管理后台登录（默认账号密码见下方）
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
# 编辑 .env，修改密钥和密码
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
# 将 install.sql 复制到容器中执行
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## 四、ডেটাবেস স্ট্রাকচার ওভারভিউ

| ডোমেইন | টেবিল সংখ্যা | মূল টেবিল |
|----|------|--------|
| ম্যানেজমেন্ট ব্যাকএন্ড | ৮ | `erik_admin_user`, `erik_admin_role`, `erik_admin_permission`, `erik_operation_log` |
| ব্যবহারকারী ডোমেইন | ৪ | `erik_user`, `erik_user_address`, `erik_user_favorite`, `erik_user_device` |
| টেকনিশিয়ান ডোমেইন | ৮ | `erik_technician_profile`, `erik_technician_schedule`, `erik_technician_earning`, `erik_technician_withdrawal`, `erik_technician_tier_config` |
| সার্ভিস ডোমেইন | ৪ | `erik_service_category`, `erik_service`, `erik_service_package`, `erik_service_record` |
| অর্ডার ডোমেইন | ৫ | `erik_order`, `erik_order_item`, `erik_order_payment`, `erik_order_refund`, `erik_order_review` |
| মার্কেটিং ডোমেইন | ৮ | `erik_coupon`, `erik_member_card`, `erik_gift_card`, `erik_user_points`, `erik_promotion` |
| কিউ | ১ | `erik_queue_number` |
| কনটেন্ট ডোমেইন | ৫ | `erik_banner`, `erik_announcement`, `erik_faq`, `erik_feedback`, `erik_platform_agreement` |
| কমিউনিটি ডোমেইন | ৩ | `erik_post`, `erik_comment`, `erik_moment` |
| শাখা | ১ | `erik_store` |
| ট্রেনিং | ২ | `erik_training_course`, `erik_training_progress` |
| পরীক্ষা | ৩ | `erik_exam`, `erik_exam_question`, `erik_exam_attempt` |
| সিস্টেম | ৩ | `erik_system_config`, `erik_notification`, `erik_signature` |
| **মোট** | **৫৫** | |

সব টেবিল `erik_` প্রিফিক্স ব্যবহার, প্রাইমারি কী `id` BIGINT নন-অটোইনক্রিমেন্ট (snowflake-php দিয়ে অ্যাপ্লিকেশন লেয়ারে জেনারেট)।

---

## 五、টেস্ট চালানো

```bash
# 业务 API 测试（21 tests）
cd service/
php vendor/bin/phpunit

# 管理后台测试（59 tests）
cd admin/
php vendor/bin/phpunit

# 静态分析
php vendor/bin/phpstan analyse --level=5 app/

# 代码风格检查
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
├── admin/                    # 管理后台 (webman v2)
│   ├── app/                  # 控制器 / 模型 / 中间件
│   ├── config/               # 路由 / 数据库 / 中间件配置
│   ├── database/             # 备份脚本（表结构与种子数据统一见 docs/install.sql）
│   ├── tests/                # PHPUnit 测试 (59 tests)
│   ├── .env.example          # 环境变量模板
│   ├── .env.docker           # Docker 环境变量
│   ├── Dockerfile            # Docker 构建文件
│   └── docker-compose.yml    # Docker 编排
├── service/                  # 业务 API 服务 (webman v2)
│   ├── app/                  # 控制器 / 模型 / 中间件
│   ├── config/               # 安全 / 路由 / 数据库配置
│   ├── seed.php              # 演示数据种子运行器（读取 docs/install.sql 演示数据段）
│   ├── tests/                # PHPUnit 测试 (21 tests)
│   ├── .env.example          # 环境变量模板
│   ├── .env.docker           # Docker 环境变量
│   ├── Dockerfile            # Docker 构建文件
│   └── docker-compose.yml    # Docker 编排
├── docs/                     # 文档
│   ├── INSTALL.md            # 本安装指南
│   ├── install.sql           # 统一数据库安装脚本（2723 行）
│   ├── ARCHITECTURE.md       # 架构设计文档
│   ├── API.md                # API 参考文档
│   └── AUDIT-REPORT.md       # 审查报告
└── .github/workflows/        # CI/CD 流水线
    └── ci.yml
```
