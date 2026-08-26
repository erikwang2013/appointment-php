# 预约服务系统 実装計画
> **Languages**: [中文](../../superpowers/plans/2026-05-26-appointment-system-plan.md) · [English](../../en/plans/2026-05-26-appointment-system-plan.md) · [한국어](../../ko/plans/2026-05-26-appointment-system-plan.md) · [Русский](../../ru/plans/2026-05-26-appointment-system-plan.md) · [Deutsch](../../de/plans/2026-05-26-appointment-system-plan.md) · [Français](../../fr/plans/2026-05-26-appointment-system-plan.md) · [Español](../../es/plans/2026-05-26-appointment-system-plan.md) · [Português](../../pt/plans/2026-05-26-appointment-system-plan.md) · [हिन्दी](../../hi/plans/2026-05-26-appointment-system-plan.md) · [العربية](../../ar/plans/2026-05-26-appointment-system-plan.md) · [বাংলা](../../bn/plans/2026-05-26-appointment-system-plan.md) · [Bahasa Indonesia](../../id/plans/2026-05-26-appointment-system-plan.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Goal:** 三端対応の予約サービスシステムを構築する：ユーザー端微信小程序 + Flutter APP（同一アカウントで身份切替）、PC管理バックエンドの拡張。

**Architecture:** `admin/`（管理バックエンドAPI）+ `service/`（業務API）の二重サービスで MySQL/Redis/ES を共有。ユーザー端の小程序とAPPは機能が同等、統一アカウントで顧客/スタッフの身份切替に対応。

**Tech Stack:** PHP 8.3 + webman v2, MySQL 8.0, Redis, Elasticsearch, 微信小程序ネイティブ, Flutter 3.x (GetX + Dio)

**技術規範:**
- 主キー: `erikwang2013/snowflake-php` BIGINT 非自動採番
- API ID: `erikwang2013/hashids` 加復号
- JWT: `erikwang2013/jwt-webman`
- 国家旗: `erikwang2013/season`
- API 機密データ: `erikwang2013/encryption`
- DB 機密フィールド: `erikwang2013/encryptable`
- ES 検索: `erikwang2013/webman-scout`
- セキュリティ検知: `erikwang2013/security-php`
- 機密操作検証: `erikwang2013/poster-php`
- 著作権: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz`
- グローバル関数は `\` を付けず、`use` でインポート
- 設定ファイルは中国語コメント付き
- Excel エクスポート + パネル可視化 + PDF パネルエクスポート
- 操作来源端: web, iPadOS, macOS, Windows, Linux, ios, android, harmonyOS

**設計規範:** `docs/superpowers/specs/2026-05-26-appointment-system-design.md`  
**プロジェクト構造:** `docs/STRUCTURE.md`  
**データベース移行:** `admin/database/migrations/2026_05_26_000003_appointment_business_tables.sql`

---

## Phase 0: Foundation — service/ プロジェクト骨格

### Task 0.1: service/ プロジェクト設定の初期化

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

- [ ] **Step 1: composer.json の作成**

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

- [ ] **Step 2: 依存関係のインストール**

```bash
cd /home/wwwroot/appointment-php/service && composer install
```

- [ ] **Step 3: config/database.php の作成** — admin と同じ MySQL を共有

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

- [ ] **Step 4: config/route.php の作成**

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

- [ ] **Step 5: config/middleware.php の作成**

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

- [ ] **Step 6: admin/config/ から共有設定ファイルをコピー**

`jwt.php`, `hashids.php`, `snowflake.php`, `encryption.php`, `encryptable.php`, `scout.php`, `season.php`, `poster.php` を `service/config/` にコピーし、著作権声明と中国語コメントを追加。

- [ ] **Step 7: app/functions.php の作成**

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

- [ ] **Step 8: app/middleware/Cors.php の作成**

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

- [ ] **Step 9: Security ミドルウェアの作成**

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

- [ ] **Step 10: Auth ミドルウェアの作成**

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

- [ ] **Step 11: TechnicianAuth ミドルウェアの作成**

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

- [ ] **Step 12: BaseController の作成** — 統一レスポンス + hashids 加復号

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

- [ ] **Step 13: Commit**

```bash
git add service/
git commit -m "feat(service): initialize project skeleton with configs, middleware, base controller"
```

---

## Phase 1: ユーザー認証と身份管理

### Task 1.1: User モデルの作成

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

### Task 1.2: AuthController の作成

- Create: `service/app/api/AuthController.php`
- 実装: 携帯番号登録（招待コード対応）、パスワードログイン、验证码ログイン、パスワード忘れ、身份切替、微信ログイン（携帯番号連携フローは予約）
- 游客モード: フロント側で制御、未ログインでも閲覧可、注文時に JWT をチェック

### Task 1.3: user モジュールコントローラーの作成

- Create: `service/app/user/ProfileController.php`
- Create: `service/app/user/AddressController.php`
- Create: `service/app/user/FavoriteController.php`
- Create: `service/app/user/FeedbackController.php`
- Create: `service/app/user/ReferralController.php`

---

## Phase 2: サービスと店舗

- Create: `service/app/model/ServiceCategory.php`
- Create: `service/app/model/Service.php` (webman-scout ES インデックス設定込み)
- Create: `service/app/model/Product.php`
- Create: `service/app/model/Store.php`
- Create: `service/app/service/CategoryController.php`
- Create: `service/app/service/ItemController.php`
- Create: `service/app/service/SearchController.php` (ES 検索)
- Create: `service/app/service/StoreController.php`

---

## Phase 3: 注文と支払い

- Create: `service/app/model/Order.php` + OrderItem/Payment/Refund/Review/Verification
- Create: `service/app/order/CartController.php`
- Create: `service/app/order/OrderController.php` — 注文時に Redis SETNX でスタッフを 3 分間ロック
- Create: `service/app/order/PaymentController.php` (微信支払いは予約)
- Create: `service/app/order/VerificationController.php` (QRコード核销)
- Create: `service/app/order/ReviewController.php`
- 返金ルール: 100%/90%/80%/0% の 4 段階比率

---

## Phase 4: スタッフモジュール

- Create: `service/app/model/TechnicianProfile.php` + Schedule/Service/Earnings/Withdrawal/Attendance/MemberNote
- Create: `service/app/technician/ProfileController.php` (入驻申請 + 審査)
- Create: `service/app/technician/ScheduleController.php`
- Create: `service/app/technician/OrderController.php`
- Create: `service/app/technician/MemberController.php`
- Create: `service/app/technician/EarningsController.php`
- Create: `service/app/technician/WithdrawalController.php` (毎月 20 日/T+1)
- Create: `service/app/technician/AttendanceController.php`

---

## Phase 5: マーケティングモジュール

- Create: `service/app/model/Coupon.php` + UserCoupon/MemberCard/UserMemberCard/MemberCardUsage/Points/GiftCard/Referral
- Create: `service/app/marketing/CouponController.php` (新規ユーザー登録時にクーポン自動配布)
- Create: `service/app/marketing/MemberCardController.php` (月卡/VIP年卡/次卡)
- Create: `service/app/marketing/PointsController.php` (1:100 で礼品卡に交換)
- Create: `service/app/marketing/GiftCardController.php`

---

## Phase 6: コンテンツと通知

- Create: `service/app/model/Banner.php` + Announcement/Agreement/Faq/Feedback/Moment/Notification
- Create: `service/app/content/BannerController.php`
- Create: `service/app/content/AnnouncementController.php`
- Create: `service/app/content/NotificationController.php`
- Create: `service/app/lbs/LocationController.php`

---

## Phase 7: Admin 管理バックエンド拡張

- すべての管理端コントローラーは既存の `admin/app/admin/controller/BaseController.php` をベースに拡張
- 機密操作（削除/審査/出金）は `erikwang2013/poster-php` でランダム検証を追加
- Dashboard: リアルタイム統計カード + Chart.js折れ線グラフ + クイックナビゲーション + 站内メッセージ
- Excel エクスポート（ユーザー/スタッフ/注文/財務）、機密フィールドは自動脱敏
- PDF パネルエクスポート
- 操作来源端検出: `OperationLog` ミドルウェア対応済み、8 端に拡張

新規コントローラー:
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

- 拡張 `admin/app/admin/controller/DashboardController.php` 業務統計を追加
- 拡張 `admin/app/admin/controller/ExportController.php` 業務データエクスポートを追加
- 拡張権限シードデータ `admin/database/migrations/` 業務権限ノードを追加

---

## Phase 8: フロントエンド

- `apps/wechat/` — 微信小程序ページ開発（STRUCTURE.md のページ構造を参照）
- `apps/flutter/` — Flutter APP ページ開発（小程序と同じ構造）
- `admin/apps/flutter/` — 管理バックエンド Flutter Web に業務ページを追加

---

## 技術規範クイックリファレンス

| 規範 | 実装方式 |
|------|----------|
| 著作権ヘッダー | すべての .php ファイル先頭: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz` |
| グローバル関数 | `use` でインポート、前置 `\` を付けない |
| ID 加復号 | BaseController::encodeIds/decodeIds が hashids エンコード/デコードを自動処理 |
| DB 機密フィールド | encryptable trait で `$encryptable` 配列を宣言 |
| API 機密データ | encryption パッケージがシリアライズ段階で加復号 |
| ES 検索 | webman-scout モデル設定 `searchableAs()` + SearchController |
| セキュリティ検知 | security-php グローバルミドルウェア、31 種の攻撃検知 |
| 機密操作検証 | poster-php が削除/審査/出金操作前に検証をポップアップ |
| Excel エクスポート | ExportController が PhpSpreadsheet を使用、機密フィールドは脱敏 |
| PDF パネルエクスポート | Dashboard 統計を PDF にレンダリング |
| 来源端検知 | OperationLog ミドルウェアの User-Agent → 8 端文字列 |
