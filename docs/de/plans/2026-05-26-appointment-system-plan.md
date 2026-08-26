# Buchungsservice-System — Implementierungsplan

> Deutsche Übersetzung · Original: [中文](../../superpowers/plans/2026-05-26-appointment-system-plan.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Ziel:** Aufbau eines Drei-Endpunkte-Buchungsservicesystems: Benutzer-WeChat-MiniProgramm + Flutter APP (Identitätswechsel im selben Konto), PC-Verwaltungsbackend-Erweiterung.

**Architektur:** `admin/` (Verwaltungsbackend-API) + `service/` (Business-API), beide Dienste teilen sich MySQL/Redis/ES. Benutzer-MiniProgramm und APP funktional äquivalent, einheitliches Konto unterstützt Kunden-/Techniker-Identitätswechsel.

**Tech-Stack:** PHP 8.3 + webman v2, MySQL 8.0, Redis, Elasticsearch, nativem WeChat-MiniProgramm, Flutter 3.x (GetX + Dio)

**Technische Standards:**
- Primärschlüssel: `erikwang2013/snowflake-php` BIGINT nicht autoinkrementierend
- API-ID: `erikwang2013/hashids` Ver-/Entschlüsselung
- JWT: `erikwang2013/jwt-webman`
- Länderflaggen: `erikwang2013/season`
- API-sensible Daten: `erikwang2013/encryption`
- DB-sensible Felder: `erikwang2013/encryptable`
- ES-Suche: `erikwang2013/webman-scout`
- Sicherheitsprüfung: `erikwang2013/security-php`
- Verifizierung sensibler Operationen: `erikwang2013/poster-php`
- Copyright: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz`
- Globale Funktionen ohne `\`, Import über `use`
- Konfigurationsdateien mit chinesischen Kommentaren
- Excel-Export + Panel-Visualisierung + PDF-Panel-Export
- Operations-Quell-Endpunkte: web, iPadOS, macOS, Windows, Linux, ios, android, harmonyOS

**Design-Spezifikation:** `docs/superpowers/specs/2026-05-26-appointment-system-design.md`  
**Projektstruktur:** `docs/STRUCTURE.md`  
**Datenbankmigration:** `admin/database/migrations/2026_05_26_000003_appointment_business_tables.sql`

---

## Phase 0: Grundlage — service/-Projektskelett

### Task 0.1: service/-Projektkonfiguration initialisieren

**Dateien:**
- Create: `service/composer.json`
- Create: `service/.env` / `service/.env.example`
- Create: `service/config/app.php`
- Create: `service/config/database.php`
- Create: `service/config/route.php`
- Create: `service/config/middleware.php`
- Create: `service/config/jwt.php`
- Create: `service/config/hashids.php`
- Create: `service/config/snowflake.php`
- Create: `service/config/encryption.php`
- Create: `service/config/encryptable.php`
- Create: `service/config/scout.php`
- Create: `service/config/security.php`
- Create: `service/config/poster.php`
- Create: `service/config/season.php`
- Create: `service/config/autoload.php`
- Create: `service/config/bootstrap.php`
- Create: `service/config/container.php`
- Create: `service/config/dependence.php`
- Create: `service/config/exception.php`
- Create: `service/config/log.php`
- Create: `service/config/process.php`
- Create: `service/config/server.php`
- Create: `service/config/session.php`
- Create: `service/config/static.php`
- Create: `service/config/translation.php`
- Create: `service/config/view.php`

- [ ] **Schritt 1: composer.json erstellen**

```json
{
    "name": "erik/appointment-service",
    "description": "预约服务系统 - 业务API服务",
    "type": "project",
    "license": "proprietary",
    "require": {
        "php": ">=8.3",
        "workerman/webman": "^2.0",
        "erikwang2013/snowflake-php": "^1.0",
        "erikwang2013/hashids": "^1.0",
        "erikwang2013/jwt-webman": "^1.0",
        "erikwang2013/encryption": "^1.0",
        "erikwang2013/encryptable": "^1.0",
        "erikwang2013/webman-scout": "^1.0",
        "erikwang2013/security-php": "^1.0",
        "erikwang2013/poster-php": "^1.0",
        "erikwang2013/season": "^1.0",
        "illuminate/database": "^11.0",
        "illuminate/events": "^11.0",
        "illuminate/redis": "^11.0",
        "monolog/monolog": "^3.0"
    },
    "autoload": {
        "psr-4": {
            "app\\": "app/"
        },
        "files": [
            "app/functions.php"
        ]
    }
}
```

- [ ] **Schritt 2: Abhängigkeiten installieren**

```bash
cd /home/wwwroot/appointment-php/service && composer install
```

- [ ] **Schritt 3: config/database.php erstellen** — teilt sich dieselbe MySQL mit admin

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
// 数据库配置 - 与 admin/ 共享同一 MySQL 实例

return [
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'driver'    => 'mysql',
            'host'      => env('DB_HOST', '127.0.0.1'),
            'port'      => env('DB_PORT', '3306'),
            'database'  => env('DB_DATABASE', 'appointment'),
            'username'  => env('DB_USERNAME', 'root'),
            'password'  => env('DB_PASSWORD', ''),
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix'    => 'erik_',       // 表前缀
            'strict'    => true,
        ],
    ],
];
```

- [ ] **Schritt 4: config/route.php erstellen**

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
// 路由配置 - 业务API，按功能域分组

use Webman\Route;

// 公开路由（无需认证）
Route::group('/api', function () {
    // 认证: 注册/登录/忘记密码/游客/身份切换
    Route::post('/auth/register', [app\api\AuthController::class, 'register']);
    Route::post('/auth/login', [app\api\AuthController::class, 'login']);
    Route::post('/auth/login-by-code', [app\api\AuthController::class, 'loginByCode']);
    Route::post('/auth/forget-password', [app\api\AuthController::class, 'forgetPassword']);
    Route::post('/auth/refresh', [app\api\AuthController::class, 'refresh']);
    // 验证码
    Route::post('/captcha/send', [app\api\CaptchaController::class, 'send']);
    // 微信回调
    Route::any('/wechat/notify', [app\api\WechatController::class, 'notify']);
    // 公开内容
    Route::get('/common/agreement/{type}', [app\api\CommonController::class, 'agreement']);
    Route::get('/common/about', [app\api\CommonController::class, 'about']);
    Route::get('/common/version', [app\api\CommonController::class, 'version']);
    // 服务浏览
    Route::get('/service/categories', [app\service\CategoryController::class, 'index']);
    Route::get('/service/items', [app\service\ItemController::class, 'index']);
    Route::get('/service/items/{id}', [app\service\ItemController::class, 'show']);
    Route::get('/service/search', [app\service\SearchController::class, 'index']);
    Route::get('/service/stores', [app\service\StoreController::class, 'index']);
    Route::get('/service/stores/{id}', [app\service\StoreController::class, 'show']);
    // 技师浏览
    Route::get('/technician/list', [app\technician\ProfileController::class, 'publicList']);
    Route::get('/technician/detail/{id}', [app\technician\ProfileController::class, 'publicShow']);
    // 内容
    Route::get('/content/banners', [app\content\BannerController::class, 'index']);
    Route::get('/content/announcements', [app\content\AnnouncementController::class, 'index']);
    // LBS
    Route::get('/lbs/cities', [app\lbs\LocationController::class, 'cities']);
    Route::get('/lbs/nearby-stores', [app\lbs\LocationController::class, 'nearbyStores']);
})->middleware([app\middleware\Cors::class]);

// 用户路由（需JWT认证）
Route::group('/api/user', function () {
    Route::get('/profile', [app\user\ProfileController::class, 'show']);
    Route::put('/profile', [app\user\ProfileController::class, 'update']);
    Route::put('/password', [app\user\ProfileController::class, 'changePassword']);
    Route::put('/phone', [app\user\ProfileController::class, 'changePhone']);
    Route::post('/logout', [app\user\ProfileController::class, 'logout']);
    Route::post('/cancel-account', [app\user\ProfileController::class, 'cancelAccount']);
    Route::resource('/addresses', app\user\AddressController::class);
    Route::get('/favorites', [app\user\FavoriteController::class, 'index']);
    Route::post('/favorites', [app\user\FavoriteController::class, 'store']);
    Route::delete('/favorites/{id}', [app\user\FavoriteController::class, 'destroy']);
    Route::post('/feedback', [app\user\FeedbackController::class, 'store']);
    Route::get('/referrals', [app\user\ReferralController::class, 'index']);
    Route::get('/referral-qrcode', [app\user\ReferralController::class, 'qrcode']);
    Route::get('/referred-users', [app\user\ReferralController::class, 'referredUsers']);
    Route::post('/switch-role', [app\api\AuthController::class, 'switchRole']);
})->middleware([app\middleware\Cors::class, app\middleware\Auth::class]);

// 技师路由（需JWT认证 + 技师身份）
Route::group('/api/technician', function () {
    Route::get('/profile', [app\technician\ProfileController::class, 'show']);
    Route::put('/profile', [app\technician\ProfileController::class, 'update']);
    Route::post('/apply', [app\technician\ProfileController::class, 'apply']);
    Route::get('/schedules', [app\technician\ScheduleController::class, 'index']);
    Route::post('/schedules', [app\technician\ScheduleController::class, 'store']);
    Route::put('/schedules/{id}', [app\technician\ScheduleController::class, 'update']);
    Route::get('/orders', [app\technician\OrderController::class, 'index']);
    Route::get('/orders/{id}', [app\technician\OrderController::class, 'show']);
    Route::post('/orders/{id}/verify', [app\technician\OrderController::class, 'verify']);
    Route::post('/orders/{id}/start-service', [app\technician\OrderController::class, 'startService']);
    Route::get('/members', [app\technician\MemberController::class, 'index']);
    Route::get('/members/{id}', [app\technician\MemberController::class, 'show']);
    Route::post('/members/{id}/note', [app\technician\MemberController::class, 'addNote']);
    Route::get('/earnings', [app\technician\EarningsController::class, 'index']);
    Route::get('/earnings/summary', [app\technician\EarningsController::class, 'summary']);
    Route::post('/withdrawal', [app\technician\WithdrawalController::class, 'store']);
    Route::get('/withdrawals', [app\technician\WithdrawalController::class, 'index']);
    Route::post('/attendance/check-in', [app\technician\AttendanceController::class, 'checkIn']);
    Route::post('/attendance/check-out', [app\technician\AttendanceController::class, 'checkOut']);
    Route::post('/attendance/upload-clean', [app\technician\AttendanceController::class, 'uploadClean']);
})->middleware([app\middleware\Cors::class, app\middleware\Auth::class, app\middleware\TechnicianAuth::class]);

// 订单路由（需JWT认证）
Route::group('/api/order', function () {
    Route::get('/cart', [app\order\CartController::class, 'index']);
    Route::post('/cart', [app\order\CartController::class, 'store']);
    Route::put('/cart/{id}', [app\order\CartController::class, 'update']);
    Route::delete('/cart/{id}', [app\order\CartController::class, 'destroy']);
    Route::post('/submit', [app\order\OrderController::class, 'submit']);
    Route::get('/list', [app\order\OrderController::class, 'index']);
    Route::get('/detail/{id}', [app\order\OrderController::class, 'show']);
    Route::post('/cancel/{id}', [app\order\OrderController::class, 'cancel']);
    Route::post('/confirm-receipt/{id}', [app\order\OrderController::class, 'confirmReceipt']);
    Route::post('/pay/{id}', [app\order\PaymentController::class, 'pay']);
    Route::get('/payment-status/{id}', [app\order\PaymentController::class, 'status']);
    Route::post('/refund/{id}', [app\order\PaymentController::class, 'refund']);
    Route::get('/verification-code/{id}', [app\order\VerificationController::class, 'getCode']);
    Route::post('/self-verify/{id}', [app\order\VerificationController::class, 'selfVerify']);
    Route::post('/review/{id}', [app\order\ReviewController::class, 'store']);
    Route::get('/reviews/{technician_id}', [app\order\ReviewController::class, 'byTechnician']);
})->middleware([app\middleware\Cors::class, app\middleware\Auth::class]);

// 营销路由（需JWT认证）
Route::group('/api/marketing', function () {
    Route::get('/coupons', [app\marketing\CouponController::class, 'index']);
    Route::post('/coupons/receive', [app\marketing\CouponController::class, 'receive']);
    Route::get('/member-cards', [app\marketing\MemberCardController::class, 'index']);
    Route::get('/my-member-cards', [app\marketing\MemberCardController::class, 'myCards']);
    Route::get('/member-card-usage', [app\marketing\MemberCardController::class, 'usageHistory']);
    Route::get('/points', [app\marketing\PointsController::class, 'index']);
    Route::post('/points/exchange', [app\marketing\PointsController::class, 'exchange']);
    Route::post('/gift-card/redeem', [app\marketing\GiftCardController::class, 'redeem']);
    Route::get('/gift-cards', [app\marketing\GiftCardController::class, 'index']);
})->middleware([app\middleware\Cors::class, app\middleware\Auth::class]);

// 通知路由
Route::group('/api/notification', function () {
    Route::get('/list', [app\content\NotificationController::class, 'index']);
    Route::get('/unread-count', [app\content\NotificationController::class, 'unreadCount']);
    Route::put('/{id}/read', [app\content\NotificationController::class, 'markRead']);
    Route::put('/read-all', [app\content\NotificationController::class, 'markAllRead']);
})->middleware([app\middleware\Cors::class, app\middleware\Auth::class]);
```

- [ ] **Schritt 5: config/middleware.php erstellen**

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
// 中间件配置 - 全局中间件注册

return [
    '' => [
        app\middleware\Cors::class,
        app\middleware\Security::class,
    ],
];
```

- [ ] **Schritt 6: Gemeinsame Konfigurationsdateien aus admin/config/ kopieren**

Kopiere `jwt.php`, `hashids.php`, `snowflake.php`, `encryption.php`, `encryptable.php`, `scout.php`, `season.php`, `poster.php` nach `service/config/` und ergänze Copyright-Hinweis und chinesische Kommentare.

- [ ] **Schritt 7: app/functions.php erstellen**

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
// 全局辅助函数

// 生成订单编号: 年月日时分秒 + 4位随机数
function generate_order_no(): string
{
    return date('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}

// 生成财务单号
function generate_finance_no(string $prefix = 'FN'): string
{
    return $prefix . date('YmdHis') . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
}
```

- [ ] **Schritt 8: app/middleware/Cors.php erstellen**

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class Cors implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $response = $request->method() === 'OPTIONS' ? response('', 204) : $handler($request);
        $response->withHeaders([
            'Access-Control-Allow-Origin'      => '*',
            'Access-Control-Allow-Methods'     => 'GET,POST,PUT,DELETE,OPTIONS',
            'Access-Control-Allow-Headers'     => 'Content-Type,Authorization,API-Version',
            'Access-Control-Max-Age'           => '86400',
        ]);
        return $response;
    }
}
```

- [ ] **Schritt 9: Security-Middleware erstellen**

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
// Security 中间件 - erikwang2013/security-php 攻击检测

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class Security implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        return $handler($request);
    }
}
```

- [ ] **Schritt 10: Auth-Middleware erstellen**

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
// JWT 认证中间件 - erikwang2013/jwt-webman

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use Erikwang2013\JwtWebman\Jwt;

class Auth implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $token = str_replace('Bearer ', '', $request->header('Authorization', ''));
        if (empty($token)) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        try {
            $payload = Jwt::decode($token);
            $request->user_id = $payload['user_id'] ?? 0;
        } catch (\Exception $e) {
            return json(['code' => 401, 'message' => '登录已过期，请重新登录', 'data' => null]);
        }
        return $handler($request);
    }
}
```

- [ ] **Schritt 11: TechnicianAuth-Middleware erstellen**

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
// 技师身份校验中间件

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;

class TechnicianAuth implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        $userId = $request->user_id ?? 0;
        if (!$userId) {
            return json(['code' => 401, 'message' => '请先登录', 'data' => null]);
        }
        $technician = \app\model\TechnicianProfile::where('user_id', $userId)
            ->where('status', 'approved')->first();
        if (!$technician) {
            return json(['code' => 403, 'message' => '需要技师身份，请先申请入驻', 'data' => null]);
        }
        $request->technician_id = $technician->id;
        return $handler($request);
    }
}
```

- [ ] **Schritt 12: BaseController erstellen** — einheitliche Antwort + hashids Ver-/Entschlüsselung

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
// 基础控制器 - 统一响应格式，所有ID通过 erikwang2013/hashids 加解密

namespace app\common;

use Erikwang2013\Hashids\Hashids;

class BaseController
{
    protected function success(mixed $data = null, string $message = '操作成功'): \Webman\Http\Response
    {
        return json(['code' => 0, 'message' => $message, 'data' => $this->encodeIds($data)]);
    }

    protected function error(string $message = '操作失败', int $code = 1, mixed $data = null): \Webman\Http\Response
    {
        return json(['code' => $code, 'message' => $message, 'data' => $data]);
    }

    protected function paginate(array $paginator): \Webman\Http\Response
    {
        $paginator['data'] = $this->encodeIds($paginator['data']);
        return json([
            'code' => 0, 'message' => 'success',
            'data' => $paginator['data'],
            'meta' => [
                'current_page' => $paginator['current_page'] ?? 1,
                'per_page'     => $paginator['per_page'] ?? 15,
                'total'        => $paginator['total'] ?? 0,
                'last_page'    => $paginator['last_page'] ?? 1,
            ],
        ]);
    }

    // 对输出数据中的 id 字段进行 hashids 编码
    protected function encodeIds(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($key === 'id' || str_ends_with((string)$key, '_id')) {
                    if (is_numeric($value) && $value > 0) {
                        $data[$key] = Hashids::encode($value);
                    }
                } elseif (is_array($value)) {
                    $data[$key] = $this->encodeIds($value);
                }
            }
        }
        return $data;
    }

    // 对输入数据中的 id 字段进行 hashids 解码
    protected function decodeIds(mixed $data): mixed
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                if ($key === 'id' || str_ends_with((string)$key, '_id')) {
                    if (is_string($value) && !empty($value)) {
                        $decoded = Hashids::decode($value);
                        $data[$key] = $decoded[0] ?? 0;
                    }
                } elseif (is_array($value)) {
                    $data[$key] = $this->decodeIds($value);
                }
            }
        }
        return $data;
    }

    protected function decodeId(string $hashid): int
    {
        $decoded = Hashids::decode($hashid);
        return $decoded[0] ?? 0;
    }
}
```

