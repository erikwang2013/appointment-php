# 预约服务系统 — 安装指南
> **多语言**：[English](en/INSTALL.md) · [한국어](ko/INSTALL.md) · [Русский](ru/INSTALL.md) · [Deutsch](de/INSTALL.md) · [Français](fr/INSTALL.md) · [Español](es/INSTALL.md) · [Português](pt/INSTALL.md) · [हिन्दी](hi/INSTALL.md) · [العربية](ar/INSTALL.md) · [বাংলা](bn/INSTALL.md) · [Bahasa Indonesia](id/INSTALL.md) · [日本語](ja/INSTALL.md)

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

---

## 环境要求

| 组件 | 最低版本 | 说明 |
|------|----------|------|
| PHP | 8.3+ | 扩展: bcmath, curl, gd, mbstring, pdo, pdo_mysql, pcntl, redis |
| MySQL | 8.0+ | 表前缀 `erik_`，字符集 utf8mb4 |
| Redis | 6.0+ | 缓存 / 限流 / Session / 验证码存储 |
| Composer | 2.x | PHP 依赖管理 |
| Elasticsearch | 8.x (可选) | 全文检索，不安装不影响核心功能 |

---

## 一、Web 安装向导（推荐）

启动管理后台后，浏览器访问 `/install` 进入一键安装向导：

```bash
# 1. 安装依赖并启动
cd admin/
cp .env.example .env
composer install --no-dev --optimize-autoloader
php start.php start -d     # 默认端口 8787
```

浏览器打开 `http://localhost:8787/install`，按 4 步完成：

1. **环境检查** — 自动检测 PHP 版本、必需扩展、文件权限
2. **数据库配置** — 填写 MySQL 连接信息，点击测试连接
3. **管理员账号** — 设置应用名称、管理员用户名和密码
4. **执行安装** — 自动导入 SQL → 创建管理员 → 写入 .env 配置

安装完成后使用设置的用户名密码登录。安装成功会写入 `.install.lock` 文件，`/install` 接口双重校验（文件锁 + isInstalled）防重复安装；`.install.lock` 已加入 `.gitignore`。建议生产环境删除 `admin/config/route.php` 中的 `/install` 路由。

---

## 二、手动安装

### 2.1 克隆项目

```bash
git clone <repo-url> appointment-php
cd appointment-php
```

### 1.2 安装 PHP 依赖

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

### 1.3 配置环境变量

编辑 `service/.env`（业务 API）和 `admin/.env`（管理后台），修改以下关键配置：

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

> 完整变量说明见 `service/.env.example` 和 `admin/.env.example`。

### 1.4 创建数据库并导入

```bash
# 创建数据库（service 和 admin 可使用同一数据库，也可分开）
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS appointment DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS open_admin DEFAULT CHARSET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 导入统一安装脚本（包含全部 54+ 张表 + 权限数据 + 演示数据）
mysql -u root -p appointment < docs/install.sql
mysql -u root -p open_admin < docs/install.sql
```

> `docs/install.sql` 由全部迁移文件合并而成，共 2723 行，包含管理后台和业务服务的全部表结构及种子数据。全新安装一次执行；对已有库重复执行会因主键/列冲突中断，升级场景请先备份或手动处理冲突。

### 1.5 启动服务

```bash
# 启动业务 API 服务（默认端口 8787）
cd service/
php start.php start -d

# 启动管理后台（默认端口 8787）
cd ../admin/
php start.php start -d
```

### 1.6 验证安装

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

### 1.7 默认账号

| 角色 | 用户名 | 密码 | 说明 |
|------|--------|------|------|
| 超级管理员 | `admin` | `admin123` | 拥有全部权限 |

> 首次登录后请立即修改密码。

---

## 三、Docker 部署

### 2.1 业务 API 服务

```bash
cd service/
cp .env.docker .env
# 编辑 .env，修改密钥和密码
docker-compose up -d
```

