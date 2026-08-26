<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\order\v1\controller;

use app\common\BaseController;

/**
 * 订单控制器
 *
 * 处理订单创建、支付、退款、核销、评价等业务。
 * 按业务域拆分为 trait（本类为聚合入口，方法均来自 trait）：
 * - OrderCreateTrait      订单创建（store/计价辅助）
 * - OrderQueryTrait       订单查询（列表/详情/物流）
 * - OrderPayTrait         支付（pay/余额支付/积分抵扣/活动懒判定）
 * - OrderCancelTrait      取消订单
 * - OrderRefundTrait      申请退款
 * - OrderCompensateTrait  退款补偿扫描 + 优惠/积分归还
 * - OrderVerifyTrait      核销（佣金/返积分）
 * - OrderRescheduleTrait  预约改期
 * - OrderNotifyTrait      通知（订阅/模板/站内/WebSocket）
 * - OrderLockTrait        分布式锁工具
 */
class OrderController extends BaseController
{
    use OrderCreateTrait;
    use OrderQueryTrait;
    use OrderPayTrait;
    use OrderCancelTrait;
    use OrderRefundTrait;
    use OrderCompensateTrait;
    use OrderVerifyTrait;
    use OrderRescheduleTrait;
    use OrderNotifyTrait;
    use OrderLockTrait;
}
