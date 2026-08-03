# 项目审计报告

**项目:** appointment-php (预约服务系统)  
**审计日期:** 2026-08-03  
**修复日期:** 2026-08-03  
**PHP 版本:** 8.3.7  
**测试结果:** 全部通过 (81 tests / 201 assertions)

---

## 修复状态

| # | 问题 | 严重度 | 状态 |
|---|------|--------|------|
| 1 | Service .env.example 缺失 | Critical | 已修复 — 生成完整 140+ 行模板 |
| 2 | debug 硬编码 true | Critical | 已修复 — 改为从 APP_DEBUG 环境变量读取 |
| 3 | API 文档弱密码 | Critical | 已修复 — 改为 getenv() 读取，默认空 |
| 4 | CI/CD 缺失 | Critical | 已修复 — 创建 .github/workflows/ci.yml |
| 5 | Service 缺 RateLimit | High | 已修复 — 创建中间件并注册全局 |
| 6 | 代码质量工具缺失 | High | 已修复 — 添加 phpstan + php-cs-fixer |
| 7 | Service 无 security.php | High | 已修复 — 创建完整 31 检测器配置 |
| 8 | Admin JWT 配置重复 | High | 已修复 — 统一变量名，删除重复行 |
| 9 | 上传仅检查扩展名 | Medium | 已修复 — 添加 MIME 类型校验 |
| 10 | 临时文件不清理 | Medium | 已修复 — 添加 shutdown 清理 + cron 方法 |
| 11 | Service Docker 简陋 | Medium | 已修复 — 重写为 5 服务编排 |
| 12 | Security 占位中间件 | Low | 已修复 — 删除死代码 |
| 13 | 缺少 strict_types | Low | 已修复 — 8 个文件添加声明 |
| 14 | 缺少版权声明 | Low | 已修复 — 14 个配置文件添加版权头 |
| — | Encryptable cipher 不一致 | Extra | 已修复 — 统一为 ENCRYPTABLE_CIPHER |

---

## 总览

| 维度 | 评分 | 状态 |
|------|------|------|
| 测试覆盖 | B+ | 81 个测试全部通过，但覆盖率可提升 |
| 代码规范 | B | 版权声明统一，但缺少静态分析工具 |
| 安全性 | B+ | 31 层检测器到位，但部分配置缺失 |
| 生态配置 | C+ | Service 端配置严重不足 |
| 运维就绪 | B | Admin Docker 完善，Service Docker 简陋 |
| 依赖管理 | C+ | 版本不一致，缺少 dev 工具链 |

---

## 一、关键问题 (Critical)

### 1.1 Service 端环境变量严重缺失

**文件:** `service/.env` 和 `service/.env.example`

Service 的 `.env` 仅有 9 行配置，`.env.example` 仅 16 行。对比 Admin 端的 108+ 行，缺少以下关键配置：

| 缺失配置 | 影响 |
|----------|------|
| `JWT_SECRET_KEY` / `JWT_*` | JWT 认证可能使用弱默认密钥 |
| `HASHIDS_SALT` / `HASHIDS_ALT_SALT` | ID 编解码使用弱盐值 |
| `SNOWFLAKE_*` | Snowflake ID 生成使用默认配置 |
| `ENCRYPTION_KEY` / `ENCRYPTABLE_KEY` | 数据加解密使用弱密钥 |
| `SCOUT_*` (ES 配置) | 搜索引擎使用默认 localhost |
| `POSTER_*` (验证码) | 验证码服务使用默认配置 |
| 微信支付 Key / 支付宝 Key | 支付功能无法正常使用 |
| SMS / Push / Map 服务 Key | 短信、推送、地图功能不可用 |

**建议:** 参照 `admin/.env.example` 为 service 生成完整的 `.env.example`。

### 1.2 调试模式硬编码为 true

**文件:** `admin/config/app.php:22`, `service/config/app.php:10`

```php
// 当前: 硬编码 true
'debug' => true,

// 应改为: 从环境变量读取
'debug' => filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
```

生产环境如果忘记修改，会暴露错误堆栈和敏感信息。

### 1.3 API 文档暴露默认密码

**文件:** `service/config/plugin/hg/apidoc/app.php:41`, `admin/config/plugin/hg/apidoc/app.php:36`

```php
// service: 弱密码
'password' => "123456",

// admin: 弱密码
'password' => "admin888",
```

API 文档接口如未关闭，攻击者可轻易访问。应改为环境变量读取，生产环境关闭文档。

