# 安全审查报告 — 预约系统 (appointment-php)

**日期**: 2026-08-04
**审查范围**: service（预约服务系统）、admin（开放管理后台）
**PHP 版本**: 8.3.7
**框架**: webman v2

---

## 一、测试结果

| 测试项 | Service | Admin |
|--------|---------|-------|
| PHP 语法检查（全量） | 通过 | 通过 |
| PHPUnit 单元测试 | 59 tests / 165 assertions PASS | 59 tests / 165 assertions PASS |
| PHPStan 静态分析 | 未安装 (dev 依赖下载超时) | 未安装 (dev 依赖下载超时) |

---

## 二、安全防护层次总览

```
请求 → Nginx (安全头+敏感文件保护) → Cors (CORS+安全头) → SecurityMiddleware (31种攻击检测) → RateLimit (Redis滑动窗口) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    IP黑名单 (5次攻击/60s → 封禁15min)
                                                                                    账号锁定 (5次失败/15min → 锁定15min)
```

---

## 三、已修复的问题

### 3.1 Service CORS 缺少安全响应头 → 已修复
**文件**: `service/app/middleware/Cors.php`
- 新增 6 个安全头：X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- 现在与 admin 安全头配置一致

### 3.2 Service 缺少登录失败锁定 → 已修复
**文件**: `service/app/api/v1/controller/AuthController.php`
- `login()` 和 `loginByCode()` 方法新增 Redis 失败计数
- 5次失败/15分钟锁定 → HTTP 429
- Redis 故障时优雅降级

### 3.3 CORS Origin 硬编码 `*` → 已修复
**文件**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- 改为通过 `CORS_ALLOW_ORIGIN` 环境变量配置
- 留空默认 `*`（向后兼容）

### 3.4 Service 缺少 security-php 依赖 → 已修复
**操作**:
- 添加 `allow-plugins.erikwang2013/security-php` 到 composer.json
- 运行 `composer install --no-dev` 安装依赖
- 配置文件已发布到 `config/plugin/erikwang2013/security-php/app.php`
- CSRF Origin 检测器 (`csrf_origin`) 已启用 (block 模式)

### 3.5 Service Nginx 缺少 Permissions-Policy → 已修复
**文件**: `service/docs/nginx.conf`
- 添加 `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;`

### 3.6 生态配置补全 → 已修复
- `service/.env.example` 和 `admin/.env.example` 新增 `CORS_ALLOW_ORIGIN`
- `service/.env.docker` 和 `admin/.env.docker` 新增 `CORS_ALLOW_ORIGIN`

---

## 四、当前安全防护完整清单

### 4.1 WAF 层 — 31 种攻击检测器

| 模式 | 检测器 | 数量 |
|------|--------|------|
| **block** (拦截403) | XSS, SQL注入, 命令注入, 路径遍历, 文件上传, SSRF, XXE, 反序列化, LDAP注入, 邮件头注入, Open Redirect, JWT攻击, Host头攻击, Request Smuggling, GraphQL注入, XPATH注入, JNDI/Log4Shell, SSI注入, CSV注入, 数据泄露, Prototype Pollution, WebSocket劫持, CORS绕过, DNS Rebinding, HTTP方法校验, 请求体大小(10MB), Content-Type白名单, CSRF Origin | 28 |
| **log** (仅记录) | 响应头注入, SSTI, NoSQL注入 | 3 |

### 4.2 认证与授权

| 机制 | Service | Admin |
|------|---------|-------|
| JWT 认证 | Auth 中间件 | AdminAuth 中间件 |
| JWT 黑名单 | 退出登录时加入 | 退出+会话超限时加入 |
| RBAC 权限 | — | method.path 格式, Redis 60s 缓存 |
| 账号锁定 | 5次/15分钟 (Redis) | 5次/15分钟 (Redis) |
| 并发会话限制 | — | 最大 3 个 Token |
| 密码哈希 | bcrypt | bcrypt |

### 4.3 限流

| 路由 | Service | Admin |
|------|---------|-------|
| 默认 | 60 次/分钟/IP | 60 次/分钟/IP |
| 登录 | 10 次/分钟 | — |
| 注册 | 5 次/分钟 | — |
| 短信/忘记密码 | 5 次/分钟 | — |

