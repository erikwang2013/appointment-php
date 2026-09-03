<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

use Webman\Route;
use support\Request;

/**
 * 业务API路由配置
 *
 * API 版本策略:
 * — 版本号固化在 URL 前缀中（/api/v1/...），不由请求头携带
 * — v() 闭包按组前缀绑定的版本解析 app/{module}/{version}/controller/ 控制器
 * — 顶层 /api/docs（OpenAPI 文档）、/payment/*（支付回调）、/health 为非版本化端点
 *
 * 新增版本: 注册 Route::group('/api/v{n}', ...) 组 + 创建 app/{module}/v{n}/controller/
 * 目录，v() 调用处传版本参数: v('module', 'Controller', 'action', 'v{n}')
 */

/**
 * 创建版本化路由闭包
 * @param string $module  模块名（api / user / technician / order / marketing / notification）
 * @param string $controller 控制器类名（如 AuthController）
 * @param string $action  方法名（如 login）
 * @param string $version 版本号，默认 v1（与 /api/v1 组前缀绑定；新增版本时显式传入）
 */
function v(string $module, string $controller, string $action, string $version = 'v1'): \Closure
{
    return function (Request $request) use ($module, $controller, $action, $version) {
        $class = "\\app\\{$module}\\{$version}\\controller\\{$controller}";
        $instance = new $class;
        $method = new ReflectionMethod($instance, $action);
        $pathParams = $request->route ? array_values($request->route->param()) : [];
        $args = [];
        $i = 0;
        foreach ($method->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof ReflectionNamedType && is_a(Request::class, $type->getName(), true)) {
                $args[] = $request;
            } else {
                $args[] = $pathParams[$i] ?? null;
                $i++;
            }
        }
        return $instance->{$action}(...$args);
    };
}

// ============================================================
// API 文档（OpenAPI 3.0 JSON，非版本化基础设施端点，与 admin /api/docs 对齐）
// ============================================================
Route::get('/api/docs', [app\api\v1\controller\DocsController::class, 'index']);

// ============================================================
// 公开接口（URL 版本 /api/v1，无需认证）
// ============================================================
Route::group('/api/v1', function () {
    // ── 短信验证码 ──
    Route::post('/captcha/send', v('api', 'CaptchaController', 'send'));

    // ── 认证 ──
    Route::post('/auth/login', v('api', 'AuthController', 'login'));
    Route::post('/auth/login-by-code', v('api', 'AuthController', 'loginByCode'));
    Route::post('/auth/register', v('api', 'AuthController', 'register'));
    Route::post('/auth/forget-password', v('api', 'AuthController', 'forgetPassword'));
    Route::post('/auth/refresh', v('api', 'AuthController', 'refresh'));

    // ── 微信 ──
    Route::post('/wechat/mini-login', v('api', 'WechatController', 'miniLogin'));
    Route::post('/wechat/phone', v('api', 'WechatController', 'phone'));
    Route::post('/wechat/oa-login', v('api', 'WechatController', 'oaLogin'));

    // ── 公共服务 ──
    Route::get('/common/config', v('api', 'CommonController', 'config'));
    Route::get('/common/area', v('api', 'CommonController', 'area'));

    // ── 服务查询 ──
    Route::get('/service/categories', v('api', 'ServiceController', 'categories'));
    Route::get('/service/items', v('api', 'ServiceController', 'items'));
    Route::get('/service/products', v('api', 'ServiceController', 'products'));
    Route::get('/service/stores', v('api', 'ServiceController', 'stores'));
    Route::get('/service/detail/{id}', v('api', 'ServiceController', 'detail'));

    // ── 技师查询 ──
    Route::get('/technician/list', v('api', 'TechnicianController', 'list'));
    Route::get('/technician/detail/{id}', v('api', 'TechnicianController', 'detail'));
    Route::get('/technician/schedule/{id}', v('api', 'TechnicianController', 'schedule'));

    // ── 预约月历 ──
    Route::get('/calendar/technician/{id}', v('api', 'CalendarController', 'month'));
    Route::get('/calendar/technician/{id}/day', v('api', 'CalendarController', 'day'));

    // ── 内容 ──
    Route::get('/content/articles', v('api', 'ContentController', 'articles'));
    Route::get('/content/article/{id}', v('api', 'ContentController', 'articleDetail'));
    Route::get('/content/banners', v('api', 'ContentController', 'banners'));

    // ── LBS ──
    Route::get('/lbs/nearby-stores', v('api', 'LbsController', 'nearbyStores'));
    Route::get('/lbs/geocode', v('api', 'LbsController', 'geocode'));

    // ── 服务套餐（公开浏览）──
    Route::get('/service-packages', v('api', 'ServicePackageController', 'index'));
    Route::get('/service-packages/{id}', v('api', 'ServicePackageController', 'show'));

    // ── 促销活动（公开浏览）──
    Route::get('/promotions', v('api', 'PromotionController', 'index'));
    Route::get('/promotions/{id}', v('api', 'PromotionController', 'show'));
    Route::get('/promotions/{id}/participants', v('api', 'PromotionController', 'participants'));

    // ── 满减活动（公开浏览）──
    Route::get('/full-reduction-activities', v('api', 'FullReductionController', 'index'));

    // ── 短视频（公开浏览）──
    Route::get('/video/list', v('api', 'VideoController', 'index'));
    Route::get('/video/detail/{id}', v('api', 'VideoController', 'show'));

    // ── 社区圈子（公开浏览）──
    Route::get('/community', v('api', 'CommunityController', 'index'));
    Route::get('/community/detail/{id}', v('api', 'CommunityController', 'show'));
    Route::get('/community/comment/list/{post_id}', v('api', 'CommunityCommentController', 'index'));

    // ── 成长等级（公开浏览）──
    Route::get('/growth/levels', v('user', 'GrowthController', 'levels'));

    // ── APP 检测更新（公开，登录前即可检查）──
    Route::get('/app/version', v('api', 'VersionController', 'index'));
});

