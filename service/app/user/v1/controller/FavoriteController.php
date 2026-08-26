<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\UserFavorite;
use support\Db;
use Webman\Http\Request;

/**
 * 用户收藏控制器
 * 管理用户对服务、技师的收藏
 */
class FavoriteController extends BaseController
{
    /**
     * 获取用户收藏列表
     * GET /api/user/favorites
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;

        $favorites = UserFavorite::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        // 关联查询目标详情
        $result = [];
        foreach ($favorites as $favorite) {
            $item = [
                'id' => $favorite->id,
                'target_type' => $favorite->target_type,
                'target_id' => $favorite->target_id,
                'created_at' => $favorite->created_at,
            ];

            if ($favorite->target_type === 'service') {
                $service = Db::table('erik_service')
                    ->where('id', $favorite->target_id)
                    ->first();

                if ($service) {
                    $item['target'] = [
                        'id' => $service->id,
                        'name' => $service->name,
                        'cover_image' => $service->cover_image,
                        'price' => $service->price,
                        'sales_volume' => $service->sales_volume,
                    ];
                }
            } elseif ($favorite->target_type === 'technician') {
                $technician = Db::table('erik_technician_profile')
                    ->where('id', $favorite->target_id)
                    ->first();

                if ($technician) {
                    $item['target'] = [
                        'id' => $technician->id,
                        'real_name' => $technician->real_name,
                        'avatar' => $technician->avatar,
                        'rating' => $technician->rating,
                        'order_count' => $technician->order_count,
                    ];
                }
            }

            $result[] = $item;
        }

        return $this->success($result);
    }

    /**
     * 添加收藏
     * POST /api/user/favorites
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;
        $targetType = $request->input('target_type', '');
        $targetId = $request->input('target_id', '');

        if (!in_array($targetType, ['service', 'technician'])) {
            return $this->error('无效的收藏类型，仅支持 service 或 technician');
        }

        if (empty($targetId)) {
            return $this->error('请指定收藏目标');
        }

        // 检查是否已收藏
        $exists = UserFavorite::where('user_id', $userId)
            ->where('target_type', $targetType)
            ->where('target_id', $targetId)
            ->exists();

        if ($exists) {
            return $this->error('您已收藏过该项目');
        }

        $favorite = UserFavorite::create([
            'id' => UserFavorite::generateId(),
            'user_id' => $userId,
            'target_type' => $targetType,
            'target_id' => $targetId,
        ]);

        // 更新技师的被收藏数
        if ($targetType === 'technician') {
            Db::table('erik_technician_profile')
                ->where('id', $targetId)
                ->increment('favorite_count');
        }

        return $this->success($favorite, '收藏成功');
    }

    /**
     * 取消收藏
     * DELETE /api/user/favorites/{id}
     */
    public function destroy(Request $request, string $id)
    {
        $userId = $request->user_id;
        $id = $this->decodeId($id);
        if ($id === null) {
            return $this->error('收藏记录不存在', 404);
        }

        $favorite = UserFavorite::where('user_id', $userId)
            ->where('id', $id)
            ->first();

        if (!$favorite) {
            return $this->error('收藏记录不存在', 404);
        }

        // 更新技师的被收藏数
        if ($favorite->target_type === 'technician') {
            Db::table('erik_technician_profile')
                ->where('id', $favorite->target_id)
                ->where('favorite_count', '>', 0)
                ->decrement('favorite_count');
        }

        $favorite->delete();

        return $this->success(null, '已取消收藏');
    }
}
