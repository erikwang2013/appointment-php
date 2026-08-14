<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

use Webman\Route;
use support\Request;

/**
 * API 路由配置
 *
 * 路由分组说明:
 * - /admin/*  管理端接口，需要 JWT 认证 + 权限校验
 * - /api/*    客户端接口（部分白名单，部分需认证）
 * - /health   健康检查（无需认证）
 *
 * API 版本策略:
 * - 版本号通过请求头 API-Version 携带（如 "v1"、"v2"），不在 URL 中体现
 * - 缺失时默认使用 v1
 * - 由 ApiVersion 中间件校验，路由闭包按版本解析对应控制器
 */

/**
 * 创建版本化 API 路由闭包
 */
function v(string $controller, string $action): \Closure
{
    return function (Request $request) use ($controller, $action) {
        $version = $request->apiVersion ?? 'v1';
        $class = "\\app\\api\\{$version}\\controller\\{$controller}";
        return (new $class)->{$action}($request);
    };
}

// ============================================================
// 安装向导（无需认证，安装完成后建议删除本路由）
// ============================================================
Route::any('/install', [app\admin\controller\InstallController::class, 'index']);

// ============================================================
// 健康检查（全局，无需认证）
// ============================================================
Route::get('/health', [app\admin\controller\HealthController::class, 'index']);

// Prometheus 指标（无需认证）
Route::get('/metrics', [app\admin\controller\MetricsController::class, 'index']);

// security.txt — RFC 9116 安全漏洞报告联系人
Route::get('/.well-known/security.txt', function () {
    return response(<<<'TXT'
Contact: mailto:erik@erik.xyz
Expires: 2027-12-31T23:59:59Z
Preferred-Languages: zh, en
Canonical: https://erik.xyz/.well-known/security.txt
TXT
    , 200, ['Content-Type' => 'text/plain; charset=utf-8']);
});

// API 文档（全局，无需认证）
Route::get('/api/docs', [app\admin\controller\DocsController::class, 'index']);

