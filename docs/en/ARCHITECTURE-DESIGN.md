# Architecture Design
> **Languages**: [中文](../ARCHITECTURE-DESIGN.md) · [한국어](../ko/ARCHITECTURE-DESIGN.md) · [Русский](../ru/ARCHITECTURE-DESIGN.md) · [Deutsch](../de/ARCHITECTURE-DESIGN.md) · [Français](../fr/ARCHITECTURE-DESIGN.md) · [Español](../es/ARCHITECTURE-DESIGN.md) · [Português](../pt/ARCHITECTURE-DESIGN.md) · [हिन्दी](../hi/ARCHITECTURE-DESIGN.md) · [العربية](../ar/ARCHITECTURE-DESIGN.md) · [বাংলা](../bn/ARCHITECTURE-DESIGN.md) · [Bahasa Indonesia](../id/ARCHITECTURE-DESIGN.md) · [日本語](../ja/ARCHITECTURE-DESIGN.md)

## Layered Architecture

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

## Middleware Design

### Execution Chain

```
Cors → Security(31种攻击检测) → RateLimit → Auth(JWT+用户状态)
    → [TechnicianAuth(技师身份)] → [AdminPermission(RBAC)] → [OperationLog(8端来源)]
    → Controller
```

### Middleware Responsibilities

| Middleware | Scope | Function |
|------------|-------|----------|
| Cors | Global | OPTIONS preflight + CORS response headers |
| Security | Global | erikwang2013/security-php, 31 attack detections |
| RateLimit | Global | Redis sliding window + atomic Lua |
| Auth | Route group | JWT parsing + user existence/status validation |
| TechnicianAuth | Route group | Technician profile lookup + approved status validation |
| AdminAuth | Route group | Admin-side JWT authentication + blacklist |
| AdminPermission | Route group | RBAC permission check, Redis 60s cache |
| OperationLog | Route group | Operation log + automatic 8-platform source detection |

### Rate Limit Strategy

| Endpoint | Limit |
|----------|-------|
| Default | 60/min/IP |
| Login | 10/min |
| Register | 5/min |
| Captcha | 1/60s/phone number |

## Database Design Principles

### Primary Key Strategy

- All primary keys: BIGINT UNSIGNED NOT NULL, non-auto-increment
- Generated at the application layer by `erikwang2013/snowflake-php`
- Model: `$incrementing = false`, `$keyType = 'string'`

### Table Prefix

Unified `appointment_` prefix, configured in `config/database.php`. Models write the raw table name and the ORM adds the prefix automatically.

### Sensitive Field Encryption

Uses the `erikwang2013/encryptable` trait:

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

Encrypted fields use VARCHAR(500) length (encrypted data expands).

### Soft Deletes and Timestamps

- Eloquent SoftDeletes: `deleted_at` DATETIME DEFAULT NULL
- All tables include `created_at` + `updated_at`

## API ID Encryption/Decryption

### Request: decodeIds()

The frontend sends hashids-encoded IDs → the controller calls `$this->decodeIds($request->all())` to decode.

### Response: encodeIds()

IDs in DB query results → `BaseController::success()` automatically calls `encodeIds()` to encode → returns hashids strings.

### Rules

Recursively processes fields whose key is `id` or ends with `_id` in arrays.

## Security Design

### Defense in Depth

```
WAF → Cors → Security(31种检测) → RateLimit → Auth(JWT+状态)
    → [身份校验] → [RBAC] → Controller(Model加密) → 响应
```

### Authentication Security

- Password: bcrypt hash
- JWT: 7-day validity + refresh + blacklist
- Lockout: 5 failures → 15 minutes
- Concurrency: at most 3 tokens

### Data Security

- API layer: erikwang2013/encryption
- DB layer: erikwang2013/encryptable trait
- Logs: sensitive data never enters logs

### Operation Security

- erikwang2013/poster-php: verification before delete/review/withdraw
- Security middleware: XSS/SQL injection/CSRF/path traversal detection

## Elasticsearch Integration

`erikwang2013/webman-scout` auto-syncs models to ES:

```php
use Erikwang2013\WebmanScout\Searchable;

class Service extends Model
{
    use Searchable;
    public function searchableAs(): string { return 'appointment_services'; }
}
```

## Excel/PDF Export

- Excel: PhpSpreadsheet, sensitive fields auto-masked
- PDF: Dashboard panel visualization export

## 8-Platform Source Detection

OperationLog parses via User-Agent:

```
iPad → iPadOS / Mac → macOS / Windows → Windows
Linux → Linux / iPhone → ios / Android → android
HarmonyOS → harmonyOS / 其他 → web
```

## TDD Tests

| Project | Test Count | Status |
|---------|------------|--------|
| admin/ | 60 | ✅ Passed |
| service/ | 21 | ✅ Passed |
| Total | 81 | ✅ |

Coverage: refund rules / order status / Hashids / queue system / encryption / captcha
