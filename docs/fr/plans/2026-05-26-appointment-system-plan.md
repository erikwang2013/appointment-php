# Plan d'implémentation du système de réservation de services
> **Languages**: [中文](../../superpowers/plans/2026-05-26-appointment-system-plan.md) · [English](../../en/plans/2026-05-26-appointment-system-plan.md) · [한국어](../../ko/plans/2026-05-26-appointment-system-plan.md) · [Русский](../../ru/plans/2026-05-26-appointment-system-plan.md) · [Deutsch](../../de/plans/2026-05-26-appointment-system-plan.md) · [Español](../../es/plans/2026-05-26-appointment-system-plan.md) · [Português](../../pt/plans/2026-05-26-appointment-system-plan.md) · [हिन्दी](../../hi/plans/2026-05-26-appointment-system-plan.md) · [العربية](../../ar/plans/2026-05-26-appointment-system-plan.md) · [বাংলা](../../bn/plans/2026-05-26-appointment-system-plan.md) · [Bahasa Indonesia](../../id/plans/2026-05-26-appointment-system-plan.md) · [日本語](../../ja/plans/2026-05-26-appointment-system-plan.md)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Objectif :** construire un système de réservation de services à trois extrémités : mini-programme WeChat + Flutter APP côté utilisateur (changement d'identité dans le même compte), extension du back-office PC.

**Architecture :** `admin/` (API du back-office) + `service/` (API métier), deux services partageant MySQL/Redis/ES. Les fonctionnalités du mini-programme et de l'APP sont équivalentes, le compte unifié prend en charge la bascule identité client/technicien.

**Pile technique :** PHP 8.3 + webman v2, MySQL 8.0, Redis, Elasticsearch, mini-programme WeChat natif, Flutter 3.x (GetX + Dio)

**Spécifications techniques :**
- Clés primaires : `erikwang2013/snowflake-php` BIGINT non auto-incrémenté
- ID API : `erikwang2013/hashids` chiffrement/déchiffrement
- JWT : `erikwang2013/jwt-webman`
- Drapeaux nationaux : `erikwang2013/season`
- Données sensibles API : `erikwang2013/encryption`
- Champs sensibles DB : `erikwang2013/encryptable`
- Recherche ES : `erikwang2013/webman-scout`
- Détection de sécurité : `erikwang2013/security-php`
- Validation des opérations sensibles : `erikwang2013/poster-php`
- Copyright : `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz`
- Les fonctions globales ne portent pas de `\`, utiliser l'import `use`
- Les fichiers de configuration contiennent des commentaires en chinois
- Export Excel + visualisation du panneau + export PDF du panneau
- Sources d'opérations : web, iPadOS, macOS, Windows, Linux, ios, android, harmonyOS

**Spécifications de conception :** [docs/fr/specs/2026-05-26-appointment-system-design.md](../specs/2026-05-26-appointment-system-design.md)  
**Structure du projet :** [docs/fr/STRUCTURE.md](../STRUCTURE.md)  
**Migration de base de données :** `admin/database/migrations/2026_05_26_000003_appointment_business_tables.sql`

---

## Phase 0 : Foundation — squelette du projet service/

### Tâche 0.1 : initialiser la configuration du projet service/

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

- [ ] **Étape 1 : créer composer.json**

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

- [ ] **Étape 2 : installer les dépendances**

```bash
cd /home/wwwroot/appointment-php/service && composer install
```

- [ ] **Étape 3 : créer config/database.php** — partage le même MySQL qu'admin

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
            'prefix'    => 'appointment_',       // 表前缀
            'strict'    => true,
        ],
    ],
];
```

- [ ] **Étape 4 : créer config/route.php**

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

- [ ] **Étape 5 : créer config/middleware.php**

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

- [ ] **Étape 6 : copier les fichiers de configuration partagés depuis admin/config/**

Copier `jwt.php`, `hashids.php`, `snowflake.php`, `encryption.php`, `encryptable.php`, `scout.php`, `season.php`, `poster.php` vers `service/config/`, ajouter la déclaration de copyright et les commentaires en chinois.

- [ ] **Étape 7 : créer app/functions.php**

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

- [ ] **Étape 8 : créer app/middleware/Cors.php**

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

- [ ] **Étape 9 : créer le middleware Security**

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

- [ ] **Étape 10 : créer le middleware Auth**

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

- [ ] **Étape 11 : créer le middleware TechnicianAuth**

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

- [ ] **Étape 12 : créer BaseController** — réponse unifiée + chiffrement/déchiffrement hashids

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

- [ ] **Étape 13 : Commit**

```bash
git add service/
git commit -m "feat(service): initialize project skeleton with configs, middleware, base controller"
```

---

## Phase 1 : authentification des utilisateurs et gestion des identités

### Tâche 1.1 : créer le modèle User

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

    protected $table = 'appointment_user';
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

### Tâche 1.2 : créer AuthController

- Create: `service/app/api/AuthController.php`
- Implémenter : inscription par numéro de téléphone (avec code de parrainage), connexion par mot de passe, connexion par code de vérification, mot de passe oublié, changement d'identité, connexion WeChat (flux de liaison du numéro de téléphone réservé)
- Mode invité : contrôlé côté frontend, navigation possible sans connexion, vérification JWT à la commande

### Tâche 1.3 : créer les contrôleurs du module user

- Create: `service/app/user/ProfileController.php`
- Create: `service/app/user/AddressController.php`
- Create: `service/app/user/FavoriteController.php`
- Create: `service/app/user/FeedbackController.php`
- Create: `service/app/user/ReferralController.php`

---

## Phase 2 : services et boutiques

- Create: `service/app/model/ServiceCategory.php`
- Create: `service/app/model/Service.php` (contient la configuration d'index ES webman-scout)
- Create: `service/app/model/Product.php`
- Create: `service/app/model/Store.php`
- Create: `service/app/service/CategoryController.php`
- Create: `service/app/service/ItemController.php`
- Create: `service/app/service/SearchController.php` (recherche ES)
- Create: `service/app/service/StoreController.php`

---

## Phase 3 : commandes et paiement

- Create: `service/app/model/Order.php` + OrderItem/Payment/Refund/Review/Verification
- Create: `service/app/order/CartController.php`
- Create: `service/app/order/OrderController.php` — verrou Redis SETNX du technicien pendant 3 minutes à la commande
- Create: `service/app/order/PaymentController.php` (paiement WeChat réservé)
- Create: `service/app/order/VerificationController.php` (vérification par QR code)
- Create: `service/app/order/ReviewController.php`
- Règles de remboursement : ratios à quatre niveaux 100 %/90 %/80 %/0 %

---

## Phase 4 : module technicien

- Create: `service/app/model/TechnicianProfile.php` + Schedule/Service/Earnings/Withdrawal/Attendance/MemberNote
- Create: `service/app/technician/ProfileController.php` (demande d'adhésion + audit)
- Create: `service/app/technician/ScheduleController.php`
- Create: `service/app/technician/OrderController.php`
- Create: `service/app/technician/MemberController.php`
- Create: `service/app/technician/EarningsController.php`
- Create: `service/app/technician/WithdrawalController.php` (le 20 de chaque mois/T+1)
- Create: `service/app/technician/AttendanceController.php`

---

## Phase 5 : module marketing

- Create: `service/app/model/Coupon.php` + UserCoupon/MemberCard/UserMemberCard/MemberCardUsage/Points/GiftCard/Referral
- Create: `service/app/marketing/CouponController.php` (émission automatique d'un bon à l'inscription d'un nouvel utilisateur)
- Create: `service/app/marketing/MemberCardController.php` (carte mensuelle/carte VIP annuelle/carte à forfait)
- Create: `service/app/marketing/PointsController.php` (échange 1:100 contre une carte cadeau)
- Create: `service/app/marketing/GiftCardController.php`

---

## Phase 6 : contenu et notifications

- Create: `service/app/model/Banner.php` + Announcement/Agreement/Faq/Feedback/Moment/Notification
- Create: `service/app/content/BannerController.php`
- Create: `service/app/content/AnnouncementController.php`
- Create: `service/app/content/NotificationController.php`
- Create: `service/app/lbs/LocationController.php`

---

## Phase 7 : extension du back-office Admin

- Tous les contrôleurs du back-office étendent le `admin/app/admin/controller/BaseController.php` existant
- Les opérations sensibles (suppression/audit/retrait) utilisent `erikwang2013/poster-php` pour ajouter une validation aléatoire
- Dashboard : cartes de statistiques en temps réel + graphiques en courbes Chart.js + navigation rapide + messages internes
- Export Excel (utilisateurs/techniciens/commandes/finances), masquage automatique des champs sensibles
- Export PDF du panneau
- Détection de la source d'opération : le middleware `OperationLog` prend déjà en charge, étendu à 8 sources

Nouveaux contrôleurs :
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

- Étendre `admin/app/admin/controller/DashboardController.php` avec les statistiques métier
- Étendre `admin/app/admin/controller/ExportController.php` avec l'export des données métier
- Étendre les données de permissions `admin/database/migrations/` avec les nœuds de permissions métier

---

## Phase 8 : frontend

- `apps/wechat/` — développement des pages du mini-programme WeChat (se référer à la structure de pages de STRUCTURE.md)
- `apps/flutter/` — développement des pages de la Flutter APP (structure identique au mini-programme)
- `admin/apps/flutter/` — nouvelles pages métier du Flutter Web du back-office

---

## Aide-mémoire des spécifications techniques

| Spécification | Mise en œuvre |
|------|----------|
| En-tête de copyright | En tête de tous les fichiers .php : `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz` |
| Fonctions globales | Import avec `use`, sans `\` préfixé |
| Chiffrement/déchiffrement des ID | encodeIds/decodeIds de BaseController : encodage/décodage hashids automatique |
| Champs sensibles DB | Déclaration du tableau `$encryptable` avec le trait encryptable |
| Données sensibles API | Chiffrement/déchiffrement du paquet encryption à la phase de sérialisation |
| Recherche ES | Configuration `searchableAs()` du modèle webman-scout + SearchController |
| Détection de sécurité | Middleware global security-php, 31 types de détection d'attaques |
| Validation des opérations sensibles | poster-php affiche une validation avant les opérations de suppression/audit/retrait |
| Export Excel | ExportController utilise PhpSpreadsheet, masquage des champs sensibles |
| Export PDF du panneau | Rendu des statistiques du Dashboard en PDF |
| Détection de la source | Middleware OperationLog User-Agent → chaîne à 8 sources |
