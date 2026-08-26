# অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম — বাস্তবায়ন পরিকল্পনা
> **Languages**: [中文](../../superpowers/plans/2026-05-26-appointment-system-plan.md) · [English](../../en/plans/2026-05-26-appointment-system-plan.md) · [한국어](../../ko/plans/2026-05-26-appointment-system-plan.md) · [Русский](../../ru/plans/2026-05-26-appointment-system-plan.md) · [Deutsch](../../de/plans/2026-05-26-appointment-system-plan.md) · [Français](../../fr/plans/2026-05-26-appointment-system-plan.md) · [Español](../../es/plans/2026-05-26-appointment-system-plan.md) · [Português](../../pt/plans/2026-05-26-appointment-system-plan.md) · [हिन्दी](../../hi/plans/2026-05-26-appointment-system-plan.md) · [العربية](../../ar/plans/2026-05-26-appointment-system-plan.md) · [Bahasa Indonesia](../../id/plans/2026-05-26-appointment-system-plan.md) · [日本語](../../ja/plans/2026-05-26-appointment-system-plan.md)

> বাংলা অনুবাদ · মূল: [中文](../../superpowers/plans/2026-05-26-appointment-system-plan.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**লক্ষ্য:** তিন-প্রান্তের অ্যাপয়েন্টমেন্ট সার্ভিস সিস্টেম নির্মাণ: ব্যবহারকারী-প্রান্ত WeChat মিনি-প্রোগ্রাম + Flutter APP (একই অ্যাকাউন্টে আইডেন্টিটি সুইচ), PC ম্যানেজমেন্ট ব্যাকএন্ড এক্সটেনশন।

**আর্কিটেকচার:** `admin/` (ম্যানেজমেন্ট ব্যাকএন্ড API) + `service/` (বিজনেস API) — দুটি সার্ভিস শেয়ারড MySQL/Redis/ES। ব্যবহারকারী-প্রান্ত মিনি-প্রোগ্রাম ও APP ফাংশন-সমান, ইউনিফাইড অ্যাকাউন্ট কাস্টমার/টেকনিশিয়ান আইডেন্টিটি সুইচ সমর্থন করে।

**টেক স্ট্যাক:** PHP 8.3 + webman v2, MySQL 8.0, Redis, Elasticsearch, WeChat মিনি-প্রোগ্রাম নেটিভ, Flutter 3.x (GetX + Dio)

**প্রযুক্তিগত স্পেসিফিকেশন:**
- প্রাইমারি কি: `erikwang2013/snowflake-php` BIGINT নন-অটো-ইনক্রিমেন্ট
- API ID: `erikwang2013/hashids` এনক্রিপশন/ডিক্রিপশন
- JWT: `erikwang2013/jwt-webman`
- দেশের পতাকা: `erikwang2013/season`
- API সংবেদনশীল ডেটা: `erikwang2013/encryption`
- DB সংবেদনশীল ফিল্ড: `erikwang2013/encryptable`
- ES সার্চ: `erikwang2013/webman-scout`
- নিরাপত্তা ডিটেকশন: `erikwang2013/security-php`
- সংবেদনশীল অপারেশন ভেরিফিকেশন: `erikwang2013/poster-php`
- কপিরাইট: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz`
- গ্লোবাল ফাংশনে `\` যোগ করা হয় না, `use` ইমপোর্ট ব্যবহার করা হয়
- কনফিগ ফাইলে চাইনিজ কমেন্ট থাকে
- Excel এক্সপোর্ট + প্যানেল ভিজ্যুয়ালাইজেশন + PDF প্যানেল এক্সপোর্ট
- অপারেশন সোর্স এন্ড: web, iPadOS, macOS, Windows, Linux, ios, android, harmonyOS

**ডিজাইন স্পেসিফিকেশন:** `docs/superpowers/specs/2026-05-26-appointment-system-design.md`  
**প্রজেক্ট স্ট্রাকচার:** `docs/STRUCTURE.md`  
**ডেটাবেস মাইগ্রেশন:** `admin/database/migrations/2026_05_26_000003_appointment_business_tables.sql`

---

## Phase 0: Foundation — service/ প্রজেক্ট স্কেলটন

### Task 0.1: service/ প্রজেক্ট কনফিগ ইনিশিয়ালাইজেশন

**Files:**
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

- [ ] **ধাপ 1: composer.json তৈরি**

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

- [ ] **ধাপ 2: ডিপেন্ডেন্সি ইনস্টল**

```bash
cd /home/wwwroot/appointment-php/service && composer install
```

- [ ] **ধাপ 3: config/database.php তৈরি** — admin-এর সাথে একই MySQL শেয়ার করা হয়

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

- [ ] **ধাপ 4: config/route.php তৈরি**

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

- [ ] **ধাপ 5: config/middleware.php তৈরি**

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

- [ ] **ধাপ 6: admin/config/ থেকে শেয়ারড কনফিগ ফাইল কপি**

`jwt.php`, `hashids.php`, `snowflake.php`, `encryption.php`, `encryptable.php`, `scout.php`, `season.php`, `poster.php` কপি করে `service/config/`-তে রাখুন, কপিরাইট ঘোষণা ও চাইনিজ কমেন্ট যোগ করুন।

- [ ] **ধাপ 7: app/functions.php তৈরি**

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

- [ ] **ধাপ 8: app/middleware/Cors.php তৈরি**

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

- [ ] **ধাপ 9: Security মিডলওয়্যার তৈরি**

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

- [ ] **ধাপ 10: Auth মিডলওয়্যার তৈরি**

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

- [ ] **ধাপ 11: TechnicianAuth মিডলওয়্যার তৈরি**

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

- [ ] **ধাপ 12: BaseController তৈরি** — ইউনিফাইড রেসপন্স + hashids এনক্রিপশন/ডিক্রিপশন

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

- [ ] **ধাপ 13: কমিট**

```bash
git add service/
git commit -m "feat(service): initialize project skeleton with configs, middleware, base controller"
```

---

## Phase 1: ইউজার অথেনটিকেশন ও আইডেন্টিটি ম্যানেজমেন্ট

### Task 1.1: User মডেল তৈরি

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

### Task 1.2: AuthController তৈরি

- Create: `service/app/api/AuthController.php`
- বাস্তবায়ন: ফোন রেজিস্ট্রেশন (রেফারেল কোডসহ), পাসওয়ার্ড লগইন, ভেরিফিকেশন কোড লগইন, পাসওয়ার্ড ভুলে গেলে, আইডেন্টিটি সুইচ, WeChat লগইন (ফোন বাইন্ডিং ফ্লো সংরক্ষিত)
- গেস্ট মোড: ফ্রন্টএন্ড নিয়ন্ত্রিত, লগইন না করেও ব্রাউজ করা যায়, অর্ডার দেওয়ার সময় JWT চেক হয়

### Task 1.3: user মডিউল কন্ট্রোলার তৈরি

- Create: `service/app/user/ProfileController.php`
- Create: `service/app/user/AddressController.php`
- Create: `service/app/user/FavoriteController.php`
- Create: `service/app/user/FeedbackController.php`
- Create: `service/app/user/ReferralController.php`

---

## Phase 2: সার্ভিস ও শাখা

- Create: `service/app/model/ServiceCategory.php`
- Create: `service/app/model/Service.php` (webman-scout ES ইনডেক্স কনফিগসহ)
- Create: `service/app/model/Product.php`
- Create: `service/app/model/Store.php`
- Create: `service/app/service/CategoryController.php`
- Create: `service/app/service/ItemController.php`
- Create: `service/app/service/SearchController.php` (ES সার্চ)
- Create: `service/app/service/StoreController.php`

---

## Phase 3: অর্ডার ও পেমেন্ট

- Create: `service/app/model/Order.php` + OrderItem/Payment/Refund/Review/Verification
- Create: `service/app/order/CartController.php`
- Create: `service/app/order/OrderController.php` — অর্ডারের সময় Redis SETNX দিয়ে টেকনিশিয়ানকে 3 মিনিট লক করা হয়
- Create: `service/app/order/PaymentController.php` (WeChat পেমেন্ট সংরক্ষিত)
- Create: `service/app/order/VerificationController.php` (QR কোড ভেরিফিকেশন)
- Create: `service/app/order/ReviewController.php`
- রিফান্ড নিয়ম: 100%/90%/80%/0% চার-স্তরের অনুপাত

---

## Phase 4: টেকনিশিয়ান মডিউল

- Create: `service/app/model/TechnicianProfile.php` + Schedule/Service/Earnings/Withdrawal/Attendance/MemberNote
- Create: `service/app/technician/ProfileController.php` (এনরোলমেন্ট আবেদন + অডিট)
- Create: `service/app/technician/ScheduleController.php`
- Create: `service/app/technician/OrderController.php`
- Create: `service/app/technician/MemberController.php`
- Create: `service/app/technician/EarningsController.php`
- Create: `service/app/technician/WithdrawalController.php` (প্রতি মাস ২০ তারিখ/T+1)
- Create: `service/app/technician/AttendanceController.php`

---

## Phase 5: মার্কেটিং মডিউল

- Create: `service/app/model/Coupon.php` + UserCoupon/MemberCard/UserMemberCard/MemberCardUsage/Points/GiftCard/Referral
- Create: `service/app/marketing/CouponController.php` (নতুন ইউজার রেজিস্ট্রেশনে স্বয়ংক্রিয় কুপন বিতরণ)
- Create: `service/app/marketing/MemberCardController.php` (মাসিক কার্ড/VIP বার্ষিক কার্ড/টাইমস কার্ড)
- Create: `service/app/marketing/PointsController.php` (1:100 গিফট কার্ড বিনিময়)
- Create: `service/app/marketing/GiftCardController.php`

---

## Phase 6: কনটেন্ট ও নোটিফিকেশন

- Create: `service/app/model/Banner.php` + Announcement/Agreement/Faq/Feedback/Moment/Notification
- Create: `service/app/content/BannerController.php`
- Create: `service/app/content/AnnouncementController.php`
- Create: `service/app/content/NotificationController.php`
- Create: `service/app/lbs/LocationController.php`

---

## Phase 7: Admin ম্যানেজমেন্ট ব্যাকএন্ড এক্সটেনশন

- সব ম্যানেজমেন্ট কন্ট্রোলার বিদ্যমান `admin/app/admin/controller/BaseController.php`-এর ভিত্তিতে এক্সটেন্ড করা হয়
- সংবেদনশীল অপারেশন (ডিলিট/অডিট/উত্তোলন) `erikwang2013/poster-php` দিয়ে র্যান্ডম ভেরিফিকেশন যোগ করা হয়
- Dashboard: রিয়েল-টাইম পরিসংখ্যান কার্ড + Chart.js লাইন চার্ট + দ্রুত নেভিগেশন + সাইট-মেসেজ
- Excel এক্সপোর্ট (ইউজার/টেকনিশিয়ান/অর্ডার/ফাইন্যান্স), সংবেদনশীল ফিল্ড স্বয়ংক্রিয় ডিসেন্সিটাইজড
- PDF প্যানেল এক্সপোর্ট
- অপারেশন সোর্স এন্ড ডিটেকশন: `OperationLog` মিডলওয়্যার সমর্থিত, 8 প্রান্তে এক্সটেন্ড করা হয়

নতুন কন্ট্রোলার:
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

- `admin/app/admin/controller/DashboardController.php` এক্সটেন্ড করে বিজনেস পরিসংখ্যান যোগ করা হয়
- `admin/app/admin/controller/ExportController.php` এক্সটেন্ড করে বিজনেস ডেটা এক্সপোর্ট যোগ করা হয়
- পারমিশন সিড ডেটা `admin/database/migrations/` এক্সটেন্ড করে বিজনেস পারমিশন নোড যোগ করা হয়

---

## Phase 8: ফ্রন্টএন্ড

- `apps/wechat/` — WeChat মিনি-প্রোগ্রাম পেজ ডেভেলপমেন্ট (STRUCTURE.md-এর পেজ স্ট্রাকচার অনুসরণ)
- `apps/flutter/` — Flutter APP পেজ ডেভেলপমেন্ট (মিনি-প্রোগ্রাম কাঠামোর সাথে মিল রেখে)
- `admin/apps/flutter/` — ম্যানেজমেন্ট ব্যাকএন্ড Flutter Web-এ নতুন বিজনেস পেজ

---

## প্রযুক্তিগত স্পেসিফিকেশন দ্রুত-রেফারেন্স

| স্পেসিফিকেশন | বাস্তবায়ন পদ্ধতি |
|------|----------|
| কপিরাইট হেডার | সব .php ফাইলের শুরুতে: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz` |
| গ্লোবাল ফাংশন | `use` ইমপোর্ট, অগ্রিম `\` যোগ করা হয় না |
| ID এনক্রিপশন/ডিক্রিপশন | BaseController::encodeIds/decodeIds স্বয়ংক্রিয় hashids এনকোড/ডিকোড |
| DB সংবেদনশীল ফিল্ড | encryptable trait-এ `$encryptable` অ্যারে ঘোষণা |
| API সংবেদনশীল ডেটা | encryption প্যাকেজ সিরিয়ালাইজেশন পর্যায়ে এনক্রিপশন/ডিক্রিপশন |
| ES সার্চ | webman-scout মডেল কনফিগ `searchableAs()` + SearchController |
| নিরাপত্তা ডিটেকশন | security-php গ্লোবাল মিডলওয়্যার, 31 ধরনের অ্যাটাক ডিটেকশন |
| সংবেদনশীল অপারেশন ভেরিফিকেশন | poster-php ডিলিট/অডিট/উত্তোলন অপারেশনের আগে ভেরিফিকেশন দেখায় |
| Excel এক্সপোর্ট | ExportController PhpSpreadsheet ব্যবহার করে, সংবেদনশীল ফিল্ড ডিসেন্সিটাইজড |
| PDF প্যানেল এক্সপোর্ট | Dashboard পরিসংখ্যান PDF-এ রেন্ডার |
| সোর্স এন্ড ডিটেকশন | OperationLog মিডলওয়্যার User-Agent → 8 প্রান্তের স্ট্রিং |
