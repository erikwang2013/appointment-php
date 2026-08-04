# 安全架构设计 — 开放管理后台

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 纵深防御层次

```
请求 → Nginx → Cors → Locale → SecurityMiddleware(31检测器) → RateLimit → {路由中间件} → Controller

/admin/*: + AdminAuth → AdminPermission → OperationLog
/api/*:   + ApiVersion
```

## 1. 传输层安全

### CORS + 安全响应头
- `Cors` 全局中间件注入安全响应头
- `X-Content-Type-Options: nosniff` — 禁止 MIME 类型嗅探
- `X-Frame-Options: DENY` — 禁止页面嵌入 iframe
- `X-XSS-Protection: 1; mode=block` — 浏览器 XSS 过滤器
- `Referrer-Policy: strict-origin-when-cross-origin` — 限制 Referer 信息
- `Permissions-Policy: camera=(), microphone=(), geolocation=()` — 限制设备 API
- `Content-Security-Policy` — 内容安全策略
- `X-Permitted-Cross-Domain-Policies: none` — 禁止跨域策略文件
- 跨域来源通过 `CORS_ALLOW_ORIGIN` 环境变量配置

### 传输加密
- `erikwang2013/encryption` (AES-256-CBC) — API 敏感数据加解密
- JWT (HS256) — 无状态认证，2 小时有效 + 14 天刷新

## 2. WAF 层

`erikwang2013/security-php` — 31 种攻击检测器

### block 模式（拦截，返回 403）
XSS、SQL注入、命令注入、路径遍历、文件上传、SSRF、XXE、反序列化、LDAP注入、邮件头注入、Open Redirect、JWT攻击、Host头攻击、Request Smuggling、GraphQL注入、XPATH注入、JNDI/Log4Shell、SSI注入、CSV注入、数据泄露、Prototype Pollution、WebSocket劫持、CORS绕过、DNS Rebinding、HTTP方法校验、请求体大小限制(10MB)、Content-Type白名单、CSRF Origin校验

### log 模式（仅记录）
响应头注入（避免 textarea 误报）、SSTI（避免前端模板误报）、NoSQL注入（避免 Shell 变量误报）

### IP 攻击升级黑名单
同一 IP 在 60 秒内触发 5 次攻击检测 → 自动封禁 15 分钟

## 3. 认证与授权

### JWT 认证 (AdminAuth 中间件)
- Bearer token 从 Authorization 头提取
- JWT 黑名单检查（退出登录时 token 加入黑名单）
- 验证失败返回 401

### RBAC 权限校验 (AdminPermission 中间件)
- `method.path` 格式权限 slug（如 `get.admin/user`）
- Redis 缓存用户权限列表（TTL 60s）
- 超级管理员 `*` 通配符跳过校验
- 无权限返回 403

### 账号锁定
- Redis 计数：同一用户名连续 5 次登录失败 → 锁定 15 分钟（返回 429）
- 成功后清除失败计数

### 并发会话限制
- 同一用户最多 3 个有效 Token
- 超限时最旧 Token 自动加入黑名单

## 4. 限流保护

### RateLimit 中间件（Redis 滑动窗口，Lua 原子化）
- 默认 60 次/分钟/IP/路由
- Redis 故障时自动放行
- 超限返回 429 + X-RateLimit-* + Retry-After 响应头

## 5. 数据安全

### 数据库加密
- 敏感字段通过 `erikwang2013/encryptable` trait 自动加解密
- 加密算法：AES-256-CBC

### ID 混淆
- 对外 API 返回的 ID 通过 Hashids 编码为短字符串

### 文件上传安全
- MIME 类型白名单校验
- 可执行文件类型黑名单拦截

### 导出数据脱敏
- Excel/PDF 导出时敏感字段自动脱敏

### 密码安全
- `password_hash(PASSWORD_BCRYPT)` 单向哈希
- 密码长度 6-32 位

## 6. API 安全

- `Route::disableDefaultRoute()` 关闭默认路由
- 版本通过请求头 `API-Version` 控制
- security.txt RFC 9116 端点 (`.well-known/security.txt`)

## 7. 审计与监控

### 操作日志 (OperationLog 中间件)
- 自动记录所有管理操作
- 8 平台来源端自动检测（Web/iOS/Android/Windows/macOS/Linux/HarmonyOS/小程序）

### 健康检查与监控
- `GET /health` 端点
- Prometheus 指标 (`GET /metrics`)

### IP 黑名单管理
- 管理后台可手动封禁/解封 IP

## 8. 部署安全

- 所有密钥通过环境变量注入（`.env` 不纳入版本控制）
- Nginx 安全参考配置 (`docs/nginx-security.conf`)
- CI/CD：PHP 语法检查 + PHPUnit + Flutter analyze
- Docker OPcache + 最小化基础镜像
