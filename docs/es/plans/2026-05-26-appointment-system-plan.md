# Plan de implementación del sistema de servicios de reservas

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Objetivo:** Construir el sistema de servicios de reservas de tres extremos: miniprograma de WeChat de usuario + APP Flutter (cambio de identidad con la misma cuenta), extensión del panel de administración PC.

**Arquitectura:** `admin/` (API del panel de administración) + `service/` (API de negocio), dos servicios que comparten MySQL/Redis/ES. El miniprograma y la APP de usuario tienen funcionalidad equivalente; la cuenta unificada admite el cambio de identidad cliente/técnico.

**Pila tecnológica:** PHP 8.3 + webman v2, MySQL 8.0, Redis, Elasticsearch, miniprograma nativo de WeChat, Flutter 3.x (GetX + Dio)

**Normas técnicas:**
- Clave primaria: `erikwang2013/snowflake-php` BIGINT no autoincremental
- ID de API: `erikwang2013/hashids` cifrado/descifrado
- JWT: `erikwang2013/jwt-webman`
- Banderas de país: `erikwang2013/season`
- Datos sensibles de API: `erikwang2013/encryption`
- Campos sensibles de DB: `erikwang2013/encryptable`
- Búsqueda ES: `erikwang2013/webman-scout`
- Detección de seguridad: `erikwang2013/security-php`
- Verificación de operaciones sensibles: `erikwang2013/poster-php`
- Copyright: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz`
- Las funciones globales no llevan `\` delante; se importan con `use`
- Los archivos de configuración contienen comentarios en chino
- Exportación Excel + visualización del panel + exportación de panel PDF
- Origen de la operación: web, iPadOS, macOS, Windows, Linux, ios, android, harmonyOS

**Especificación de diseño:** `docs/superpowers/specs/2026-05-26-appointment-system-design.md`  
**Estructura del proyecto:** `docs/STRUCTURE.md`  
**Migración de base de datos:** `admin/database/migrations/2026_05_26_000003_appointment_business_tables.sql`

---

## Phase 0: Base — esqueleto del proyecto service/

### Task 0.1: Inicializar la configuración del proyecto service/

**Archivos:**
- Crear: `service/composer.json`
- Crear: `service/.env` / `service/.env.example`
- Crear: `service/config/app.php`
- Crear: `service/config/database.php`
- Crear: `service/config/route.php`
- Crear: `service/config/middleware.php`
- Crear: `service/config/jwt.php`
- Crear: `service/config/hashids.php`
- Crear: `service/config/snowflake.php`
- Crear: `service/config/encryption.php`
- Crear: `service/config/encryptable.php`
- Crear: `service/config/scout.php`
- Crear: `service/config/security.php`
- Crear: `service/config/poster.php`
- Crear: `service/config/season.php`
- Crear: `service/config/autoload.php`
- Crear: `service/config/bootstrap.php`
- Crear: `service/config/container.php`
- Crear: `service/config/dependence.php`
- Crear: `service/config/exception.php`
- Crear: `service/config/log.php`
- Crear: `service/config/process.php`
- Crear: `service/config/server.php`
- Crear: `service/config/session.php`
- Crear: `service/config/static.php`
- Crear: `service/config/translation.php`
- Crear: `service/config/view.php`

- [ ] **Step 1: Crear composer.json**

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

- [ ] **Step 2: Instalar las dependencias**

```bash
cd /home/wwwroot/appointment-php/service && composer install
```

- [ ] **Step 3: Crear config/database.php** — comparte el mismo MySQL con admin

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

- [ ] **Step 4: Crear config/route.php**

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

- [ ] **Step 5: Crear config/middleware.php**

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

- [ ] **Step 6: Copiar los archivos de configuración compartidos desde admin/config/**

Copiar `jwt.php`, `hashids.php`, `snowflake.php`, `encryption.php`, `encryptable.php`, `scout.php`, `season.php`, `poster.php` a `service/config/`, añadiendo la declaración de copyright y comentarios en chino.

- [ ] **Step 7: Crear app/functions.php**

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

- [ ] **Step 8: Crear app/middleware/Cors.php**

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

- [ ] **Step 9: Crear el middleware Security**

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

- [ ] **Step 10: Crear el middleware Auth**

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

- [ ] **Step 11: Crear el middleware TechnicianAuth**

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

- [ ] **Step 12: Crear BaseController** — respuesta unificada + cifrado/descifrado hashids

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

## Phase 1: Autenticación de usuario y gestión de identidad

### Task 1.1: Crear el modelo User

- Crear: `service/app/model/User.php`

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

### Task 1.2: Crear AuthController

- Crear: `service/app/api/AuthController.php`
- Implementar: registro con teléfono (incluye código de recomendación), inicio de sesión con contraseña, inicio de sesión con código de verificación, olvido de contraseña, cambio de identidad, inicio de sesión WeChat (flujo de vinculación de teléfono reservado)
- Modo invitado: controlado por el frontend; se puede navegar sin iniciar sesión; al realizar un pedido se comprueba el JWT

### Task 1.3: Crear los controladores del módulo user

- Crear: `service/app/user/ProfileController.php`
- Crear: `service/app/user/AddressController.php`
- Crear: `service/app/user/FavoriteController.php`
- Crear: `service/app/user/FeedbackController.php`
- Crear: `service/app/user/ReferralController.php`

---

## Phase 2: Servicios y tiendas

- Crear: `service/app/model/ServiceCategory.php`
- Crear: `service/app/model/Service.php` (incluye configuración del índice ES de webman-scout)
- Crear: `service/app/model/Product.php`
- Crear: `service/app/model/Store.php`
- Crear: `service/app/service/CategoryController.php`
- Crear: `service/app/service/ItemController.php`
- Crear: `service/app/service/SearchController.php` (búsqueda ES)
- Crear: `service/app/service/StoreController.php`

---

## Phase 3: Pedidos y pagos

- Crear: `service/app/model/Order.php` + OrderItem/Payment/Refund/Review/Verification
- Crear: `service/app/order/CartController.php`
- Crear: `service/app/order/OrderController.php` — al realizar el pedido, Redis SETNX bloquea al técnico 3 minutos
- Crear: `service/app/order/PaymentController.php` (pago WeChat reservado)
- Crear: `service/app/order/VerificationController.php` (verificación con código QR)
- Crear: `service/app/order/ReviewController.php`
- Reglas de reembolso: proporción de cuatro niveles 100 %/90 %/80 %/0 %

---

## Phase 4: Módulo de técnico

- Crear: `service/app/model/TechnicianProfile.php` + Schedule/Service/Earnings/Withdrawal/Attendance/MemberNote
- Crear: `service/app/technician/ProfileController.php` (solicitud de incorporación + auditoría)
- Crear: `service/app/technician/ScheduleController.php`
- Crear: `service/app/technician/OrderController.php`
- Crear: `service/app/technician/MemberController.php`
- Crear: `service/app/technician/EarningsController.php`
- Crear: `service/app/technician/WithdrawalController.php` (día 20 de cada mes/T+1)
- Crear: `service/app/technician/AttendanceController.php`

---

## Phase 5: Módulo de marketing

- Crear: `service/app/model/Coupon.php` + UserCoupon/MemberCard/UserMemberCard/MemberCardUsage/Points/GiftCard/Referral
- Crear: `service/app/marketing/CouponController.php` (emisión automática de cupones a nuevos usuarios)
- Crear: `service/app/marketing/MemberCardController.php` (tarjeta mensual/tarjeta anual VIP/tarjeta por uso)
- Crear: `service/app/marketing/PointsController.php` (canje 1:100 por tarjeta regalo)
- Crear: `service/app/marketing/GiftCardController.php`

---

## Phase 6: Contenido y notificaciones

- Crear: `service/app/model/Banner.php` + Announcement/Agreement/Faq/Feedback/Moment/Notification
- Crear: `service/app/content/BannerController.php`
- Crear: `service/app/content/AnnouncementController.php`
- Crear: `service/app/content/NotificationController.php`
- Crear: `service/app/lbs/LocationController.php`

---

## Phase 7: Extensión del panel de administración de Admin

- Todos los controladores del panel se basan en el `admin/app/admin/controller/BaseController.php` existente
- Las operaciones sensibles (eliminar/auditar/retirar) usan `erikwang2013/poster-php` para añadir verificación aleatoria
- Dashboard: tarjetas de estadísticas en tiempo real + gráfico de líneas Chart.js + navegación rápida + mensajes internos
- Exportación Excel (usuarios/técnicos/pedidos/finanzas), los campos sensibles se desidentifican automáticamente
- Exportación de panel PDF
- Detección del origen de la operación: el middleware `OperationLog` ya lo admite; se amplía a 8 extremos

Nuevos controladores:
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

- Extender `admin/app/admin/controller/DashboardController.php` con estadísticas de negocio
- Extender `admin/app/admin/controller/ExportController.php` con exportación de datos de negocio
- Extender los datos de permisos semilla `admin/database/migrations/` con nodos de permisos de negocio

---

## Phase 8: Frontend

- `apps/wechat/` — desarrollo de páginas del miniprograma de WeChat (según la estructura de páginas de STRUCTURE.md)
- `apps/flutter/` — desarrollo de páginas de la APP Flutter (estructura coherente con el miniprograma)
- `admin/apps/flutter/` — nuevas páginas de negocio del Flutter Web del panel de administración

---

## Referencia rápida de normas técnicas

| Norma | Forma de implementación |
|------|----------|
| Cabecera de copyright | En todos los archivos .php: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz` |
| Funciones globales | Importar con `use`, sin `\` delante |
| Cifrado/descifrado de ID | BaseController::encodeIds/decodeIds codifican y decodifican hashids automáticamente |
| Campos sensibles de DB | El trait encryptable declara el array `$encryptable` |
| Datos sensibles de API | El paquete encryption cifra/descifra en la fase de serialización |
| Búsqueda ES | Modelos webman-scout con `searchableAs()` + SearchController |
| Detección de seguridad | Middleware global security-php, detección de 31 ataques |
| Verificación de operaciones sensibles | poster-php muestra verificación antes de eliminar/auditar/retirar |
| Exportación Excel | ExportController usa PhpSpreadsheet; campos sensibles desidentificados |
| Exportación de panel PDF | Las estadísticas del Dashboard se renderizan como PDF |
| Detección del origen | Middleware OperationLog: User-Agent → cadena de 8 extremos |