### 1.4 CI/CD 流水线缺失

CLAUDE.md 中描述了 `.github/workflows/ci.yml`，但实际 `.github/` 目录不存在。项目无自动化测试、语法检查、构建验证流水线。

---

## 二、高优先级 (High)

### 2.1 Service 端缺少全局 RateLimit 中间件

**对比:**

| 中间件 | Admin | Service |
|--------|-------|---------|
| Cors | 全局 | 全局 |
| Locale | 全局 | 无 |
| SecurityMiddleware | 全局 | 全局 (`@` 格式) |
| RateLimit | 全局 | **缺失** |

Service API 没有全局限流保护，登录/注册等敏感端点暴露在暴力破解风险中。

### 2.2 包版本不一致

| 包名 | Admin 依赖版本 | Service 依赖版本 |
|------|---------------|-----------------|
| `workerman/webman-framework` | `^2.1` | `^2.0` |
| `erikwang2013/snowflake-php` | `^2.0` | `^1.0` |
| `erikwang2013/jwt-webman` | `^2.0` | `^1.0` |
| `erikwang2013/encryptable` | `^2.0` | `^1.0` |
| `erikwang2013/webman-scout` | `^2.0` | `^1.0` |
| `erikwang2013/season` | `^2.0` | `^1.0` |
| `erikwang2013/security-php` | `^1.1` | `^1.0` |

Admin 使用 v2 系列，Service 使用 v1 系列。

### 2.3 缺少代码质量工具

两个 `composer.json` 的 `require-dev` 中均只有 `phpunit/phpunit`。缺少：

- `phpstan/phpstan` — 静态分析
- `friendsofphp/php-cs-fixer` — 代码格式化
- `vimeo/psalm` — 类型安全分析

### 2.4 Service 端无独立安全配置文件

Admin 有完整的 `config/security.php`（31 种检测器配置），Service 端缺少此文件。

### 2.5 Admin .env 存在重复的 JWT 配置

**文件:** `admin/.env:109-111`

```ini
# 第 21 行已有:
JWT_SECRET=open-admin-jwt-secret-change-in-production
JWT_TTL=7200
JWT_REFRESH_TTL=1209600

# 第 109-111 行重复/冲突:
JWT_SECRET_KEY=test-jwt-secret-key-for-testing
JWT_DEFAULT_EXPIRE=86400
JWT_REFRESH_EXPIRE=604800
```

代码实际读取的是 `JWT_SECRET_KEY`（见 `admin/config/jwt.php:16`），而 `.env.example` 中未包含此变量。`.env` 中存在但 `.env.example` 中缺失，会导致新部署者不知如何配置。

---

## 三、中优先级 (Medium)

### 3.1 文件上传仅检查扩展名

**文件:** `admin/app/admin/controller/UploadController.php:38`

```php
$ext = strtolower($file->getUploadExtension() ?: 'bin');
if (!in_array($ext, $this->allowExts, true)) {
```

未验证文件的真实 MIME 类型。建议增加 `getUploadMimeType()` 校验。

### 3.2 DB::raw() 使用

**文件:** `admin/app/admin/controller/DashboardController.php:329`  
**文件:** `admin/app/admin/controller/CustomerProfileController.php`

```php
->groupBy(DB::raw('HOUR(created_at)'))
```

虽然此处参数为硬编码字符串，无注入风险，但 `DB::raw()` 是常见 SQL 注入入口，值得标记审计。

### 3.3 ExportController 临时文件不清理

导出 Excel/PDF 后，临时文件写入 `runtime/tmp/` 但未在响应后清理。长期运行可能占满磁盘。

### 3.4 Service docker-compose.yml 过于简陋

Admin 编排了 Nginx + App + MySQL + Redis + ES 5 个服务，而 Service 的只有 1 个 webman 容器，缺少数据库和缓存服务。

### 3.5 两个 Flutter 项目缺乏共享

- `apps/flutter/` — 用户端 App（Dart SDK >=3.2.0）
- `admin/apps/flutter/` — 管理后台（Dart SDK ^3.11.5）

API Service、AuthService 逻辑有重复可能，SDK 约束也不一致。

### 3.6 测试覆盖率有限

| 子项目 | 测试数 | 断言数 | 覆盖范围 |
|--------|--------|--------|----------|
| Admin | 60 | 165 | 工具类 (Snowflake, Hashids, Encryption, Captcha, Env, Backend) |
| Service | 21 | 36 | 基础测试 |

