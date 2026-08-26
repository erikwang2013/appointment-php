# 架构设计
> **多语言**：[English](en/ARCHITECTURE-DESIGN.md) · [한국어](ko/ARCHITECTURE-DESIGN.md) · [Русский](ru/ARCHITECTURE-DESIGN.md) · [Deutsch](de/ARCHITECTURE-DESIGN.md) · [Français](fr/ARCHITECTURE-DESIGN.md) · [Español](es/ARCHITECTURE-DESIGN.md) · [Português](pt/ARCHITECTURE-DESIGN.md) · [हिन्दी](hi/ARCHITECTURE-DESIGN.md) · [العربية](ar/ARCHITECTURE-DESIGN.md) · [বাংলা](bn/ARCHITECTURE-DESIGN.md) · [Bahasa Indonesia](id/ARCHITECTURE-DESIGN.md) · [日本語](ja/ARCHITECTURE-DESIGN.md)

## 分层架构

```
┌─────────────────────────────────────────┐
│              表现层 (Presentation)        │
│  微信小程序 / Flutter APP / Flutter Web   │
├─────────────────────────────────────────┤
│              路由层 (Route)               │
│  config/route.php — 路由分组 + 中间件绑定  │
├─────────────────────────────────────────┤
│            中间件层 (Middleware)           │
│  Cors → Security → RateLimit → Auth      │
│  → TechnicianAuth → OperationLog         │
├─────────────────────────────────────────┤
│            控制器层 (Controller)           │
│  BaseController → 各业务Controller        │
├─────────────────────────────────────────┤
│             服务层 (Service)              │
│  common/ — Snowflake/Hashids/Encryption  │
├─────────────────────────────────────────┤
│             模型层 (Model)                │
│  Eloquent ORM + Encryptable + Scout      │
├─────────────────────────────────────────┤
│              数据层 (Data)                │
│  MySQL / Redis / Elasticsearch           │
└─────────────────────────────────────────┘
```

## 中间件设计

### 执行链

```
Cors → Security(31种攻击检测) → RateLimit → Auth(JWT+用户状态)
    → [TechnicianAuth(技师身份)] → [AdminPermission(RBAC)] → [OperationLog(8端来源)]
    → Controller
```

### 中间件职责

| 中间件 | 作用域 | 功能 |
|--------|--------|------|
| Cors | 全局 | OPTIONS预检 + CORS响应头 |
| Security | 全局 | erikwang2013/security-php，31种攻击检测 |
| RateLimit | 全局 | Redis滑动窗口+Lua原子化 |
| Auth | 路由组 | JWT解析 + 用户存在性/状态校验 |
| TechnicianAuth | 路由组 | 技师档案查询 + approved状态校验 |
| AdminAuth | 路由组 | Admin端JWT认证 + 黑名单 |
| AdminPermission | 路由组 | RBAC权限校验，Redis 60s缓存 |
| OperationLog | 路由组 | 操作日志 + 8端来源自动检测 |

### 限流策略

| 接口 | 限制 |
|------|------|
| 默认 | 60次/分钟/IP |
| 登录 | 10次/分钟 |
| 注册 | 5次/分钟 |
| 验证码 | 1次/60秒/手机号 |

## 数据库设计原则

### 主键策略

- 所有主键：BIGINT UNSIGNED NOT NULL，非自增
- 由 `erikwang2013/snowflake-php` 在应用层生成
- Model: `$incrementing = false`, `$keyType = 'string'`

### 表前缀

统一 `erik_` 前缀，`config/database.php` 配置。Model写原始表名，ORM自动添加前缀。

### 敏感字段加密

使用 `erikwang2013/encryptable` trait：

```php
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use Encryptable;
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];
}
```

加密字段VARCHAR长度设为500（加密数据膨胀）。

### 软删除与时间戳

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- 所有表含 `created_at` + `updated_at`

## API ID加解密机制

### 请求：decodeIds()

前端发送hashids编码的ID → 控制器调用 `$this->decodeIds($request->all())` 解码。

### 响应：encodeIds()

DB查询结果的ID → `BaseController::success()` 自动调用 `encodeIds()` 编码 → 返回hashids字符串。

### 规则

递归处理数组中键名为 `id` 或以 `_id` 结尾的字段。

## 安全设计

### 纵深防御

```
WAF → Cors → Security(31种检测) → RateLimit → Auth(JWT+状态)
    → [身份校验] → [RBAC] → Controller(Model加密) → 响应
```

### 认证安全

- 密码：bcrypt哈希
- JWT：7天有效期 + 刷新 + 黑名单
- 锁定：5次失败→15分钟
- 并发：最多3个Token

### 数据安全

- API层：erikwang2013/encryption
- DB层：erikwang2013/encryptable trait
- 日志：敏感数据不入日志

### 操作安全

- erikwang2013/poster-php：删除/审核/提现前验证
- Security中间件：XSS/SQL注入/CSRF/路径遍历检测

## Elasticsearch集成

`erikwang2013/webman-scout` 自动同步模型到ES：

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'erik_services'; }
}
```

## Excel/PDF导出

- Excel：PhpSpreadsheet，敏感字段自动脱敏
- PDF：Dashboard面板可视化导出

## 8端来源检测

OperationLog通过User-Agent解析：

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / 其他 → web
```


## TDD 测试

| 项目 | 测试数 | 状态 |
|------|--------|------|
| admin/ | 60 | ✅ 通过 |
| service/ | 21 | ✅ 通过 |
| 合计 | 81 | ✅ |

测试覆盖: 退款规则 / 订单状态 / Hashids / 排队系统 / 加密 / 验证码
