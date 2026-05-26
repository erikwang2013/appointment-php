<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

use Webman\Route;

/**
 * 业务API路由配置
 *
 * 路由分组说明:
 * - /api/*  公开接口（无需认证），包含：认证、验证码、微信、公共服务、技师查询、内容、LBS
 * - /api/user/*  用户接口（需要 JWT 认证）
 * - /api/technician/*  技师接口（需要 JWT 认证 + 技师身份校验）
 * - /api/order/*  订单接口（需要 JWT 认证）
 * - /api/marketing/*  营销接口（需要 JWT 认证）
 * - /api/notification/*  通知接口（需要 JWT 认证）
 */

// ============================================================
// 公开接口（无需认证）
// ============================================================
Route::group('/api', function () {
    // ── 点击验证码 ──
    Route::post('/captcha/generate', [app\api\controller\CaptchaController::class, 'generate']);
    Route::post('/captcha/verify', [app\api\controller\CaptchaController::class, 'verify']);

    // ── 认证：登录/注册/刷新 ──
    Route::post('/auth/login', [app\api\controller\AuthController::class, 'login']);
    Route::post('/auth/register', [app\api\controller\AuthController::class, 'register']);
    Route::post('/auth/refresh', [app\api\controller\AuthController::class, 'refresh']);

    // ── 微信 ──
    Route::post('/wechat/mini-login', [app\api\controller\WechatController::class, 'miniLogin']);
    Route::post('/wechat/phone', [app\api\controller\WechatController::class, 'phone']);
    Route::post('/wechat/oa-login', [app\api\controller\WechatController::class, 'oaLogin']);

    // ── 公共服务 ──
    Route::get('/common/config', [app\api\controller\CommonController::class, 'config']);
    Route::get('/common/area', [app\api\controller\CommonController::class, 'area']);

    // ── 服务查询（公开） ──
    Route::get('/service/categories', [app\api\controller\ServiceController::class, 'categories']);
    Route::get('/service/items', [app\api\controller\ServiceController::class, 'items']);
    Route::get('/service/products', [app\api\controller\ServiceController::class, 'products']);
    Route::get('/service/stores', [app\api\controller\ServiceController::class, 'stores']);
    Route::get('/service/detail/{id}', [app\api\controller\ServiceController::class, 'detail']);

    // ── 技师查询（公开） ──
    Route::get('/technician/list', [app\api\controller\TechnicianController::class, 'list']);
    Route::get('/technician/detail/{id}', [app\api\controller\TechnicianController::class, 'detail']);
    Route::get('/technician/schedule/{id}', [app\api\controller\TechnicianController::class, 'schedule']);

    // ── 内容 ──
    Route::get('/content/articles', [app\api\controller\ContentController::class, 'articles']);
    Route::get('/content/article/{id}', [app\api\controller\ContentController::class, 'articleDetail']);
    Route::get('/content/banners', [app\api\controller\ContentController::class, 'banners']);

    // ── LBS ──
    Route::get('/lbs/nearby-stores', [app\api\controller\LbsController::class, 'nearbyStores']);
    Route::get('/lbs/geocode', [app\api\controller\LbsController::class, 'geocode']);
});

// ============================================================
// 用户接口（需要 JWT 认证）
// ============================================================
Route::group('/api/user', function () {
    // 个人资料
    Route::get('/profile', [app\user\controller\ProfileController::class, 'show']);
    Route::put('/profile', [app\user\controller\ProfileController::class, 'update']);
    // 地址管理
    Route::get('/addresses', [app\user\controller\AddressController::class, 'index']);
    Route::post('/addresses', [app\user\controller\AddressController::class, 'store']);
    Route::put('/addresses/{id}', [app\user\controller\AddressController::class, 'update']);
    Route::delete('/addresses/{id}', [app\user\controller\AddressController::class, 'destroy']);
    // 车辆管理
    Route::get('/vehicles', [app\user\controller\VehicleController::class, 'index']);
    Route::post('/vehicles', [app\user\controller\VehicleController::class, 'store']);
    Route::put('/vehicles/{id}', [app\user\controller\VehicleController::class, 'update']);
    Route::delete('/vehicles/{id}', [app\user\controller\VehicleController::class, 'destroy']);
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 技师接口（需要 JWT 认证 + 技师身份校验）
// ============================================================
Route::group('/api/technician', function () {
    Route::get('/profile', [app\technician\controller\ProfileController::class, 'show']);
    Route::put('/profile', [app\technician\controller\ProfileController::class, 'update']);
    Route::get('/schedule', [app\technician\controller\ScheduleController::class, 'index']);
    Route::put('/schedule', [app\technician\controller\ScheduleController::class, 'update']);
    Route::get('/orders', [app\technician\controller\OrderController::class, 'index']);
    Route::get('/earnings', [app\technician\controller\EarningController::class, 'index']);
    Route::post('/withdraw', [app\technician\controller\WithdrawController::class, 'store']);
})->middleware([
    app\middleware\Auth::class,
    app\middleware\TechnicianAuth::class,
]);

// ============================================================
// 订单接口（需要 JWT 认证）
// ============================================================
Route::group('/api/order', function () {
    Route::post('/', [app\order\controller\OrderController::class, 'store']);
    Route::get('/list', [app\order\controller\OrderController::class, 'index']);
    Route::get('/detail/{id}', [app\order\controller\OrderController::class, 'show']);
    Route::post('/cancel/{id}', [app\order\controller\OrderController::class, 'cancel']);
    Route::post('/pay/{id}', [app\order\controller\OrderController::class, 'pay']);
    Route::post('/refund/{id}', [app\order\controller\OrderController::class, 'refund']);
    Route::post('/verify/{id}', [app\order\controller\OrderController::class, 'verify']);
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 营销接口（需要 JWT 认证）
// ============================================================
Route::group('/api/marketing', function () {
    Route::get('/coupons', [app\marketing\controller\CouponController::class, 'index']);
    Route::post('/coupons/receive', [app\marketing\controller\CouponController::class, 'receive']);
    Route::get('/cards', [app\marketing\controller\CardController::class, 'index']);
    Route::post('/cards/buy', [app\marketing\controller\CardController::class, 'buy']);
    Route::get('/points', [app\marketing\controller\PointController::class, 'index']);
    Route::get('/gift-cards', [app\marketing\controller\GiftCardController::class, 'index']);
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 通知接口（需要 JWT 认证）
// ============================================================
Route::group('/api/notification', function () {
    Route::get('/', [app\notification\controller\NotificationController::class, 'index']);
    Route::put('/read/{id}', [app\notification\controller\NotificationController::class, 'read']);
    Route::put('/read-all', [app\notification\controller\NotificationController::class, 'readAll']);
})->middleware([
    app\middleware\Auth::class,
]);

// 关闭默认路由，避免意外的路由匹配
Route::disableDefaultRoute();
