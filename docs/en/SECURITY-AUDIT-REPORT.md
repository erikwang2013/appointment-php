# Security Audit Report — Appointment System (appointment-php)
> **Languages**: [中文](../SECURITY-AUDIT-REPORT.md)

**Date**: 2026-08-04
**Scope**: service (appointment service system), admin (open admin dashboard)
**PHP version**: 8.3.7
**Framework**: webman v2

---

## 1. Test Results

> Updated 2026-10-30: the current suite has 96 files / 675 cases (service 70 files / 525 cases, admin 26 files / 150 cases). The table below is the 2026-08-04 round, kept for historical comparison.

| Test item | Service | Admin |
|-----------|---------|-------|
| PHP syntax check (full) | Passed (2026-08-04) | Passed (2026-08-04) |
| PHPUnit unit tests | 59 tests / 165 assertions PASS (2026-08-04) | 59 tests / 165 assertions PASS (2026-08-04) |
| PHPStan static analysis | Not installed (dev dep download timeout, 2026-08-04) | Not installed (dev dep download timeout, 2026-08-04) |

---

## 2. Security Defense Layer Overview

```
请求 → Nginx (安全头+敏感文件保护) → Cors (CORS+安全头) → SecurityMiddleware (31种攻击检测) → RateLimit (Redis滑动窗口) → Auth (JWT) → Controller
                                                                                                   ↓
                                                                                    IP黑名单 (5次攻击/60s → 封禁15min)
                                                                                    账号锁定 (5次失败/15min → 锁定15min)
```

---

## 3. Fixed Issues

### 3.1 Service CORS missing security response headers → Fixed
**File**: `service/app/middleware/Cors.php`
- Added 6 security headers: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, Referrer-Policy, Permissions-Policy, CSP, X-Permitted-Cross-Domain-Policies
- Now consistent with admin security header config

### 3.2 Service missing login failure lockout → Fixed
**File**: `service/app/api/v1/controller/AuthController.php`
- Added Redis failure counting to `login()` and `loginByCode()`
- 5 failures / 15 minutes → HTTP 429
- Graceful degradation on Redis failure

### 3.3 CORS Origin hardcoded `*` → Fixed
**File**: `service/app/middleware/Cors.php`, `admin/app/middleware/Cors.php`
- Changed to configurable via the `CORS_ALLOW_ORIGIN` environment variable: accepts `*` or a comma-separated allowlist
- When empty, only same-origin is allowed; `*` is no longer returned (since 2026-10-30; previously empty defaulted to `*`)

### 3.4 Service missing security-php dependency → Fixed
**Actions**:
- Added `allow-plugins.erikwang2013/security-php` to composer.json
- Ran `composer install --no-dev` to install the dependency
- Config file published to `config/plugin/erikwang2013/security-php/app.php`
- CSRF Origin detector (`csrf_origin`) enabled (block mode)

### 3.5 Service Nginx missing Permissions-Policy → Fixed
**File**: `service/docs/nginx.conf`
- Added `add_header Permissions-Policy "geolocation=(), microphone=(), camera=()" always;`

### 3.6 Ecosystem config completion → Fixed
- Added `CORS_ALLOW_ORIGIN` to `service/.env.example` and `admin/.env.example`
- Added `CORS_ALLOW_ORIGIN` to `service/.env.docker` and `admin/.env.docker`

---

## 4. Current Complete Security Defense List

### 4.1 WAF Layer — 31 Attack Detectors

| Mode | Detectors | Count |
|------|-----------|-------|
| **block** (intercept 403) | XSS, SQL injection, command injection, path traversal, file upload, SSRF, XXE, deserialization, LDAP injection, email header injection, Open Redirect, JWT attacks, Host header attacks, Request Smuggling, GraphQL injection, XPATH injection, JNDI/Log4Shell, SSI injection, CSV injection, data leakage, Prototype Pollution, WebSocket hijacking, CORS bypass, DNS Rebinding, HTTP method validation, request body size (10MB), Content-Type whitelist, CSRF Origin | 28 |
| **log** (record only) | Response header injection, SSTI, NoSQL injection | 3 |

