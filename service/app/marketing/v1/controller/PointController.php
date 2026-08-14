<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\marketing\v1\controller;

use app\common\BaseController;
use app\model\UserPoints;
use Webman\Http\Request;

/**
 * 用户积分控制器
 */
class PointController extends BaseController
{
    /**
     * 获取用户积分流水
     * GET /api/marketing/points
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;

        // 当前积分余额
        $balance = (int) UserPoints::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->value('balance') ?? 0;

        // 积分流水记录（分页，支持 type/source 过滤）
        $perPage = (int) $request->input('per_page', 20);
        $query = UserPoints::where('user_id', $userId);
        if ($type = (string) $request->input('type', '')) {
            $query->where('type', $type);
        }
        if ($source = (string) $request->input('source', '')) {
            $query->where('source', $source);
        }
        $paginator = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return json([
            'code' => 0,
            'message' => 'success',
            'data' => [
                'balance' => $balance,
                'records' => $this->encodeIds($paginator->items()),
            ],
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'has_more' => $paginator->hasMorePages(),
            ],
        ]);
    }
}
