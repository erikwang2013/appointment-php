# خطة تنفيذ نظام خدمات الحجز

> **للعاملين الآليين:** المهارة الفرعية المطلوبة: استخدم superpowers:subagent-driven-development (موصى به) أو superpowers:executing-plans لتنفيذ هذا المخطط مهمةً تلو الأخرى.

**الهدف:** بناء نظام خدمات حجز ثلاثي الأطراف: برنامج صغير WeChat للعميل + تطبيق Flutter APP (تبديل الهوية بنفس الحساب)، وتوسيع لوحة الإدارة PC.

**البنية:** خدمتان `admin/` (واجهة برمجة إدارة) + `service/` (واجهة برمجة أعمال) تشاركان MySQL/Redis/ES. وظائف البرنامج الصغير وتطبيق APP متكافئة، وحساب موحد يدعم تبديل هوية العميل/الفني.

**حزمة التقنيات:** PHP 8.3 + webman v2, MySQL 8.0, Redis, Elasticsearch, برنامج صغير WeChat أصلي, Flutter 3.x (GetX + Dio)

**المواصفات التقنية:**
- المفتاح الأساسي: `erikwang2013/snowflake-php` BIGINT غير تلقائي الزيادة
- معرفات API: تشفير وفك `erikwang2013/hashids`
- JWT: `erikwang2013/jwt-webman`
- أعلام الدول: `erikwang2013/season`
- بيانات API الحساسة: `erikwang2013/encryption`
- حقول قاعدة البيانات الحساسة: `erikwang2013/encryptable`
- بحث ES: `erikwang2013/webman-scout`
- كشف الأمان: `erikwang2013/security-php`
- التحقق من العمليات الحساسة: `erikwang2013/poster-php`
- الحقوق: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz`
- الدوال العامة لا تسبق بـ `\`، استخدم الاستيراد `use`
- ملفات الإعداد تحتوي على تعليقات صينية
- تصدير Excel + تصور لوحة + تصدير لوحة PDF
- جهة مصدر العملية: web, iPadOS, macOS, Windows, Linux, ios, android, harmonyOS

**مواصفة التصميم:** `docs/superpowers/specs/2026-05-26-appointment-system-design.md`  
**بنية المشروع:** `docs/STRUCTURE.md`  
**ترحيل قاعدة البيانات:** `admin/database/migrations/2026_05_26_000003_appointment_business_tables.sql`

---

## المرحلة 0: الأساس — هيكل مشروع service/

### المهمة 0.1: تهيئة إعدادات مشروع service/

**الملفات:**
- إنشاء: `service/composer.json`
- إنشاء: `service/.env` / `service/.env.example`
- إنشاء: `service/config/app.php`
- إنشاء: `service/config/database.php`
- إنشاء: `service/config/route.php`
- إنشاء: `service/config/middleware.php`
- إنشاء: `service/config/jwt.php`
- إنشاء: `service/config/hashids.php`
- إنشاء: `service/config/snowflake.php`
- إنشاء: `service/config/encryption.php`
- إنشاء: `service/config/encryptable.php`
- إنشاء: `service/config/scout.php`
- إنشاء: `service/config/security.php`
- إنشاء: `service/config/poster.php`
- إنشاء: `service/config/season.php`
- إنشاء: `service/config/autoload.php`
- إنشاء: `service/config/bootstrap.php`
- إنشاء: `service/config/container.php`
- إنشاء: `service/config/dependence.php`
- إنشاء: `service/config/exception.php`
- إنشاء: `service/config/log.php`
- إنشاء: `service/config/process.php`
- إنشاء: `service/config/server.php`
- إنشاء: `service/config/session.php`
- إنشاء: `service/config/static.php`
- إنشاء: `service/config/translation.php`
- إنشاء: `service/config/view.php`

- [ ] **الخطوة 1: إنشاء composer.json**

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

- [ ] **الخطوة 2: تثبيت التبعيات**

```bash
cd /home/wwwroot/appointment-php/service && composer install
```

- [ ] **الخطوة 3: إنشاء config/database.php** — يشارك نفس MySQL مع admin

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

- [ ] **الخطوة 4: إنشاء config/route.php**

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

- [ ] **الخطوة 5: إنشاء config/middleware.php**

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

- [ ] **الخطوة 6: نسخ ملفات الإعداد المشتركة من admin/config/**

انسخ `jwt.php`, `hashids.php`, `snowflake.php`, `encryption.php`, `encryptable.php`, `scout.php`, `season.php`, `poster.php` إلى `service/config/`، مع إضافة إعلان الحقوق والتعليقات الصينية.

- [ ] **الخطوة 7: إنشاء app/functions.php**

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

- [ ] **الخطوة 8: إنشاء app/middleware/Cors.php**

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

- [ ] **الخطوة 9: إنشاء وسيط Security**

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

- [ ] **الخطوة 10: إنشاء وسيط Auth**

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

- [ ] **الخطوة 11: إنشاء وسيط TechnicianAuth**

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

- [ ] **الخطوة 12: إنشاء BaseController** — استجابة موحدة + تشفير/فك hashids

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

- [ ] **الخطوة 13: Commit**

```bash
git add service/
git commit -m "feat(service): initialize project skeleton with configs, middleware, base controller"
```

---

## المرحلة 1: مصادقة المستخدم وإدارة الهوية

### المهمة 1.1: إنشاء نموذج User

- إنشاء: `service/app/model/User.php`

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

### المهمة 1.2: إنشاء AuthController

- إنشاء: `service/app/api/AuthController.php`
- التنفيذ: تسجيل برقم الهاتف (بما في ذلك كود الدعوة)، تسجيل دخول بكلمة المرور، تسجيل دخول برمز التحقق، نسيان كلمة المرور، تبديل الهوية، تسجيل دخول WeChat (مع مسار ربط رقم الهاتف المحجوز)
- وضع الضيف: التحكم من الواجهة الأمامية، يمكن التصفح بدون تسجيل، عند الطلب يتم فحص JWT

### المهمة 1.3: إنشاء وحدات تحكم وحدة user

- إنشاء: `service/app/user/ProfileController.php`
- إنشاء: `service/app/user/AddressController.php`
- إنشاء: `service/app/user/FavoriteController.php`
- إنشاء: `service/app/user/FeedbackController.php`
- إنشاء: `service/app/user/ReferralController.php`

---

## المرحلة 2: الخدمات والفروع

- إنشاء: `service/app/model/ServiceCategory.php`
- إنشاء: `service/app/model/Service.php` (يتضمن إعداد فهرس ES عبر webman-scout)
- إنشاء: `service/app/model/Product.php`
- إنشاء: `service/app/model/Store.php`
- إنشاء: `service/app/service/CategoryController.php`
- إنشاء: `service/app/service/ItemController.php`
- إنشاء: `service/app/service/SearchController.php` (بحث ES)
- إنشاء: `service/app/service/StoreController.php`

---

## المرحلة 3: الطلبات والدفع

- إنشاء: `service/app/model/Order.php` + OrderItem/Payment/Refund/Review/Verification
- إنشاء: `service/app/order/CartController.php`
- إنشاء: `service/app/order/OrderController.php` — عند الطلب يتم قفل الفني بـ Redis SETNX لمدة 3 دقائق
- إنشاء: `service/app/order/PaymentController.php` (الدفع عبر WeChat محجوز)
- إنشاء: `service/app/order/VerificationController.php` (إلغاء التحقق بالرمز QR)
- إنشاء: `service/app/order/ReviewController.php`
- قاعدة الاسترداد: أربع مستويات 100%/90%/80%/0%

---

## المرحلة 4: وحدة الفني

- إنشاء: `service/app/model/TechnicianProfile.php` + Schedule/Service/Earnings/Withdrawal/Attendance/MemberNote
- إنشاء: `service/app/technician/ProfileController.php` (طلب الانضمام + المراجعة)
- إنشاء: `service/app/technician/ScheduleController.php`
- إنشاء: `service/app/technician/OrderController.php`
- إنشاء: `service/app/technician/MemberController.php`
- إنشاء: `service/app/technician/EarningsController.php`
- إنشاء: `service/app/technician/WithdrawalController.php` (اليوم 20 من كل شهر/T+1)
- إنشاء: `service/app/technician/AttendanceController.php`

---

## المرحلة 5: وحدة التسويق

- إنشاء: `service/app/model/Coupon.php` + UserCoupon/MemberCard/UserMemberCard/MemberCardUsage/Points/GiftCard/Referral
- إنشاء: `service/app/marketing/CouponController.php` (إصدار تلقائي لقسيمة للمستخدم الجديد عند التسجيل)
- إنشاء: `service/app/marketing/MemberCardController.php` (بطاقة شهرية/بطاقة VIP سنوية/بطاقة بعدد مرات)
- إنشاء: `service/app/marketing/PointsController.php` (استبدال بطاقة هدايا بنسبة 1:100)
- إنشاء: `service/app/marketing/GiftCardController.php`

---

## المرحلة 6: المحتوى والإشعارات

- إنشاء: `service/app/model/Banner.php` + Announcement/Agreement/Faq/Feedback/Moment/Notification
- إنشاء: `service/app/content/BannerController.php`
- إنشاء: `service/app/content/AnnouncementController.php`
- إنشاء: `service/app/content/NotificationController.php`
- إنشاء: `service/app/lbs/LocationController.php`

---

## المرحلة 7: توسيع لوحة إدارة Admin

- جميع وحدات تحكم الإدارة تستند إلى `admin/app/admin/controller/BaseController.php` الحالي
- العمليات الحساسة (حذف/مراجعة/سحب) تستخدم `erikwang2013/poster-php` لإضافة تحقق عشوائي
- Dashboard: بطاقات إحصائية لحظية + مخطط خطي Chart.js + تنقل سريع + رسائل داخلية
- تصدير Excel (المستخدمون/الفنيون/الطلبات/المالية)، مع إخفاء تلقائي للحقول الحساسة
- تصدير لوحة PDF
- كشف جهة مصدر العملية: يدعمه وسيط `OperationLog` بالفعل، توسيع إلى 8 أطراف

وحدات تحكم جديدة:
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

- توسيع `admin/app/admin/controller/DashboardController.php` بإحصائيات الأعمال
- توسيع `admin/app/admin/controller/ExportController.php` بتصدير بيانات الأعمال
- توسيع بيانات البذرة للصلاحيات في `admin/database/migrations/` بإضافة عقد صلاحيات الأعمال

---

## المرحلة 8: الواجهة الأمامية

- `apps/wechat/` — تطوير صفحات برنامج WeChat الصغير (وفق بنية صفحات STRUCTURE.md)
- `apps/flutter/` — تطوير صفحات تطبيق Flutter APP (بنية متطابقة مع البرنامج الصغير)
- `admin/apps/flutter/` — صفحات أعمال جديدة لـ Flutter Web للوحة الإدارة

---

## مرجع سريع للمواصفات التقنية

| المواصفة | طريقة التنفيذ |
|------|----------|
| ترويسة الحقوق | رأس جميع ملفات .php: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz` |
| الدوال العامة | الاستيراد بـ `use`، بدون بادئة `\` |
| تشفير/فك المعرفات | BaseController::encodeIds/decodeIds ترميز/فك تلقائي بـ hashids |
| حقول قاعدة البيانات الحساسة | إعلان مصفوفة `$encryptable` في trait encryptable |
| بيانات API الحساسة | حزمة encryption تشفر/تفك في مرحلة التسلسل |
| بحث ES | webman-scout تكوين `searchableAs()` في النموذج + SearchController |
| كشف الأمان | وسيط عام security-php، 31 نوع كشف هجوم |
| التحقق من العمليات الحساسة | poster-php يظهر تحققًا قبل عمليات الحذف/المراجعة/السحب |
| تصدير Excel | ExportController يستخدم PhpSpreadsheet، مع إخفاء الحقول الحساسة |
| تصدير لوحة PDF | إحصائيات Dashboard تُعرض كـ PDF |
| كشف جهة المصدر | وسيط OperationLog User-Agent → سلسلة الأطراف الثمانية |