### 4.2 Authentication & Authorization

| Mechanism | Service | Admin |
|-----------|---------|-------|
| JWT authentication | Auth middleware | AdminAuth middleware |
| JWT blacklist | Added on logout | Added on logout + session limit exceeded |
| RBAC permissions | — | method.path format, Redis 60s cache |
| Account lockout | 5/15 min (Redis) | 5/15 min (Redis) |
| Concurrent session limit | — | Max 3 tokens |
| Password hashing | bcrypt | bcrypt |

### 4.3 Rate Limiting

| Route | Service | Admin |
|-------|---------|-------|
| Default | 60/min/IP | 60/min/IP |
| Login | 10/min | — |
| Register | 5/min | — |
| SMS/forgot password | 5/min | — |
| Captcha | 5/min (IP + route) | — |

### 4.4 Data Security

| Measure | Service | Admin |
|---------|---------|-------|
| Database field encryption | Encryptable per-field AES-256-CBC; User only `real_name` (`phone`/`wx_openid`/`wx_unionid` plaintext) | Same (sensitive AdminUser fields, etc.) |
| API transport encryption | AES-256-CBC | AES-256-CBC |
| ID obfuscation (Hashids) | All external IDs | All external IDs |
| Snowflake IDs | Non-auto-increment BIGINT | Non-auto-increment BIGINT |
| Sensitive field masking | Phone number masking | Export data masking |

---

## 5. Pending Recommendations

### 5.1 Recommendation: switch security-php storage to Redis (production)
**Current**: both services use `file`-type storage (local JSON files)
**Risk**: with multi-instance deployments the IP blacklist is not shared; attackers can bypass by switching instances
**Recommendation**: change `storage.type` to `redis` in production

### 5.2 Recommendation: Session Cookie security attributes
**Current**: `secure: false`, `same_site: ''`
**Risk**: cookie can be transmitted over HTTP, weakening CSRF protection
**Recommendation**: set `secure: true`, `same_site: 'Lax'` in production

### 5.3 Recommendation: install PHPStan dev dependency
**Current**: `composer install --dev` failed due to network timeout
**Actions**: `composer install --dev` or `composer require --dev phpstan/phpstan`

### 5.4 Reminder: change all keys before production deployment
Placeholder keys in `.env.docker` must be replaced with randomly generated values before production deployment:
- `JWT_SECRET_KEY`
- `HASHIDS_SALT`, `HASHIDS_ALT_SALT`
- `ENCRYPTION_KEY`, `ENCRYPTABLE_KEY`
- `DB_PASSWORD`

---

## 6. Documentation Deliverables

| Document | Path |
|----------|------|
| Service security architecture | `service/docs/SECURITY.md` |
| Admin security architecture | `admin/docs/SECURITY.md` |
| This audit report | `docs/SECURITY-AUDIT-REPORT.md` |

---

## 7. Audit Conclusion

**Overall security rating: Good**

- Complete defense-in-depth layers (Nginx → WAF → Rate Limit → Auth → RBAC)
- 31 attack detectors with global coverage, 28 in intercept mode
- Multi-layer authentication: JWT + blacklist + account lockout + IP blacklist
- Data layer per-field AES-256-CBC encryption (sensitive fields only) + Hashids obfuscation
- Fixed three critical issues on the service side: missing security response headers, missing login lockout, missing WAF package
- Recommendations are production config optimizations, not security vulnerabilities

---

## 8. 2026-08-26 Fix Round (Security Hardening)