缺少 Controller 集成测试、Middleware 单元测试、数据库交互测试、API 端到端测试。

---

## 四、低优先级 (Low)

### 4.1 Service Security 中间件是空占位

**文件:** `service/app/middleware/Security.php`

该中间件不执行任何逻辑，仅 `return $next($request)`。真正的安全检测完全依赖全局 `\Erikwang2013\Security\Middleware\Webman\SecurityMiddleware`。此中间件未被全局配置引用，属于死代码。

### 4.2 WeChat 小程序页面不完整

`apps/wechat/` 仅有 5 个页面模块，缺少完整的预约流程、支付、技师端页面。

### 4.3 部分文件缺少 `declare(strict_types=1)`

`admin/app/functions.php`、`admin/support/*.php` 等文件未声明严格类型。

---

## 五、生态配置完整性矩阵

| 配置域 | Admin | Admin .env.example | Service | Service .env.example |
|--------|-------|-------------------|---------|---------------------|
| App 基础 | 完整 | 完整 | 完整 | 基础 |
| 数据库 | 完整 | 完整 | 完整 | 完整 |
| Redis | 完整 | 完整 | 完整 | 完整 |
| JWT | 完整 | 部分 (缺 KEY) | 完整 | 基础 |
| Hashids | 完整 | 完整 | 完整 | 缺失 |
| Snowflake | 完整 | 完整 | — | 缺失 |
| Encryption | 完整 | 完整 | — | 基础 |
| Encryptable | 完整 | 完整 | — | 基础 |
| Elasticsearch | 完整 | 完整 | — | 缺失 |
| 验证码 | 完整 | 完整 | — | 缺失 |
| 微信支付 | — | — | 需要验证 | 缺失 |
| 支付宝 | — | — | 需要验证 | 缺失 |
| SMS | — | — | 需要验证 | 缺失 |
| Push | — | — | 需要验证 | 缺失 |
| 地图/LBS | — | — | 需要验证 | 缺失 |

---

## 六、优化路线图

### 立即执行（本周）

1. **生成 Service 完整 .env.example** — 参照 admin，列出所有必需的环境变量
2. **修复 debug 硬编码** — 改为从 `APP_DEBUG` 环境变量读取
3. **Apidoc 密码改为环境变量** — 生产环境关闭文档
4. **为 Service 添加 RateLimit 中间件** — 保护登录/注册等敏感端点
5. **清理 Admin .env 重复 JWT 配置** — 统一变量命名规范，同步更新 .env.example

### 短期（2 周内）

6. **添加 CI/CD** — 创建 `.github/workflows/ci.yml`
7. **添加代码质量工具** — `composer require --dev phpstan/phpstan friendsofphp/php-cs-fixer`
8. **为 Service 添加 security.php 配置**
9. **完善 Service docker-compose.yml** — 添加 MySQL、Redis、ES 服务
10. **增强文件上传安全** — 验证 MIME 类型

### 中期（1 个月内）

11. **统一包版本** — 评估将 service 包升级到 v2
12. **增加 Controller 测试** — 为核心 API 端点编写集成测试
13. **提取共享 Flutter 代码** — 将 ApiService/AuthService 提取为共享包
14. **添加临时文件清理机制** — 定时清理 runtime/tmp/
15. **删除或实现 Service Security 占位中间件**

---

## 七、测试结果

```
=== Admin (PHPUnit 12.5.27) ===
Tests: 60, Assertions: 165
Time: 0.519s, Memory: 24.00 MB
Result: OK

=== Service (PHPUnit 12.5.27) ===
Tests: 21, Assertions: 36
Time: 0.836s, Memory: 24.00 MB
Result: OK

=== PHP 语法检查 ===
Admin  app/: 0 errors
Service app/: 0 errors
```

---

## 八、统计摘要

| 指标 | Admin | Service |
|------|-------|---------|
| 控制器 | 51 个 | 40 个 |
| 中间件 | 8 个 | 6 个 |
| 数据模型 | 35 个 | 40 个 |
| 配置文件 | 24 个 | 22 个 |
| PHP 总行数 | ~13,080 | ~15,792 |
| 前端应用 | Flutter Web (PC 管理后台) | Flutter App + WeChat 小程序 |
| 数据库迁移 | 8 个 SQL | — |

---

*报告生成: 2026-08-03 | 工具: Claude Code Audit*
