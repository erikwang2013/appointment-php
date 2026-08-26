> Tradução em português · Original: [中文](../../superpowers/plans/2026-05-26-appointment-system-plan.md)

# Plano de implementação do sistema de serviços de agendamento

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task.

**Objetivo:** construir o sistema de serviços de agendamento em três frentes: miniprograma WeChat do lado do utilizador + APP Flutter (alternância de identidade na mesma conta), extensão do painel de administração para PC.

**Arquitetura:** `admin/` (API do painel de administração) + `service/` (API de negócio), dois serviços partilham MySQL/Redis/ES. O miniprograma e o APP do lado do utilizador têm funcionalidades equivalentes; a conta unificada suporta a alternância de identidade cliente/técnico.

**Tech Stack:** PHP 8.3 + webman v2, MySQL 8.0, Redis, Elasticsearch, miniprograma WeChat nativo, Flutter 3.x (GetX + Dio)

**Normas técnicas:**
- Chave primária: `erikwang2013/snowflake-php` BIGINT não autoincrementado
- ID de API: `erikwang2013/hashids` encriptação/desencriptação
- JWT: `erikwang2013/jwt-webman`
- Bandeiras de países: `erikwang2013/season`
- Dados sensíveis de API: `erikwang2013/encryption`
- Campos sensíveis de BD: `erikwang2013/encryptable`
- Pesquisa ES: `erikwang2013/webman-scout`
- Deteção de segurança: `erikwang2013/security-php`
- Verificação de operações sensíveis: `erikwang2013/poster-php`
- Copyright: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz`
- Funções globais sem `\` na frente, usar `use` para importar
- Ficheiros de configuração com comentários em chinês
- Exportação Excel + visualização no painel + exportação PDF do painel
- Origem das operações: web, iPadOS, macOS, Windows, Linux, ios, android, harmonyOS

**Especificação de design:** `docs/superpowers/specs/2026-05-26-appointment-system-design.md`  
**Estrutura do projeto:** `docs/STRUCTURE.md`  
**Migração da base de dados:** `admin/database/migrations/2026_05_26_000003_appointment_business_tables.sql`

---

## Fase 0: Fundação — esqueleto do projeto service/

### Tarefa 0.1: inicializar a configuração do projeto service/

**Ficheiros:**
- Criar: `service/composer.json`
- Criar: `service/.env` / `service/.env.example`
- Criar: `service/config/app.php`
- Criar: `service/config/database.php`
- Criar: `service/config/route.php`
- Criar: `service/config/middleware.php`
- Criar: `service/config/jwt.php`
- Criar: `service/config/hashids.php`
- Criar: `service/config/snowflake.php`
- Criar: `service/config/encryption.php`
- Criar: `service/config/encryptable.php`
- Criar: `service/config/scout.php`
- Criar: `service/config/security.php`
- Criar: `service/config/poster.php`
- Criar: `service/config/season.php`
- Criar: `service/config/autoload.php`
- Criar: `service/config/bootstrap.php`
- Criar: `service/config/container.php`
- Criar: `service/config/dependence.php`
- Criar: `service/config/exception.php`
- Criar: `service/config/log.php`
- Criar: `service/config/process.php`
- Criar: `service/config/server.php`
- Criar: `service/config/session.php`
- Criar: `service/config/static.php`
- Criar: `service/config/translation.php`
- Criar: `service/config/view.php`

- [ ] **Passo 1: criar composer.json**

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

- [ ] **Passo 2: instalar dependências**

```bash
cd /home/wwwroot/appointment-php/service && composer install
```

- [ ] **Passo 3: criar config/database.php** — partilha o mesmo MySQL com o admin

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

- [ ] **Passo 4: criar config/route.php**

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

- [ ] **Passo 5: criar config/middleware.php**

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

- [ ] **Passo 6: copiar os ficheiros de configuração partilhados de admin/config/**

Copiar `jwt.php`, `hashids.php`, `snowflake.php`, `encryption.php`, `encryptable.php`, `scout.php`, `season.php`, `poster.php` para `service/config/`, adicionando a declaração de copyright e comentários em chinês.

- [ ] **Passo 7: criar app/functions.php**

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

- [ ] **Passo 8: criar app/middleware/Cors.php**

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

- [ ] **Passo 9: criar o middleware Security**

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

- [ ] **Passo 10: criar o middleware Auth**

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

- [ ] **Passo 11: criar o middleware TechnicianAuth**

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

- [ ] **Passo 12: criar o BaseController** — resposta unificada + encriptação/desencriptação hashids

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

- [ ] **Passo 13: Commit**

```bash
git add service/
git commit -m "feat(service): initialize project skeleton with configs, middleware, base controller"
```

---

## Fase 1: Autenticação do utilizador e gestão de identidade

### Tarefa 1.1: criar o modelo User

- Criar: `service/app/model/User.php`

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

### Tarefa 1.2: criar o AuthController

- Criar: `service/app/api/AuthController.php`
- Implementar: registo com número de telemóvel (incluindo código de recomendação), início de sessão com palavra-passe, início de sessão com código de verificação, esquecer palavra-passe, alternância de identidade, início de sessão WeChat (fluxo de associação de telemóvel reservado)
- Modo convidado: controlado no frontend; é possível navegar sem iniciar sessão; o JWT é verificado ao criar a encomenda

### Tarefa 1.3: criar os controladores do módulo user

- Criar: `service/app/user/ProfileController.php`
- Criar: `service/app/user/AddressController.php`
- Criar: `service/app/user/FavoriteController.php`
- Criar: `service/app/user/FeedbackController.php`
- Criar: `service/app/user/ReferralController.php`

---

## Fase 2: Serviços e lojas

- Criar: `service/app/model/ServiceCategory.php`
- Criar: `service/app/model/Service.php` (inclui configuração do índice ES do webman-scout)
- Criar: `service/app/model/Product.php`
- Criar: `service/app/model/Store.php`
- Criar: `service/app/service/CategoryController.php`
- Criar: `service/app/service/ItemController.php`
- Criar: `service/app/service/SearchController.php` (pesquisa ES)
- Criar: `service/app/service/StoreController.php`

---

## Fase 3: Encomendas e pagamento

- Criar: `service/app/model/Order.php` + OrderItem/Payment/Refund/Review/Verification
- Criar: `service/app/order/CartController.php`
- Criar: `service/app/order/OrderController.php` — bloqueia o técnico durante 3 minutos com Redis SETNX ao criar a encomenda
- Criar: `service/app/order/PaymentController.php` (pagamento WeChat reservado)
- Criar: `service/app/order/VerificationController.php` (verificação por código QR)
- Criar: `service/app/order/ReviewController.php`
- Regras de reembolso: percentagens em quatro níveis 100%/90%/80%/0%

---

## Fase 4: Módulo do técnico

- Criar: `service/app/model/TechnicianProfile.php` + Schedule/Service/Earnings/Withdrawal/Attendance/MemberNote
- Criar: `service/app/technician/ProfileController.php` (candidatura de adesão + aprovação)
- Criar: `service/app/technician/ScheduleController.php`
- Criar: `service/app/technician/OrderController.php`
- Criar: `service/app/technician/MemberController.php`
- Criar: `service/app/technician/EarningsController.php`
- Criar: `service/app/technician/WithdrawalController.php` (dia 20 de cada mês/T+1)
- Criar: `service/app/technician/AttendanceController.php`

---

## Fase 5: Módulo de marketing

- Criar: `service/app/model/Coupon.php` + UserCoupon/MemberCard/UserMemberCard/MemberCardUsage/Points/GiftCard/Referral
- Criar: `service/app/marketing/CouponController.php` (emissão automática de cupão para novos utilizadores no registo)
- Criar: `service/app/marketing/MemberCardController.php` (cartão mensal/cartão anual VIP/cartão de vezes)
- Criar: `service/app/marketing/PointsController.php` (1:100 para trocar por cartão-presente)
- Criar: `service/app/marketing/GiftCardController.php`

---

## Fase 6: Conteúdos e notificações

- Criar: `service/app/model/Banner.php` + Announcement/Agreement/Faq/Feedback/Moment/Notification
- Criar: `service/app/content/BannerController.php`
- Criar: `service/app/content/AnnouncementController.php`
- Criar: `service/app/content/NotificationController.php`
- Criar: `service/app/lbs/LocationController.php`

---

## Fase 7: Extensões do painel de administração Admin

- Todos os controladores do lado da gestão estendem o `admin/app/admin/controller/BaseController.php` existente
- As operações sensíveis (eliminar/aprovar/levantar) usam `erikwang2013/poster-php` para adicionar verificação aleatória
- Dashboard: cartões de estatísticas em tempo real + gráficos de linha Chart.js + navegação rápida + mensagens intra-site
- Exportação Excel (utilizadores/técnicos/encomendas/financeiro), com desmascaramento automático de campos sensíveis
- Exportação PDF do painel
- Deteção da origem das operações: o middleware `OperationLog` já suporta, estendido para 8 origens

Novos controladores:
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

- Estender `admin/app/admin/controller/DashboardController.php` com estatísticas de negócio
- Estender `admin/app/admin/controller/ExportController.php` com exportação de dados de negócio
- Estender os dados de seed de permissões `admin/database/migrations/` com nós de permissões de negócio

---

## Fase 8: Frontend

- `apps/wechat/` — desenvolvimento de páginas do miniprograma WeChat (ver a estrutura de páginas do STRUCTURE.md)
- `apps/flutter/` — desenvolvimento de páginas do APP Flutter (estrutura consistente com o miniprograma)
- `admin/apps/flutter/` — novas páginas de negócio do Flutter Web do painel de administração

---

## Consulta rápida das normas técnicas

| Norma | Forma de implementação |
|------|----------|
| Cabeçalho de copyright | No topo de todos os ficheiros .php: `Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz` |
| Funções globais | Importar com `use`, sem `\` na frente |
| Encriptação/desencriptação de IDs | BaseController::encodeIds/decodeIds codificam/descodificam hashids automaticamente |
| Campos sensíveis de BD | A trait encryptable declara o array `$encryptable` |
| Dados sensíveis de API | O pacote encryption encripta/desencripta na fase de serialização |
| Pesquisa ES | webman-scout com `searchableAs()` no modelo + SearchController |
| Deteção de segurança | security-php como middleware global, 31 tipos de deteção de ataques |
| Verificação de operações sensíveis | poster-php apresenta a verificação antes das operações de eliminar/aprovar/levantar |
| Exportação Excel | ExportController usa PhpSpreadsheet, com desmascaramento de campos sensíveis |
| Exportação PDF do painel | As estatísticas do Dashboard são renderizadas em PDF |
| Deteção da origem | O middleware OperationLog converte User-Agent → string de 8 origens |
