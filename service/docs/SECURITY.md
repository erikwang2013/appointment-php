# 安全架构设计 — 预约服务系统

Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

## 纵深防御层次

```
请求 → Nginx → Cors → SecurityMiddleware(31检测器) → RateLimit → ApiVersion → Auth → {Controller}
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

### JWT 认证 (Auth 中间件)
- Bearer token 从 Authorization 头提取
- 验证失败返回 401
- 每次请求验证用户状态

### 技师认证 (TechnicianAuth 中间件)
- 验证用户角色为 technician 且档案为 approved 状态

### 账号锁定（登录保护）
- Redis 计数：同一手机号连续 5 次登录失败 → 锁定 15 分钟（返回 429）
- 成功登录后自动清除失败计数
- 应用于 `login` 和 `loginByCode` 两个端点

## 4. 限流保护

### RateLimit 中间件（Redis 滑动窗口，Lua 原子化）
- 默认 60 次/分钟/IP/路由
- 登录：10 次/分钟
- 注册/忘记密码/短信：5 次/分钟
- Redis 故障时自动放行，超限返回 429 + Retry-After

## 5. 数据安全

### 数据库加密
- 敏感字段通过 `erikwang2013/encryptable` trait 自动加解密 (AES-256-CBC)
- 密钥独立于传输加密密钥
- 应用模型：User、UserAddress、TechnicianProfile、TechnicianWithdrawal、Store、TechnicianMemberNote

### ID 混淆
- 所有对外 API 返回的 ID 通过 Hashids 编码，防止 ID 枚举

### 密码安全
- `password_hash(PASSWORD_BCRYPT)` 单向哈希

## 6. API 安全

- `Route::disableDefaultRoute()` 关闭默认路由
- 版本通过请求头 `API-Version` 控制
- 支付回调不走认证中间件，签名由 SDK 验证

## 7. 部署安全

- 所有密钥通过环境变量注入（`.env` 不纳入版本控制）
- Nginx 安全配置参考 (`docs/nginx.conf`)
- Docker OPcache 开启