- [ ] **Schritt 13: Commit**

```bash
git add service/
git commit -m "feat(service): initialize project skeleton with configs, middleware, base controller"
```

---

## Phase 1: Benutzer-Authentifizierung und Identitätsverwaltung

### Task 1.1: User-Modell erstellen

- Create: `service/app/model/User.php`

```php
<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

namespace app\model;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Erikwang2013\Encryptable\Encryptable;

class User extends Model
{
    use SoftDeletes, Encryptable;

    protected $table = 'erik_user';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    // 数据库敏感字段加密（erikwang2013/encryptable）
    protected array $encryptable = [
        'phone', 'wx_openid', 'wx_unionid', 'real_name',
    ];

    protected $fillable = [
        'phone', 'password', 'wx_openid', 'wx_unionid', 'avatar', 'nickname',
        'real_name', 'gender', 'user_type', 'active_role', 'referral_code',
        'referrer_id', 'status', 'last_login_at', 'last_login_ip',
    ];

    protected $hidden = ['password', 'wx_openid', 'wx_unionid', 'deleted_at'];

    public function technicianProfile()
    {
        return $this->hasOne(TechnicianProfile::class, 'user_id');
    }
}
```

### Task 1.2: AuthController erstellen

- Create: `service/app/api/AuthController.php`
- Implementieren: Telefonnummer-Registrierung (inkl. Empfehlungscode), Passwort-Login, Verifizierungscode-Login, Passwort vergessen, Identitätswechsel, WeChat-Login (Ablauf zur Telefonnummer-Bindung vorgesehen)
- Gastmodus: vom Frontend gesteuert, ohne Login browsen, bei Bestellung JWT-Prüfung

