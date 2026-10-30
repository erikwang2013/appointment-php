<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

return [
    'files' => array_merge([
        base_path() . '/app/functions.php',
        base_path() . '/support/Request.php',
        base_path() . '/support/Response.php',
    ], array_values(array_filter([
        // 跨应用共享实现（与服务端同一类文件，避免双份逻辑漂移）：
        // 提现审批通过后的微信企业付款，admin 控制器通过 app\common\TechnicianWithdrawalService 委托
        // 容器化部署时 service 目录可能未挂载，逐个 is_file() 防护避免 bootstrap 致命错误
        dirname(base_path()) . '/service/app/common/WechatPayService.php',
        dirname(base_path()) . '/service/app/common/TechnicianWithdrawalService.php',
        // 订单完成奖励（管理端确认完成与技师端/自动完成同口径）
        dirname(base_path()) . '/service/app/common/ReferralRewardService.php',
        dirname(base_path()) . '/service/app/common/ReturnCustomerRewardService.php',
        // 待支付订单统一取消（管理端取消与定时器/拼团批量关闭同口径）
        dirname(base_path()) . '/service/app/common/PendingOrderCancelService.php',
    ], 'is_file')))
];