编排: nginx (80/443) + app (8787) + mysql (3306) + redis (6379) + elasticsearch (9200)

### 2.2 管理后台

```bash
cd admin/
cp .env.docker .env
docker-compose up -d
```

### 2.3 Docker 环境导入数据库

```bash
# 将 install.sql 复制到容器中执行
docker cp docs/install.sql appointment-svc-mysql:/tmp/
docker exec -it appointment-svc-mysql mysql -u root -p appointment < /tmp/install.sql
```

---

## 四、数据库结构概览

| 域 | 表数 | 核心表 |
|----|------|--------|
| 管理后台 | 8 | `erik_admin_user`, `erik_admin_role`, `erik_admin_permission`, `erik_operation_log` |
| 用户域 | 4 | `erik_user`, `erik_user_address`, `erik_user_favorite`, `erik_user_device` |
| 技师域 | 8 | `erik_technician_profile`, `erik_technician_schedule`, `erik_technician_earning`, `erik_technician_withdrawal`, `erik_technician_tier_config` |
| 服务域 | 4 | `erik_service_category`, `erik_service`, `erik_service_package`, `erik_service_record` |
| 订单域 | 5 | `erik_order`, `erik_order_item`, `erik_order_payment`, `erik_order_refund`, `erik_order_review` |
| 营销域 | 8 | `erik_coupon`, `erik_member_card`, `erik_gift_card`, `erik_user_points`, `erik_promotion` |
| 排队 | 1 | `erik_queue_number` |
| 内容域 | 5 | `erik_banner`, `erik_announcement`, `erik_faq`, `erik_feedback`, `erik_platform_agreement` |
| 社区域 | 3 | `erik_post`, `erik_comment`, `erik_moment` |
| 门店 | 1 | `erik_store` |
| 培训 | 2 | `erik_training_course`, `erik_training_progress` |
| 考试 | 3 | `erik_exam`, `erik_exam_question`, `erik_exam_attempt` |
| 系统 | 3 | `erik_system_config`, `erik_notification`, `erik_signature` |
| **合计** | **55** | |

所有表使用 `erik_` 前缀，主键 `id` 为 BIGINT 非自增（由 snowflake-php 应用层生成）。

---

## 五、运行测试

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

## 六、第三方服务配置

在管理后台「系统配置」中填写以下配置组：

| 配置组 | 用途 | 必填 |
|--------|------|------|
| `wechat_pay` | 微信支付商户号 / API 密钥 / 证书 | 支付功能需要 |
| `wechat_app` | 微信小程序 AppID / AppSecret | 微信登录需要 |
| `sms` | 短信服务商 (aliyun/tencent) + 签名/模板 | 短信验证码需要 |
| `map_service` | 地图服务 (amap/tencent) + API Key | LBS 功能需要 |
| `storage` | 对象存储 (oss/cos) + AccessKey/Endpoint | 文件上传需要 |

---

## 七、常见问题

**Q: 启动报错 `Class 'support\Model' not found`**
A: 运行 `composer dump-autoload`。

**Q: 数据库连接失败 `SQLSTATE[HY000] [2002]`**
A: 检查 `.env` 中 `DB_HOST`/`DB_PORT`/`DB_USERNAME`/`DB_PASSWORD` 配置。

**Q: 导入 SQL 时编码错误**
A: 使用 `mysql -u root -p --default-character-set=utf8mb4 < docs/install.sql`

**Q: Redis 连接失败**
A: 确认 Redis 已启动，检查 `REDIS_HOST`/`REDIS_PORT` 配置。

**Q: 端口被占用**
A: 修改 `config/server.php` 中的 `listen` 端口。

**Q: 验证码不显示**
A: 确认 GD 扩展已安装，`POSTER_CAPTCHA_STORAGE` 配置正确（本地可用 `file`，生产用 `redis`）。

**Q: Elasticsearch 不工作**
A: ES 为可选组件，确认 `SCOUT_HOSTS` 配置正确且 ES 服务已启动。

---

## 八、目录结构

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