### Task 1.3: Controller des user-Moduls erstellen

- Create: `service/app/user/ProfileController.php`
- Create: `service/app/user/AddressController.php`
- Create: `service/app/user/FavoriteController.php`
- Create: `service/app/user/FeedbackController.php`
- Create: `service/app/user/ReferralController.php`

---

## Phase 2: Leistungen und Filialen

- Create: `service/app/model/ServiceCategory.php`
- Create: `service/app/model/Service.php` (mit webman-scout-ES-Indexkonfiguration)
- Create: `service/app/model/Product.php`
- Create: `service/app/model/Store.php`
- Create: `service/app/service/CategoryController.php`
- Create: `service/app/service/ItemController.php`
- Create: `service/app/service/SearchController.php` (ES-Suche)
- Create: `service/app/service/StoreController.php`

---

## Phase 3: Bestellungen und Zahlung

- Create: `service/app/model/Order.php` + OrderItem/Payment/Refund/Review/Verification
- Create: `service/app/order/CartController.php`
- Create: `service/app/order/OrderController.php` — beim Bestellen Techniker per Redis SETNX 3 Minuten sperren
- Create: `service/app/order/PaymentController.php` (WeChat-Zahlung vorgesehen)
- Create: `service/app/order/VerificationController.php` (QR-Code-Verifizierung)
- Create: `service/app/order/ReviewController.php`
- Rückerstattungsregeln: vierstufige Anteile 100 %/90 %/80 %/0 %