### 4.4 数据安全

| 措施 | Service | Admin |
|------|---------|-------|
| 数据库字段加密 | AES-256-CBC (6 个模型) | AES-256-CBC |
| API 传输加密 | AES-256-CBC | AES-256-CBC |
| ID 混淆 (Hashids) | 所有对外 ID | 所有对外 ID |
| Snowflake ID | 非自增 BIGINT | 非自增 BIGINT |
| 敏感字段脱敏 | 手机号脱敏 | 导出数据脱敏 |

---

## 五、待处理建议

### 5.1 建议：security-php 存储改用 Redis（生产环境）
**当前**: 两个服务都使用 `file` 类型存储（本地 JSON 文件）
**风险**: 多实例部署时 IP 黑名单不共享，攻击者可切换实例绕过
**建议**: 生产环境将 `storage.type` 改为 `redis`

### 5.2 建议：Session Cookie 安全属性
**当前**: `secure: false`, `same_site: ''`
**风险**: Cookie 可通过 HTTP 传输，CSRF 防护减弱
**建议**: 生产环境设为 `secure: true`, `same_site: 'Lax'`

### 5.3 建议：安装 PHPStan 开发依赖
**当前**: `composer install --dev` 因网络超时失败
**操作**: `composer install --dev` 或 `composer require --dev phpstan/phpstan`

### 5.4 提醒：生产部署前修改所有密钥
`.env.docker` 中的占位符密钥必须在生产部署前替换为随机生成的值：
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## 六、文档产出

| 文档 | 路径 |
|------|------|
| Service 安全架构 | `service/docs/SECURITY.md` |
| Admin 安全架构 | `admin/docs/SECURITY.md` |
| 本审查报告 | `docs/SECURITY-AUDIT-REPORT.md` |

---

## 七、审查结论

**安全防护整体评级：良好**

- 纵深防御层次完整（Nginx → WAF → Rate Limit → Auth → RBAC）
- 31 种攻击检测器全局覆盖，28 种为拦截模式
- JWT + 黑名单 + 账号锁定 + IP 黑名单多层认证防护
- 数据层 AES-256-CBC 加密 + Hashids 混淆
- 已修复 service 端安全响应头缺失、登录锁定缺失、WAF 包缺失三个关键问题
- 建议项为生产环境配置优化，非安全漏洞

---

## 八、2026-08-26 修复轮（安全加固）

| 项 | 修复内容 |
|----|---------|
| 下单防篡改 | OrderController::store() 订单项价格一律以数据库记录为准（service→erik_service、product→erik_product），客户端价格不参与计算；未知 target_type 422；target_id 必须 hashid（raw id 解码为 0 → 422「商品不存在或已下架」）；拼团/秒杀价同以 DB 为准 |
| 秒杀扣库存统一 | 库存统一由 /api/order store() 事务内行锁扣减；SeckillController::buy 不再预扣库存（保留 Redis 活动锁 + client_token 幂等）；直接调 /api/order 带 seckill_id 同样扣库存 |
| 技师提现 | 申请时余额扣除在途（pending/approved）预留；审批转账前复核 settled−withdrawn−在途 ≥ 提现额；并发审批不会双打款 |
| 支付回调 | 微信回调 total_fee 与订单应付金额严格比对，不符拒绝；支付宝回调日志脱敏（不含 buyer_id/seller_id 等） |
| /install 防护 | 安装成功写 .install.lock，install 接口双重校验（文件锁 + isInstalled）；.gitignore 已忽略 .install.lock |
| 依赖收敛 | webman-scout 统一 2.0.5（service/admin）；新增 opensearch-project/opensearch-php ^2.6；dompdf/security-php/webman-database 精确版本锁定（去 "*" 通配符） |
| 工程 | 删除 service/app/common/StorageService.php（死代码）；admin/app/common/ 新增 TechnicianWithdrawalService/WechatPayService（admin 独立部署不依赖 service 代码）；两应用 phpstan.neon 修复可运行（php -d memory_limit=2G） |