// ============================================================
// 用户接口（JWT认证）
// ============================================================
Route::group('/api/v1/user', function () {
    Route::get('/profile', v('user', 'ProfileController', 'show'));
    Route::put('/profile', v('user', 'ProfileController', 'update'));
    Route::post('/change-password', v('user', 'ProfileController', 'changePassword'));
    Route::post('/change-phone', v('user', 'ProfileController', 'changePhone'));
    Route::post('/cancel-account', v('user', 'ProfileController', 'cancelAccount'));
    Route::post('/logout', v('user', 'ProfileController', 'logout'));
    Route::post('/switch-role', v('api', 'AuthController', 'switchRole'));

    Route::get('/addresses', v('user', 'AddressController', 'index'));
    Route::post('/addresses', v('user', 'AddressController', 'store'));
    Route::get('/addresses/{id}', v('user', 'AddressController', 'show'));
    Route::put('/addresses/{id}', v('user', 'AddressController', 'update'));
    Route::delete('/addresses/{id}', v('user', 'AddressController', 'destroy'));

    Route::get('/favorites', v('user', 'FavoriteController', 'index'));
    Route::post('/favorites', v('user', 'FavoriteController', 'store'));
    Route::delete('/favorites/{id}', v('user', 'FavoriteController', 'destroy'));

    Route::post('/feedback', v('user', 'FeedbackController', 'store'));

    Route::get('/referral', v('user', 'ReferralController', 'index'));
    Route::get('/referral/qrcode', v('user', 'ReferralController', 'qrcode'));
    Route::get('/referral/referred-users', v('user', 'ReferralController', 'referredUsers'));
    Route::get('/referral/earnings', v('user', 'ReferralController', 'earnings'));

    Route::post('/points/transfer', v('user', 'PointsTransferController', 'transfer'));
    Route::get('/points/transfers', v('user', 'PointsTransferController', 'records'));

    Route::post('/check-in', v('user', 'CheckInController', 'store'));
    Route::get('/check-in/status', v('user', 'CheckInController', 'status'));

    Route::post('/service-packages/buy', v('api', 'ServicePackageController', 'buy'));
    Route::post('/promotions/join/{id}', v('api', 'PromotionController', 'join'));

    // ── 消息偏好设置 ──
    Route::get('/notify-settings', v('user', 'NotifySettingController', 'index'));
    Route::put('/notify-settings', v('user', 'NotifySettingController', 'update'));

    // ── 设备推送 ──
    Route::post('/device/register', v('user', 'DeviceController', 'register'));
    Route::post('/device/unregister', v('user', 'DeviceController', 'unregister'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 积分幸运转盘（JWT）——奖品 / 抽奖 / 记录
// ============================================================
Route::group('/api/v1/wheel', function () {
    Route::get('/prizes', v('api', 'WheelController', 'prizes'));
    Route::post('/spin', v('api', 'WheelController', 'spin'));
    Route::get('/records', v('api', 'WheelController', 'records'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 游客模式（只读，无需认证）——未登录浏览
// ============================================================
Route::group('/api/v1/guest', function () {
    Route::get('/home', v('api', 'GuestController', 'home'));
    Route::get('/services', v('api', 'GuestController', 'services'));
    Route::get('/services/{id}', v('api', 'GuestController', 'serviceDetail'));
    Route::get('/stores', v('api', 'GuestController', 'stores'));
    Route::get('/technicians', v('api', 'GuestController', 'technicians'));
});

// ============================================================
// 秒杀接口（JWT）——活动列表 / 详情 / 抢购下单
// ============================================================
Route::group('/api/v1/seckill', function () {
    Route::get('/', v('api', 'SeckillController', 'index'));
    Route::get('/{id}', v('api', 'SeckillController', 'show'));
    Route::post('/{id}/buy', v('api', 'SeckillController', 'buy'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 隐私合规接口（JWT）——数据导出 / 账号注销闭环
// ============================================================
Route::group('/api/v1/privacy', function () {
    Route::get('/data', v('api', 'PrivacyController', 'data'));
    Route::post('/close-request', v('api', 'PrivacyController', 'closeRequest'));
    Route::post('/close-cancel', v('api', 'PrivacyController', 'closeCancel'));
    Route::post('/close-confirm', v('api', 'PrivacyController', 'closeConfirm'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 成长体系接口（JWT认证）
// ============================================================
Route::group('/api/v1/growth', function () {
    Route::get('/', v('user', 'GrowthController', 'index'));
    Route::get('/records', v('user', 'GrowthController', 'records'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 用户健康档案接口（JWT认证）
// ============================================================
Route::group('/api/v1/health-profile', function () {
    Route::get('/', v('user', 'HealthProfileController', 'show'));
    Route::put('/', v('user', 'HealthProfileController', 'upsert'));
    Route::delete('/', v('user', 'HealthProfileController', 'destroy'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 技师接口（JWT + 技师身份）
// ============================================================
Route::group('/api/v1/technician', function () {
    Route::get('/profile', v('technician', 'ProfileController', 'show'));
    Route::put('/profile', v('technician', 'ProfileController', 'update'));
    Route::get('/schedule', v('technician', 'ScheduleController', 'index'));
    Route::put('/schedule', v('technician', 'ScheduleController', 'update'));
    Route::post('/schedule/batch', v('technician', 'ScheduleController', 'batch'));
    Route::get('/orders', v('technician', 'OrderController', 'index'));
    Route::get('/earnings', v('technician', 'EarningController', 'index'));
    Route::post('/withdraw', v('technician', 'WithdrawController', 'store'));

    // ── 技师工作台（第 8 轮：今日任务 / 核销记录 / 开始服务 / 完成服务）──
    Route::get('/work/today', v('technician', 'WorkController', 'today'));
    Route::get('/work/records', v('technician', 'WorkController', 'records'));
    Route::post('/work/{id}/start', v('technician', 'WorkController', 'start'));
    Route::post('/work/{id}/complete', v('technician', 'WorkController', 'complete'));

    Route::post('/service-records', v('technician', 'ServiceRecordController', 'store'));
    Route::get('/service-records/{id}', v('technician', 'ServiceRecordController', 'show'));

    // ── 考试系统 ──
    Route::get('/exams', v('technician', 'ExamController', 'index'));
    Route::post('/exam/start/{id}', v('technician', 'ExamController', 'start'));
    Route::post('/exam/submit/{id}', v('technician', 'ExamController', 'submit'));

    // ── 评价回复 ──
    Route::post('/review/reply/{order_id}', v('technician', 'ReviewController', 'reply'));

    // ── 技师考勤打卡 ──
    Route::get('/attendance', v('technician', 'AttendanceController', 'index'));
    Route::post('/attendance/check-in', v('technician', 'AttendanceController', 'checkIn'));
    Route::post('/attendance/check-out', v('technician', 'AttendanceController', 'checkOut'));
})->middleware([
    app\middleware\Auth::class,
    app\middleware\TechnicianAuth::class,
]);

// ============================================================
// 订单接口（JWT）
// ============================================================
Route::group('/api/v1/order', function () {
    Route::post('/', v('order', 'OrderController', 'store'));
    Route::get('/list', v('order', 'OrderController', 'index'));
    // ── ICS 日历导出（我的预约，iCal 下载/导入手机日历）──
    Route::get('/ics', v('order', 'IcsController', 'export'));
    Route::get('/detail/{id}', v('order', 'OrderController', 'show'));
    Route::get('/logistics/{id}', v('order', 'OrderController', 'logistics'));
    // ── 订单状态时间线（仅本人，倒序：最新在前）──
    Route::get('/{id}/timeline', v('order', 'TimelineController', 'show'));
    Route::post('/cancel/{id}', v('order', 'OrderController', 'cancel'));
    Route::post('/pay/{id}', v('order', 'OrderController', 'pay'));
    Route::post('/refund/{id}', v('order', 'OrderController', 'refund'));
    Route::post('/reschedule/{id}', v('order', 'OrderController', 'reschedule'));
    // @deprecated 遗留入口（核销码走 URL 路径），仅保留兼容，不再推荐使用；新入口见下方 verify-by-code
    Route::post('/verify/{id}', v('order', 'OrderController', 'verify'));
    // 扫码核销（技师端，推荐唯一入口）：核销码放请求体，POST body {code}
    Route::post('/verify-by-code', v('order', 'OrderController', 'verifyByCode'));

    // ── 订单评价（用户提交）＋追评（第 19 轮：已完成评价追加内容/图片，只可追评一次）──
    Route::post('/review/{order_id}', v('order', 'ReviewController', 'store'));
    Route::post('/review/{order_id}/append', v('order', 'ReviewController', 'append'));

    Route::post('/waitlist', v('order', 'WaitlistController', 'store'));
    Route::get('/waitlist', v('order', 'WaitlistController', 'index'));
    Route::post('/waitlist/cancel/{id}', v('order', 'WaitlistController', 'cancel'));

    Route::post('/signature', v('order', 'SignatureController', 'store'));
    Route::get('/signature/{order_id}', v('order', 'SignatureController', 'show'));

    // ── 购物车（Redis 存储，键 cart:{user_id}）──
    Route::get('/cart', v('order', 'CartController', 'index'));
    Route::post('/cart', v('order', 'CartController', 'store'));
    Route::delete('/cart', v('order', 'CartController', 'destroy'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 售后（退换货）接口（JWT）
// ============================================================
Route::group('/api/v1/aftersales', function () {
    Route::post('/', v('order', 'AftersaleController', 'store'));
    Route::get('/', v('order', 'AftersaleController', 'index'));
    Route::get('/{id}', v('order', 'AftersaleController', 'show'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 客服工单接口（JWT）
// ============================================================
Route::group('/api/v1/tickets', function () {
    Route::post('/', v('user', 'TicketController', 'store'));
    Route::get('/', v('user', 'TicketController', 'index'));
    Route::get('/{id}', v('user', 'TicketController', 'show'));
    Route::post('/{id}/close', v('user', 'TicketController', 'close'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 钱包接口（JWT）——储值支付余额体系
// ============================================================
Route::group('/api/v1/wallet', function () {
    Route::get('/', v('wallet', 'WalletController', 'index'));
    Route::post('/recharge', v('wallet', 'WalletController', 'recharge'));
    Route::post('/recharge/{id}/pay', v('wallet', 'WalletController', 'pay'));
    Route::get('/txns', v('wallet', 'WalletController', 'txns'));
    Route::post('/pay-password/set', v('wallet', 'WalletController', 'setPayPassword'));
    Route::post('/pay-password/verify', v('wallet', 'WalletController', 'verifyPayPassword'));
    Route::post('/pay-password/check', v('wallet', 'WalletController', 'checkPayPassword'));
    Route::post('/transfer', v('wallet', 'WalletTransferController', 'transfer'));
    Route::get('/transfers', v('wallet', 'WalletTransferController', 'transfers'));
    Route::get('/transfers/{id}', v('wallet', 'WalletTransferController', 'show'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 营销接口（JWT）
// ============================================================
Route::group('/api/v1/marketing', function () {
    Route::get('/coupons', v('marketing', 'CouponController', 'index'));
    Route::post('/coupons/receive', v('marketing', 'CouponController', 'receive'));
    Route::post('/coupons/transfer', v('marketing', 'CouponController', 'transfer'));
    Route::post('/coupons/claim', v('marketing', 'CouponController', 'claim'));
    Route::get('/coupons/transfers', v('marketing', 'CouponController', 'transfers'));
    Route::get('/cards', v('marketing', 'CardController', 'index'));
    Route::post('/cards/buy', v('marketing', 'CardController', 'buy'));
    Route::get('/cards/my', v('marketing', 'CardController', 'my'));
    Route::post('/cards/use', v('marketing', 'CardController', 'use'));
    Route::get('/points', v('marketing', 'PointController', 'index'));
    Route::get('/gift-cards', v('marketing', 'GiftCardController', 'index'));
    Route::get('/gift-cards/my', v('marketing', 'GiftCardController', 'my'));
    Route::post('/gift-cards/redeem', v('marketing', 'GiftCardController', 'redeem'));
    Route::post('/gift-cards/store', v('marketing', 'GiftCardController', 'store'));

    // ── 积分兑换商城 ──
    Route::get('/points-exchange', v('marketing', 'PointsExchangeController', 'index'));
    Route::post('/points-exchange/{id}', v('marketing', 'PointsExchangeController', 'exchange'));

    Route::get('/benefits', v('marketing', 'MemberBenefitController', 'index'));
    Route::get('/benefits/birthday', v('marketing', 'MemberBenefitController', 'birthday'));
    Route::get('/member-cards', v('marketing', 'MemberCardController', 'index'));
    Route::post('/member-cards/buy', v('marketing', 'MemberCardController', 'buy'));
    Route::get('/member-cards/my', v('marketing', 'MemberCardController', 'my'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 通知接口（JWT）
// ============================================================
Route::group('/api/v1/notification', function () {
    Route::get('/', v('notification', 'NotificationController', 'index'));
    Route::put('/read/{id}', v('notification', 'NotificationController', 'read'));
    Route::put('/read-all', v('notification', 'NotificationController', 'readAll'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 电子发票接口（JWT）
// ============================================================
Route::group('/api/v1/invoices', function () {
    Route::post('/', v('user', 'InvoiceController', 'store'));
    Route::get('/', v('user', 'InvoiceController', 'index'));
    Route::get('/{id}', v('user', 'InvoiceController', 'show'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 常用发票抬头接口（JWT）
// ============================================================
Route::group('/api/v1/invoice-titles', function () {
    Route::post('/', v('user', 'InvoiceTitleController', 'store'));
    Route::get('/', v('user', 'InvoiceTitleController', 'index'));
    Route::put('/{id}', v('user', 'InvoiceTitleController', 'update'));
    Route::delete('/{id}', v('user', 'InvoiceTitleController', 'destroy'));
    Route::post('/{id}/default', v('user', 'InvoiceTitleController', 'setDefault'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 浏览足迹接口（JWT）——最近浏览服务
// ============================================================
Route::group('/api/v1/browse-history', function () {
    Route::get('/', v('api', 'BrowseHistoryController', 'index'));
    Route::delete('/', v('api', 'BrowseHistoryController', 'clear'));
    Route::delete('/{item_id}', v('api', 'BrowseHistoryController', 'destroy'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 社区圈子接口（JWT）
// ============================================================
Route::group('/api/v1/community', function () {
    Route::post('/', v('api', 'CommunityController', 'store'));
    Route::post('/like/{id}', v('api', 'CommunityController', 'like'));
    Route::get('/my-posts', v('api', 'CommunityController', 'myPosts'));

    Route::post('/comment', v('api', 'CommunityCommentController', 'store'));
    Route::delete('/comment/{id}', v('api', 'CommunityCommentController', 'destroy'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 短视频接口（JWT）
// ============================================================
Route::group('/api/v1/video', function () {
    Route::post('/like/{id}', v('api', 'VideoController', 'like'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 门店排队叫号接口（JWT）
// ============================================================
Route::group('/api/v1/queue', function () {
    Route::post('/take', v('api', 'QueueController', 'take'));
    Route::get('/current', v('api', 'QueueController', 'current'));
    Route::get('/store/{store_id}', v('api', 'QueueController', 'storeQueue'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 店长工作台接口（JWT）——门店数据按登录用户 store_id 隔离
// ============================================================
Route::group('/api/v1/store-manager', function () {
    Route::get('/overview', v('api', 'StoreManagerController', 'overview'));
    Route::get('/orders', v('api', 'StoreManagerController', 'orders'));
    Route::get('/technicians', v('api', 'StoreManagerController', 'technicians'));
    Route::get('/revenue', v('api', 'StoreManagerController', 'revenue'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 支付回调接口（不使用版本控制中间件，不进行JWT认证）
// ============================================================
Route::any('/payment/wechat-notify', [app\api\v1\controller\PaymentNotifyController::class, 'wechatNotify']);
Route::any('/payment/alipay-notify', [app\api\v1\controller\PaymentNotifyController::class, 'alipayNotify']);

// ============================================================
// 打印接口（JWT）
// ============================================================
Route::group('/api/v1/print', function () {
    Route::get('/receipt/{order_id}', v('api', 'PrintController', 'receipt'));
    Route::get('/preview/{order_id}', v('api', 'PrintController', 'preview'));
})->middleware([
    app\middleware\Auth::class,
]);

// ============================================================
// 健康检查（全局，无需认证，供 docker-compose healthcheck / 负载均衡探活）
// ============================================================
Route::get('/health', function () {
    return json(['code' => 0, 'message' => 'ok']);
});

// 关闭默认路由
Route::disableDefaultRoute();