| Item | Fix |
|------|-----|
| Order tamper prevention | OrderController::store() order item prices all use database records (service→appointment_service, product→appointment_product); client prices never participate in calculation; unknown target_type 422; target_id must be a hashid (raw id decodes to 0 → 422 "product does not exist or is offline"); group-buy prices likewise DB-based |
| Technician withdrawals | Balance reserved in-transit (pending/approved) on application; re-check settled−withdrawn−in-transit ≥ withdrawal amount before approval transfer; concurrent approvals cannot cause double payouts |
| Payment callbacks | WeChat callback total_fee strictly compared against the order's payable amount, mismatches rejected; Alipay callback logs masked (no buyer_id/seller_id, etc.) |
| /install protection | .install.lock written on successful install; install endpoint double-checks (file lock + isInstalled); .install.lock in .gitignore |
| Dependency convergence | webman-scout unified to 2.0.5 (service/admin); added opensearch-project/opensearch-php ^2.6; dompdf/security-php/webman-database pinned to exact versions (removed "*" wildcards) |
| Engineering | Deleted service/app/common/StorageService.php (dead code); admin/app/common/ added TechnicianWithdrawalService/WechatPayService (admin deploys independently without depending on service code); both apps' phpstan.neon fixed and runnable (php -d memory_limit=2G) |

---

## 9. 2026-10-30 Fix Round

This round tightens orders, cards, authentication, caching, and exports. Section 8 and earlier remain as historical records.

| Item | Fix |
|------|-----|
| Order price tamper prevention | The server re-reads the database by `target_id`; order item price, name, and cover always come from `appointment_service` / `appointment_product`, and client-supplied values are ignored. `target_id` must be a valid hashid; unknown `target_type` returns 422 |
| Order concurrency lock | Unified `order_lock:{id}` (Redis) covers pay, cancel, refund, payment callback, auto-cancel, verification, reschedule, and technician start/complete, preventing duplicate payment and state races |
| Pending-order auto-cancel | Pending orders now time out after **30 minutes** (was 15; `PENDING_TIMEOUT = 1800`); `AutoCancelTimer` cancels inside the lock and rolls back coupons/card uses |
| Order state machine | Successful payment lands on `paid`; both `paid` and `confirmed` can enter `serving`, then `completed`. There is no `reviewed` status — reviews are a separate record and do not change order state |
| Refund ratios | 100% within 15 minutes of ordering; 100% more than 6 hours before service; 90% within 6 hours before service; 0% once service has started, service time has passed, or status is `confirmed`/`serving`/`completed`; 100% for products or bookings without a service time |
| User card endpoints | Removed user-facing `POST /api/v1/marketing/cards/buy` and `POST /api/v1/marketing/gift-cards/store`; member-card purchase is `POST /api/v1/marketing/member-cards/buy`; users keep member-card list/detail/`my`; gift cards are admin-only |
| Admin auth semantics | Missing/invalid/disabled/refresh-token-as-access returns 401; disabled account on login returns 403; validation failure 422; login lockout 429; repeated initialization 410; refresh requires `token_type=refresh` and an active account |
| Cache clearing | Uses Redis `SCAN` with an allowed-prefix whitelist instead of `KEYS`, avoiding blocking and accidental deletion |
| Pagination | `per_page` is clamped to a safe range to prevent oversized page queries |
| Export | Fixed export recursion; retained CSV formula-injection protection (escape `=`, `+`, `-`, `@` prefixes); temporary files are cleaned hourly |
| CORS | Without `CORS_ALLOW_ORIGIN`, only same-origin is allowed; supports `*` or a comma-separated allowlist |
| Field encryption | `User` encrypts only `real_name`; `phone`/`wx_openid`/`wx_unionid` stay plaintext for login, deduplication, and unique-key exact lookups |
| Rate limiting | Captcha 5/min (IP + route); login 10/min, register/forgot-password 5/min, and default 60/min unchanged |
| Regression tests | Added `admin/tests/WechatPayServiceSyncTest.php`, `service/tests/Unit/AutoCancelLockTest.php`, and `service/tests/Unit/OrderPriceTamperTest.php`; the suite now has 96 test files / 675 cases |
| Documentation scope | The open-source edition no longer claims full-reduction, flash sale, points wheel, e-invoice, support tickets, queue/waitlist/e-signature, or user-to-user wallet/points transfer; those capabilities are marked commercial-only in the README comparison table |
