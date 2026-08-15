<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\FullReductionActivity;
use Webman\Http\Request;

/**
 * 满减活动控制器
 *
 * 公开接口：返回当前生效中的满减活动列表（status=1 且时间在有效期内），
 * 供用户端下单前展示「满 X 减 Y」活动。
 */
class FullReductionController extends BaseController
{
    /**
     * 生效中的满减活动列表
     * GET /api/full-reduction-activities
     */
    public function index(Request $request)
    {
        $now = date('Y-m-d H:i:s');
        $activities = FullReductionActivity::where('status', 1)
            ->where('start_at', '<=', $now)
            ->where('end_at', '>=', $now)
            ->orderByDesc('reduction')
            ->get();

        return $this->success($activities);
    }
}