---

## Phase 4: Technikermodul

- Create: `service/app/model/TechnicianProfile.php` + Schedule/Service/Earnings/Withdrawal/Attendance/MemberNote
- Create: `service/app/technician/ProfileController.php` (Aufnahmeantrag + Prüfung)
- Create: `service/app/technician/ScheduleController.php`
- Create: `service/app/technician/OrderController.php`
- Create: `service/app/technician/MemberController.php`
- Create: `service/app/technician/EarningsController.php`
- Create: `service/app/technician/WithdrawalController.php` (jeden Monat am 20./T+1)
- Create: `service/app/technician/AttendanceController.php`

---

## Phase 5: Marketingmodul

- Create: `service/app/model/Coupon.php` + UserCoupon/MemberCard/UserMemberCard/MemberCardUsage/Points/GiftCard/Referral
- Create: `service/app/marketing/CouponController.php` (automatische Gutscheinausgabe bei Neukunden-Registrierung)
- Create: `service/app/marketing/MemberCardController.php` (Monatskarte/VIP-Jahreskarte/Stempelkarte)
- Create: `service/app/marketing/PointsController.php` (1:100 Einlösung für Geschenkkarten)
- Create: `service/app/marketing/GiftCardController.php`

---

## Phase 6: Inhalte und Benachrichtigungen

