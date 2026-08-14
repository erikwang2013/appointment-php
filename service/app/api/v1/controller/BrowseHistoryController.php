<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\BrowseHistory;
use support\Db;
use Webman\Http\Request;

/**
 * 浏览足迹控制器
 * 管理用户最近浏览的服务列表（列表 / 删除单条 / 清空）
 */
class BrowseHistoryController extends BaseController
{
    /**
     * 最近浏览列表（按浏览时间倒序，join 服务名称/封面/价格/原价）
     * GET /api/browse-history
     */
    public function index(Request $request)
    {
        $userId = $request->user_id;
        $perPage = (int)$request->input('per_page', 15);
        $perPage = max(1, min($perPage, 50));
        $page = (int)$request->input('page', 1);

        $paginator = Db::table('erik_browse_history as bh')
            ->join('erik_service as s', 's.id', '=', 'bh.item_id')
            ->where('bh.user_id', $userId)
            ->orderBy('bh.viewed_at', 'desc')
            ->orderBy('bh.id', 'desc')
            ->paginate($perPage, [
                'bh.item_id', 'bh.viewed_at',
                's.name', 's.cover_image', 's.price', 's.original_price',
            ], 'page', $page);

        $items = array_map(static function ($row) {
            return [
                'item_id'        => $row->item_id,
                'viewed_at'      => $row->viewed_at,
                'name'           => $row->name,
                'cover_image'    => $row->cover_image,
                'price'          => (float)$row->price,
                'original_price' => (float)$row->original_price,
            ];
        }, $paginator->items());

        return json([
            'code'    => 0,
            'message' => 'success',
            'data'    => $this->encodeIds($items),
            'meta'    => [
                'current_page' => $paginator->currentPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
                'last_page'    => $paginator->lastPage(),
                'has_more'     => $paginator->hasMorePages(),
            ],
        ]);
    }

    /**
     * 删除单条浏览记录（仅本人）
     * DELETE /api/browse-history/{item_id}
     */
    public function destroy(Request $request, string $itemId)
    {
        $userId = $request->user_id;
        $decodedId = $this->decodeId($itemId);

        if ($decodedId === null) {
            return $this->error('浏览记录不存在', 404);
        }

        $deleted = BrowseHistory::where('user_id', $userId)
            ->where('item_id', $decodedId)
            ->delete();

        if (!$deleted) {
            return $this->error('浏览记录不存在', 404);
        }

        return $this->success(null, '已删除');
    }

    /**
     * 清空浏览记录（仅本人）
     * DELETE /api/browse-history
     */
    public function clear(Request $request)
    {
        $userId = $request->user_id;

        BrowseHistory::where('user_id', $userId)->delete();

        return $this->success(null, '已清空');
    }
}
