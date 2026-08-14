<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\OrderVerification;
use support\Request;
use support\Response;

/**
 * 核销记录管理
 *
 * 只读列表 + 详情：按订单号 / 技师 / 核销方式 / 核销日期筛选。
 * 核销码 64 位随机串（erik_order_verification.code，UK），核销时校验；
 * verified_by 为核销技师 user_id，核销人归属的订单技师以 erik_order.technician_id 为准。
 */
class OrderVerificationController extends BaseController
{
    /**
     * 核销记录列表
     * 筛选: order_no / technician_id / verify_type / date_start / date_end
     */
    public function index(Request $request): Response
    {
        $page         = (int) $request->input('page', 1);
        $limit        = (int) $request->input('limit', 15);
        $orderNo      = $request->input('order_no', '');
        $technicianId = $request->input('technician_id', '');
        $verifyType   = $request->input('verify_type', '');
        $dateStart    = $request->input('date_start', '');
        $dateEnd      = $request->input('date_end', '');

        // 仅展示已核销记录（未核销的行只是「已生成核销码」）
        $query = OrderVerification::with(['order'])->whereNotNull('verified_at');

        if ($orderNo) {
            $query->whereHas('order', function ($q) use ($orderNo) {
                $q->where('order_no', 'like', "%{$orderNo}%");
            });
        }
        if ($technicianId) {
            $query->whereHas('order', function ($q) use ($technicianId) {
                $q->where('technician_id', $technicianId);
            });
        }
        if ($verifyType) {
            $query->where('verify_type', $verifyType);
        }
        if ($dateStart) {
            $query->whereDate('verified_at', '>=', $dateStart);
        }
        if ($dateEnd) {
            $query->whereDate('verified_at', '<=', $dateEnd);
        }

        $total = $query->count();
        $list  = $query->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->orderBy('verified_at', 'desc')
                       ->get()
                       ->map(function ($v) {
                           $arr = $this->encodeIds($v->toArray());
                           // 关联订单同样编码 ID 字段，前端无需二次解码
                           if (isset($arr['order']) && is_array($arr['order'])) {
                               $arr['order'] = $this->encodeIds($arr['order']);
                           }
                           return $arr;
                       });

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 核销记录详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id = $this->decodeId($hashid);
        if ($id <= 0) {
            return $this->fail('核销记录不存在', 404);
        }

        $verification = OrderVerification::with(['order'])->find($id);
        if (!$verification) {
            return $this->fail('核销记录不存在', 404);
        }

        $data = $this->encodeIds($verification->toArray());
        if (isset($data['order']) && is_array($data['order'])) {
            $data['order'] = $this->encodeIds($data['order']);
        }
        return $this->success($data);
    }
}
