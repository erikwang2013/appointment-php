<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\PlatformAgreement;
use support\Request;
use support\Response;

class AgreementController extends BaseController
{
    /**
     * 协议列表（按类型分组）
     */
    public function index(Request $request): Response
    {
        $type = $request->input('type', '');

        $query = PlatformAgreement::query();
        if ($type) {
            $query->where('type', $type);
        }

        $list = $query->orderBy('type')
                      ->orderBy('version', 'desc')
                      ->get()
                      ->map(fn($a) => $this->encodeIds($a->toArray()));

        return $this->success(['list' => $list]);
    }

    /**
     * 协议详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $id        = $this->decodeId($hashid);
        $agreement = PlatformAgreement::find($id);
        if (!$agreement) {
            return $this->fail('协议不存在', 404);
        }

        return $this->success($this->encodeIds($agreement->toArray()));
    }

    /**
     * 更新协议内容与版本
     */
    public function update(Request $request, string $hashid): Response
    {
        $id        = $this->decodeId($hashid);
        $agreement = PlatformAgreement::find($id);
        if (!$agreement) {
            return $this->fail('协议不存在', 404);
        }

        if ($request->has('title')) {
            $agreement->title = $request->input('title');
        }
        if ($request->has('content')) {
            $agreement->content = $request->input('content');
        }
        if ($request->has('version')) {
            $agreement->version = $request->input('version');
        }
        $agreement->save();

        return $this->success($this->encodeIds($agreement->toArray()), '更新成功');
    }

    /**
     * 发布协议
     */
    public function publish(Request $request, string $hashid): Response
    {
        $id        = $this->decodeId($hashid);
        $agreement = PlatformAgreement::find($id);
        if (!$agreement) {
            return $this->fail('协议不存在', 404);
        }

        // 将同类型的其他协议取消发布
        PlatformAgreement::where('type', $agreement->type)
            ->where('id', '!=', $id)
            ->update(['status' => 0]);

        $agreement->status       = 1;
        $agreement->published_at = date('Y-m-d H:i:s');
        $agreement->save();

        return $this->success($this->encodeIds($agreement->toArray()), '发布成功');
    }
}