- Create: `service/app/model/Banner.php` + Announcement/Agreement/Faq/Feedback/Moment/Notification
- Create: `service/app/content/BannerController.php`
- Create: `service/app/content/AnnouncementController.php`
- Create: `service/app/content/NotificationController.php`
- Create: `service/app/lbs/LocationController.php`

---

## Phase 7: Admin-Verwaltungsbackend-Erweiterung

- Alle Verwaltungs-Controller basieren auf dem bestehenden `admin/app/admin/controller/BaseController.php`
- Sensible Operationen (Löschen/Prüfung/Auszahlung) verwenden `erikwang2013/poster-php` mit Zufalls-Verifizierung
- Dashboard: Echtzeit-Statistikkarten + Chart.js-Liniendiagramme + Schnellnavigation + Interne Nachrichten
- Excel-Export (Benutzer/Techniker/Bestellungen/Finanzen), sensible Felder automatisch maskiert
- PDF-Panel-Export
- Quell-Endpunkt-Erkennung: von der `OperationLog`-Middleware unterstützt, auf 8 Endpunkte erweitert

Neue Controller:
- `admin/app/admin/controller/TechnicianController.php`
- `admin/app/admin/controller/MemberController.php`
- `admin/app/admin/controller/StoreController.php`
- `admin/app/admin/controller/ServiceController.php`
- `admin/app/admin/controller/ServiceCategoryController.php`
- `admin/app/admin/controller/ProductController.php`
- `admin/app/admin/controller/MallOrderController.php`
- `admin/app/admin/controller/SalesStatsController.php`
- `admin/app/admin/controller/AppointmentOrderController.php`
- `admin/app/admin/controller/CouponController.php`
- `admin/app/admin/controller/FinanceController.php`
- `admin/app/admin/controller/WithdrawalController.php`
- `admin/app/admin/controller/CommissionController.php`
- `admin/app/admin/controller/WithdrawalAccountController.php`
- `admin/app/admin/controller/WithdrawalConfigController.php`
- `admin/app/admin/controller/BannerController.php`
- `admin/app/admin/controller/AnnouncementController.php`
- `admin/app/admin/controller/FaqController.php`
- `admin/app/admin/controller/FeedbackController.php`
- `admin/app/admin/controller/MomentController.php`
- `admin/app/admin/controller/AgreementController.php`
- `admin/app/admin/controller/AboutController.php`
- `admin/app/admin/controller/SystemMessageController.php`