// ============================================================
// 管理端路由
// ============================================================
Route::group('/admin', function () {
    // 仪表盘
    Route::get('/dashboard', [app\admin\controller\DashboardController::class, 'index']);

    // 用户管理
    Route::resource('/user', app\admin\controller\UserController::class);
    Route::post('/user/batch/destroy', [app\admin\controller\UserController::class, 'batchDestroy']);
    Route::post('/user/batch/status', [app\admin\controller\UserController::class, 'batchStatus']);

    // 角色管理
    Route::resource('/role', app\admin\controller\RoleController::class);

    // 权限管理
    Route::resource('/permission', app\admin\controller\PermissionController::class);

    // 系统配置
    Route::get('/config', [app\admin\controller\ConfigController::class, 'index']);
    Route::post('/config', [app\admin\controller\ConfigController::class, 'store']);
    Route::put('/config/{id}', [app\admin\controller\ConfigController::class, 'update']);
    Route::delete('/config/{id}', [app\admin\controller\ConfigController::class, 'destroy']);

    // 操作日志
    Route::get('/log', [app\admin\controller\LogController::class, 'index']);

    // 个人中心
    Route::put('/profile', [app\admin\controller\ProfileController::class, 'updateProfile']);
    Route::put('/profile/password', [app\admin\controller\ProfileController::class, 'updatePassword']);
    Route::post('/profile/logout', [app\admin\controller\ProfileController::class, 'logout']);

    // 导出
    Route::post('/export/excel', [app\admin\controller\ExportController::class, 'excel']);
    Route::post('/export/pdf', [app\admin\controller\ExportController::class, 'pdf']);

    // 导入
    Route::post('/import/users', [app\admin\controller\ImportController::class, 'users']);

    // 文件上传
    Route::post('/upload', [app\admin\controller\UploadController::class, 'upload']);

    // ============================================================
    // 业务管理路由
    // ============================================================

    // 门店管理
    Route::resource('/stores', app\admin\controller\StoreController::class);
    Route::put('/stores/{id}/toggle-status', [app\admin\controller\StoreController::class, 'toggleStatus']);

    // 技师管理（静态路由必须先于 resource 定义，避免被 {id} 变量路由 shadow）
    Route::get('/technicians/export-schedules', [app\admin\controller\TechnicianController::class, 'exportSchedules']);
    Route::get('/technicians/export-attendance', [app\admin\controller\TechnicianController::class, 'exportAttendance']);
    Route::resource('/technicians', app\admin\controller\TechnicianController::class);
    Route::post('/technicians/{id}/audit', [app\admin\controller\TechnicianController::class, 'audit']);
    Route::get('/technicians/{id}/schedules', [app\admin\controller\TechnicianController::class, 'schedules']);
    Route::post('/technicians/{id}/schedules', [app\admin\controller\TechnicianController::class, 'schedules']);
    Route::get('/technicians/{id}/services', [app\admin\controller\TechnicianController::class, 'services']);
    Route::post('/technicians/{id}/services', [app\admin\controller\TechnicianController::class, 'services']);
    Route::get('/technicians/{id}/export', [app\admin\controller\TechnicianController::class, 'export']);

    // 服务分类
    Route::resource('/service-categories', app\admin\controller\ServiceCategoryController::class);

    // 服务项目
    Route::resource('/services', app\admin\controller\ServiceController::class);

    // 产品管理
    Route::resource('/products', app\admin\controller\ProductController::class);

    // 订单管理
    Route::resource('/appointment-orders', app\admin\controller\AppointmentOrderController::class);

    // 商城订单
    Route::resource('/mall-orders', app\admin\controller\MallOrderController::class);

    // 优惠券
    Route::resource('/coupons', app\admin\controller\CouponController::class);

    // 会员管理
    Route::resource('/members', app\admin\controller\MemberController::class);

    // 提现管理
    Route::resource('/withdrawals', app\admin\controller\WithdrawalController::class);
    Route::post('/withdrawals/{id}/approve', [app\admin\controller\WithdrawalController::class, 'approve']);
    Route::post('/withdrawals/{id}/reject', [app\admin\controller\WithdrawalController::class, 'reject']);
    Route::post('/withdrawals/{id}/complete', [app\admin\controller\WithdrawalController::class, 'complete']);

    // 提现账户
    Route::resource('/withdrawal-accounts', app\admin\controller\WithdrawalAccountController::class);

    // 提现配置
    Route::get('/withdrawal-config', [app\admin\controller\WithdrawalConfigController::class, 'show']);
    Route::put('/withdrawal-config/{id}', [app\admin\controller\WithdrawalConfigController::class, 'update']);

    // 佣金管理
    Route::resource('/commissions', app\admin\controller\CommissionController::class);

    // 财务管理
    Route::resource('/finances', app\admin\controller\FinanceController::class);

    // 销售统计
    Route::get('/sales-stats', [app\admin\controller\SalesStatsController::class, 'index']);

    // 轮播图
    Route::resource('/banners', app\admin\controller\BannerController::class);

    // 公告
    Route::resource('/announcements', app\admin\controller\AnnouncementController::class);

    // 协议
    Route::resource('/agreements', app\admin\controller\AgreementController::class);

    // FAQ
    Route::resource('/faqs', app\admin\controller\FaqController::class);

    // 反馈
    Route::resource('/feedbacks', app\admin\controller\FeedbackController::class);

    // 朋友圈
    Route::resource('/moments', app\admin\controller\MomentController::class);

    // 系统消息
    Route::resource('/system-messages', app\admin\controller\SystemMessageController::class);

    // 关于我们
    Route::get('/about', [app\admin\controller\AboutController::class, 'show']);
    Route::post('/about', [app\admin\controller\AboutController::class, 'update']);

    // ============================================================
    // 新增管理扩展路由
    // ============================================================

    // 店长子账号管理
    Route::get('/store-managers', [app\admin\controller\StoreManagerController::class, 'index']);
    Route::post('/store-managers', [app\admin\controller\StoreManagerController::class, 'store']);
    Route::put('/store-managers/{id}', [app\admin\controller\StoreManagerController::class, 'update']);
    Route::delete('/store-managers/{id}', [app\admin\controller\StoreManagerController::class, 'destroy']);

    // 培训课程
    Route::resource('/training-courses', app\admin\controller\TrainingController::class);
    Route::get('/training/progress/{technician_id}', [app\admin\controller\TrainingController::class, 'progress']);
    Route::post('/training/remind/{technician_id}', [app\admin\controller\TrainingController::class, 'remind']);

    // 调度任务
    // M9: auto-cancel 已下线——与 service 端 AutoCancelTimer（30s 有锁扫描）重复，
    // 统一由 service 进程驱动；其余任务仍为 HTTP 触发（需 cron 接入）
    Route::get('/scheduled-tasks', [app\admin\controller\ScheduledTaskController::class, 'index']);
    Route::post('/scheduled-tasks/auto-settle', [app\admin\controller\ScheduledTaskController::class, 'autoSettle']);
    Route::post('/scheduled-tasks/expire-coupons', [app\admin\controller\ScheduledTaskController::class, 'expireCoupons']);
    Route::post('/scheduled-tasks/expire-member-cards', [app\admin\controller\ScheduledTaskController::class, 'expireMemberCards']);

    // 客户画像
    Route::get('/customer-profiles/segments', [app\admin\controller\CustomerProfileController::class, 'segments']);
    Route::get('/customer-profiles/{user_id}', [app\admin\controller\CustomerProfileController::class, 'show']);

    // 批量消息
    Route::get('/batch-messages/templates', [app\admin\controller\BatchMessageController::class, 'templates']);
    Route::get('/batch-messages/history', [app\admin\controller\BatchMessageController::class, 'history']);
    Route::post('/batch-messages/send', [app\admin\controller\BatchMessageController::class, 'send']);

    // M6: 退款审批工作流已下线——该控制器操作的是技师提现（与用户退款无关），
    // 用户退款由 service 端同步自动退款闭环完成，路由与控制器均已废弃

    // 技师等级
    Route::get('/technician-tiers', [app\admin\controller\TechnicianTierController::class, 'index']);
    Route::put('/technician-tiers/{id}', [app\admin\controller\TechnicianTierController::class, 'update']);
    Route::post('/technician-tiers/assign', [app\admin\controller\TechnicianTierController::class, 'assign']);

    // ============================================================
    // 卡项设计
    // ============================================================
    Route::get('/service-cards', [app\admin\controller\ServiceCardController::class, 'index']);
    Route::post('/service-cards', [app\admin\controller\ServiceCardController::class, 'store']);
    Route::get('/service-cards/{id}', [app\admin\controller\ServiceCardController::class, 'show']);
    Route::put('/service-cards/{id}', [app\admin\controller\ServiceCardController::class, 'update']);
    Route::delete('/service-cards/{id}', [app\admin\controller\ServiceCardController::class, 'destroy']);

    // 技师性别限制
    Route::get('/technicians/{id}/service-restrictions', [app\admin\controller\TechnicianController::class, 'serviceRestrictions']);
    Route::post('/technicians/gender-restrictions', [app\admin\controller\TechnicianController::class, 'updateRestrictions']);

    // 系统监控
    Route::get('/system-monitor', [app\admin\controller\SystemMonitorController::class, 'index']);
    Route::get('/system-monitor/processes', [app\admin\controller\SystemMonitorController::class, 'processes']);
    Route::post('/system-monitor/clear-cache', [app\admin\controller\SystemMonitorController::class, 'clearCache']);

    // IP 黑名单
    Route::get('/ip-blacklist', [app\admin\controller\IpBlacklistController::class, 'index']);
    Route::post('/ip-blacklist/block', [app\admin\controller\IpBlacklistController::class, 'block']);
    Route::delete('/ip-blacklist/{ip}', [app\admin\controller\IpBlacklistController::class, 'unblock']);
    Route::get('/ip-blacklist/attacks', [app\admin\controller\IpBlacklistController::class, 'attacks']);

    // 数据库备份
    Route::get('/db-backups', [app\admin\controller\DbBackupController::class, 'index']);
    Route::post('/db-backups/create', [app\admin\controller\DbBackupController::class, 'create']);
    Route::get('/db-backups/{filename}/download', [app\admin\controller\DbBackupController::class, 'download']);
    Route::post('/db-backups/{filename}/restore', [app\admin\controller\DbBackupController::class, 'restore']);
    Route::delete('/db-backups/{filename}', [app\admin\controller\DbBackupController::class, 'destroy']);

    // 操作日志详情
    Route::get('/log/{id}', [app\admin\controller\LogController::class, 'show']);

    // 自定义导出 + 定期报表
    Route::post('/export/custom', [app\admin\controller\ExportController::class, 'custom']);
    Route::post('/export/scheduled', [app\admin\controller\ExportController::class, 'scheduled']);

    // ============================================================
    // 短视频审核
    // ============================================================
    Route::get('/video-audit', [app\admin\controller\VideoAuditController::class, 'index']);
    Route::post('/video-audit/{hashid}', [app\admin\controller\VideoAuditController::class, 'audit']);

    // ============================================================
    // 社区审核
    // ============================================================
    Route::get('/community-moderation', [app\admin\controller\CommunityModerationController::class, 'index']);
    Route::post('/community-moderation/pin/{hashid}', [app\admin\controller\CommunityModerationController::class, 'pin']);
    Route::post('/community-moderation/unpin/{hashid}', [app\admin\controller\CommunityModerationController::class, 'unpin']);
    Route::post('/community-moderation/hide/{hashid}', [app\admin\controller\CommunityModerationController::class, 'hide']);
    Route::delete('/community-moderation/{hashid}', [app\admin\controller\CommunityModerationController::class, 'destroy']);
})->middleware([
    app\middleware\AdminAuth::class,
    app\middleware\AdminPermission::class,
    app\middleware\OperationLog::class,
]);

// ============================================================
// 公开接口（通过 API-Version 头路由到版本化控制器）
// ============================================================
Route::group('/api', function () {
    // 点击验证码
    Route::post('/captcha/generate', v('CaptchaController', 'generate'));
    Route::post('/captcha/verify', v('CaptchaController', 'verify'));

    // 认证
    Route::post('/auth/login', v('AuthController', 'login'));
    Route::post('/auth/register', v('AuthController', 'register'));
    Route::post('/auth/refresh', v('AuthController', 'refresh'));
})->middleware([
    app\middleware\ApiVersion::class,
]);

// 关闭默认路由
Route::disableDefaultRoute();
