<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\GrowthLevel;
use app\model\UserGrowth;
use Webman\Http\Request;

/**
 * 用户成长等级控制器
 *
 * 独立成长体系（与会员卡 member_level 无耦合）：
 * - GET /api/growth       我的成长值、当前等级、下一等级进度、等级权益
 * - GET /api/growth/records 成长记录分页（?type=&page=&limit=，倒序）
 * - GET /api/growth/levels  全部等级列表（公开）
 */
class GrowthController extends BaseController
{
    /**
     * 我的成长值 + 当前/下一等级
     * GET /api/growth
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $total = UserGrowth::totalFor($userId);

        $levels = GrowthLevel::allLevels();
        $current = null;
        $next = null;
        foreach ($levels as $level) {
            if ((int) $level->min_growth <= $total) {
                $current = $level;
            } elseif ($next === null) {
                $next = $level;
            }
        }
        return $this->success([
            'total_growth'   => $total,
            'current_level'  => $this->levelPayload($current),
            'next_level'     => $this->levelPayload($next),
            'next_gap'       => $next !== null
                ? max(0, (int) $next->min_growth - $total)
                : 0,
        ]);
    }

    /**
     * 成长记录分页（倒序，可按类型过滤）
     * GET /api/growth/records?type=consume|signin|review&page=1&limit=15
     */
    public function records(Request $request)
    {
        $userId = $request->user_id;
        $perPage = min(max((int) $request->input('limit', 15), 1), 50);
        $page = max(1, (int) $request->input('page', 1));

        $query = UserGrowth::where('user_id', $userId);

        $type = (string) $request->input('type', '');
        if ($type !== '' && in_array($type, UserGrowth::TYPES, true)) {
            $query->where('type', $type);
        }

        $paginator = $query->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);

        return $this->paginate($paginator);
    }

    /**
     * 全部等级列表（公开）
     * GET /api/growth/levels
     */
    public function levels(Request $request): \Webman\Http\Response
    {
        return $this->success([
            'levels' => array_map(
                fn (GrowthLevel $level) => $this->levelPayload($level),
                GrowthLevel::allLevels()
            ),
        ]);
    }

    private function levelPayload(?GrowthLevel $level): ?array
    {
        if ($level === null) {
            return null;
        }
        return [
            'id'         => $level->id,
            'level'      => (int) $level->level,
            'name'       => $level->name,
            'min_growth' => (int) $level->min_growth,
            'benefits'   => $level->benefits,
        ];
    }
}
