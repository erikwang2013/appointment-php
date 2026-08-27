# Appointment Service System — Installation Guide
> **Languages**: [中文](../INSTALL.md) · [한국어](../ko/INSTALL.md) · [Русский](../ru/INSTALL.md) · [Deutsch](../de/INSTALL.md) · [Français](../fr/INSTALL.md) · [Español](../es/INSTALL.md) · [Português](../pt/INSTALL.md) · [हिन्दी](../hi/INSTALL.md) · [العربية](../ar/INSTALL.md) · [বাংলা](../bn/INSTALL.md) · [Bahasa Indonesia](../id/INSTALL.md) · [日本語](../ja/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## Environment Requirements

| Component | Minimum Version | Notes |
|-----------|-----------------|-------|
| PHP | 8.3+ | Extensions: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | Table prefix `appointment_`, utf8mb4 charset |
| Redis | 6.0+ | Cache / rate limit / Session / captcha storage |
| Composer | 2.x | PHP dependency management |
| Elasticsearch | 8.x (optional) | Full-text search; core features work without it |

---

## 1. Web Install Wizard (Recommended)

After starting the admin dashboard, visit `/install` in a browser to enter the one-click install wizard:

```bash
# 1. Install dependencies and start
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # default port 8787
```

Open `http://localhost:8787/install` in a browser and complete 4 steps:

1. **Environment check** — auto-detects PHP version, required extensions, file permissions
2. **Database config** — fill in MySQL connection info, click Test Connection
3. **Admin account** — set the app name, admin username and password
4. **Run installation** — auto-imports SQL → creates admin → writes .env config

After installation completes, log in with the credentials you set. A `.install.lock` file is written on success, and the `/install` endpoint double-checks (file lock + isInstalled) to prevent re-installation; `.install.lock` is in `.gitignore`. For production, it is recommended to remove the `/install` route from `admin/config/route.php`.

---

## 2. Manual Installation

### 2.1 Clone the Project

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 2.2 Install PHP Dependencies

```bash
# Business API service
cd service/
cp .env.example .env
composer install --no-dev --optimize-autoloader

# Admin dashboard
cd ../admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
```

### 2.3 Configure Environment Variables

Edit `service/.env` (business API) and `admin/.env` (admin dashboard), modify the following key config:

```bash
# Database connection
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=appointment          # service uses appointment, admin uses open_admin
DB_USERNAME=root
DB_PASSWORD=your-password

# Redis connection
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# JWT secret — change to a 64-character random string in production
JWT_SECRET_KEY=your-64-char-random-string

# Encryption keys — change in production
ENCRYPTION_KEY=your-32-byte-key
ENCRYPTABLE_KEY=your-32-byte-key

# Hashids salt — change in production
HASHIDS_SALT=your-random-salt

# Debug mode — must be false in production
APP_DEBUG=false
```

> Full variable documentation is in `service/.env.example` and `admin/.env.example`.

### 2.4 Create the Database and Import

```bash
# Create databases (service and admin can share one database or use separate ones)
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Import the unified install script (contains all 54+ tables + permission data + demo data)
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` is merged from all migration files, 2723 lines in total, containing the full schema and seed data for both the admin dashboard and the business service. Run it once for a fresh install; re-running on an existing database will abort on primary-key/column conflicts — for upgrades, back up first or resolve conflicts manually.

### 2.5 Start the Services

```bash
# Start the business API service (default port 8787)
cd service/
php start.php start -d

# Start the admin dashboard (default port 8787)
cd ../admin/
php start.php start -d
```

### 2.6 Verify the Installation

```bash
# Business API
curl http://localhost:8787/api/common/config

# Admin dashboard health check
curl http://localhost:8787/health

# Admin dashboard login (default credentials below)
curl -X POST http://localhost:8787/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"username":"admin","password":"admin123"}'
```

### 2.7 Default Account

| Role | Username | Password | Notes |
|------|----------|----------|-------|
| Super admin | `admin` | `admin123` | Has all permissions |

> Change the password immediately after first login.

---

## 3. Docker Deployment

### 3.1 Business API Service

```bash
cd service/
cp .env.docker .env
# Edit .env, change the keys and passwords
docker-compose up -d
```

Stack: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 3.2 Admin Dashboard

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 3.3 Importing the Database in Docker

```bash
# Copy install.sql into the container and run it
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## 4. Database Structure Overview

| Domain | Tables | Core Tables |
|--------|--------|-------------|
| Admin dashboard | 8 | `appointment_admin_user`, `appointment_admin_role`, `appointment_admin_permission`, `appointment_operation_log` |
| User | 4 | `appointment_user`, `appointment_user_address`, `appointment_user_favorite`, `appointment_user_device` |
| Technician | 8 | `appointment_technician_profile`, `appointment_technician_schedule`, `appointment_technician_earning`, `appointment_technician_withdrawal`, `appointment_technician_tier_config` |
| Service | 4 | `appointment_service_category`, `appointment_service`, `appointment_service_package`, `appointment_service_record` |
| Order | 5 | `appointment_order`, `appointment_order_item`, `appointment_order_payment`, `appointment_order_refund`, `appointment_order_review` |
| Marketing | 8 | `appointment_coupon`, `appointment_member_card`, `appointment_gift_card`, `appointment_user_points`, `appointment_promotion` |
| Queue | 1 | `appointment_queue_number` |
| Content | 5 | `appointment_banner`, `appointment_announcement`, `appointment_faq`, `appointment_feedback`, `appointment_platform_agreement` |
| Community | 3 | `appointment_post`, `appointment_comment`, `appointment_moment` |
| Store | 1 | `appointment_store` |
| Training | 2 | `appointment_training_course`, `appointment_training_progress` |
| Exam | 3 | `appointment_exam`, `appointment_exam_question`, `appointment_exam_attempt` |
| System | 3 | `appointment_system_config`, `appointment_notification`, `appointment_signature` |
| **Total** | **55** | |

All tables use the `appointment_` prefix; the `id` primary key is BIGINT non-auto-increment (generated at the application layer by snowflake-php).

---

## 5. Running Tests

```bash
# Business API tests (21 tests)
cd service/
php vendor/bin/phpunit

# Admin dashboard tests (59 tests)
cd admin/
php vendor/bin/phpunit

# Static analysis
php vendor/bin/phpstan analyse --level=5 app/

# Code style check
php vendor/bin/php-cs-fixer fix --dry-run --diff
```

---

## 6. Third-Party Service Configuration

Fill in the following config groups in the admin dashboard under "System Config":

| Config Group | Purpose | Required |
|--------------|---------|----------|
| `wechat_pay` | WeChat Pay merchant ID / API key / certificates | For payment features |
| `wechat_app` | WeChat Mini Program AppID / AppSecret | For WeChat login |
| `sms` | SMS provider (aliyun/tencent) + signature/template | For SMS captcha |
| `map_service` | Map service (amap/tencent) + API Key | For LBS features |
| `storage` | Object storage (oss/cos) + AccessKey/Endpoint | For file uploads |

---

## 7. FAQ

**Q: Startup error `Class 'support\Model' not found`**
A: Run `composer dump-autoload`.

**Q: Database connection failure `SQLSTATE[HY000] [2002]`**
A: Check the `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` config in `.env`.

**Q: Encoding error when importing SQL**
A: Use `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql`

**Q: Redis connection failure**
A: Confirm Redis is running and check the `REDIS_HOST`/`REDIS_PORT` config.

**Q: Port already in use**
A: Change the `listen` port in `config/server.php`.

**Q: Captcha not displaying**
A: Confirm the GD extension is installed and `POSTER_CAPTCHA_STORAGE` is configured correctly (use `file` locally, `redis` in production).

**Q: Elasticsearch not working**
A: ES is optional; confirm `SCOUT_HOSTS` is configured correctly and the ES service is running.

---

## 8. Directory Structure

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