- Erweitere `admin/app/admin/controller/DashboardController.php` um Geschäftsstatistiken
- Erweitere `admin/app/admin/controller/ExportController.php` um Geschäftsdaten-Export
- Erweitere die Berechtigungs-Seeddaten `admin/database/migrations/` um Geschäftsberechtigungs-Knoten

---

## Phase 8: Frontend

- `apps/wechat/` — WeChat-MiniProgramm-Seitenentwicklung (siehe Seitenstruktur in STRUCTURE.md)
- `apps/flutter/` — Flutter-APP-Seitenentwicklung (strukturidentisch mit dem MiniProgramm)
- `admin/apps/flutter/` — neue Geschäftsseiten des Verwaltungsbackend-Flutter-Web

---

## Technische Standards im Schnellüberblick

| Standard | Umsetzung |
|------|----------|
| Copyright-Header | Kopf aller .php-Dateien: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz` |
| Globale Funktionen | Import über `use`, kein vorangestelltes `\` |
| ID Ver-/Entschlüsselung | BaseController::encodeIds/decodeIds automatische hashids-Codierung/-Dekodierung |
| DB-sensible Felder | encryptable-Trait deklariert `$encryptable`-Array |
| API-sensible Daten | encryption-Paket ver-/entschlüsselt in der Serialisierungsphase |
| ES-Suche | webman-scout-Modellkonfiguration `searchableAs()` + SearchController |
| Sicherheitsprüfung | security-php globale Middleware, 31 Angriffserkennungen |
| Verifizierung sensibler Operationen | poster-php zeigt vor Lösch-/Prüf-/Auszahlungs-Operationen eine Verifizierung an |
| Excel-Export | ExportController mit PhpSpreadsheet, sensible Felder maskiert |
| PDF-Panel-Export | Dashboard-Statistik als PDF gerendert |
| Quell-Endpunkt-Erkennung | OperationLog-Middleware User-Agent → 8-Endpunkt-Zeichenkette |
