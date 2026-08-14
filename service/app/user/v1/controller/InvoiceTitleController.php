<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\InvoiceTitle;
use Illuminate\Database\QueryException;
use support\Db;
use Webman\Http\Request;

/**
 * 常用发票抬头控制器
 *
 * POST   /api/invoice-titles          保存抬头
 * GET    /api/invoice-titles          我的抬头列表（is_default 置顶）
 * PUT    /api/invoice-titles/{id}     编辑抬头（仅本人）
 * DELETE /api/invoice-titles/{id}     删除抬头（仅本人）
 * POST   /api/invoice-titles/{id}/default 设为默认（同用户其他行清零，事务）
 *
 * 规则：
 * 1. company 抬头税号必填；同用户同类型同抬头重复 422（uk_user_title 兜底）；
 * 2. 首个保存自动为默认；删除默认后自动指定最早一条为默认；
 * 3. 仅本人可编辑/删除/设默认（非本人 404）。
 */
class InvoiceTitleController extends BaseController
{
    /** 抬头类型白名单 */
    private const TITLE_TYPES = [
        InvoiceTitle::TITLE_TYPE_PERSONAL,
        InvoiceTitle::TITLE_TYPE_COMPANY,
    ];

    /**
     * 我的抬头列表
     * GET /api/invoice-titles（is_default 置顶）
     */
    public function index(Request $request)
    {
        $userId = (string) $request->user_id;

        $titles = InvoiceTitle::where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return $this->success($titles);
    }

    /**
     * 保存抬头
     * POST /api/invoice-titles
     */
    public function store(Request $request)
    {
        $userId    = (string) $request->user_id;
        $titleType = (string) $request->input('title_type', '');
        $title     = trim((string) $request->input('invoice_title', ''));
        $taxNo     = trim((string) $request->input('tax_no', ''));

        if (!in_array($titleType, self::TITLE_TYPES, true)) {
            return $this->error('抬头类型无效', 422);
        }
        if ($title === '') {
            return $this->error('发票抬头不能为空', 422);
        }
        if ($titleType === InvoiceTitle::TITLE_TYPE_COMPANY && $taxNo === '') {
            return $this->error('企业抬头必须填写税号', 422);
        }

        // 同用户同类型同抬头重复 422（先查后插 + 唯一键兜底）
        if (InvoiceTitle::where('user_id', $userId)
            ->where('title_type', $titleType)
            ->where('invoice_title', $title)->exists()) {
            return $this->error('该抬头已存在', 422);
        }

        try {
            $titleRow = InvoiceTitle::create([
                'id'            => InvoiceTitle::generateId(),
                'user_id'       => $userId,
                'title_type'    => $titleType,
                'invoice_title' => $title,
                'tax_no'        => $taxNo !== '' ? $taxNo : null,
                // 首个保存自动为默认
                'is_default'    => InvoiceTitle::where('user_id', $userId)->count() === 0 ? 1 : 0,
            ]);
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                return $this->error('该抬头已存在', 422);
            }
            throw $e;
        }

        return $this->success($titleRow, '抬头保存成功');
    }

    /**
     * 编辑抬头
     * PUT /api/invoice-titles/{id}
     */
    public function update(Request $request, ?string $id)
    {
        $userId   = (string) $request->user_id;
        $titleId  = $this->decodeId($id);
        if ($titleId === null) {
            return $this->error('抬头不存在', 404);
        }

        $titleRow = InvoiceTitle::where('id', $titleId)->where('user_id', $userId)->first();
        if (!$titleRow) {
            return $this->error('抬头不存在', 404);
        }

        $titleType = (string) $request->input('title_type', (string) $titleRow->title_type);
        $title     = trim((string) $request->input('invoice_title', (string) $titleRow->invoice_title));
        $taxNo     = trim((string) $request->input('tax_no', (string) ($titleRow->tax_no ?? '')));

        if (!in_array($titleType, self::TITLE_TYPES, true)) {
            return $this->error('抬头类型无效', 422);
        }
        if ($title === '') {
            return $this->error('发票抬头不能为空', 422);
        }
        if ($titleType === InvoiceTitle::TITLE_TYPE_COMPANY && $taxNo === '') {
            return $this->error('企业抬头必须填写税号', 422);
        }

        if (InvoiceTitle::where('user_id', $userId)
            ->where('title_type', $titleType)
            ->where('invoice_title', $title)
            ->where('id', '!=', $titleId)->exists()) {
            return $this->error('该抬头已存在', 422);
        }

        $titleRow->fill([
            'title_type'    => $titleType,
            'invoice_title' => $title,
            'tax_no'        => $taxNo !== '' ? $taxNo : null,
        ]);
        $titleRow->save();

        return $this->success($titleRow, '抬头更新成功');
    }

    /**
     * 删除抬头
     * DELETE /api/invoice-titles/{id}
     */
    public function destroy(Request $request, ?string $id)
    {
        $userId  = (string) $request->user_id;
        $titleId = $this->decodeId($id);
        if ($titleId === null) {
            return $this->error('抬头不存在', 404);
        }

        $titleRow = InvoiceTitle::where('id', $titleId)->where('user_id', $userId)->first();
        if (!$titleRow) {
            return $this->error('抬头不存在', 404);
        }

        $wasDefault = (int) $titleRow->is_default === 1;
        $titleRow->delete();

        // 删除默认后自动指定最早一条为默认
        if ($wasDefault) {
            $next = InvoiceTitle::where('user_id', $userId)
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->first();
            if ($next) {
                $next->is_default = 1;
                $next->save();
            }
        }

        return $this->success(null, '抬头已删除');
    }

    /**
     * 设为默认（同用户其他行清零，事务）
     * POST /api/invoice-titles/{id}/default
     */
    public function setDefault(Request $request, ?string $id)
    {
        $userId  = (string) $request->user_id;
        $titleId = $this->decodeId($id);
        if ($titleId === null) {
            return $this->error('抬头不存在', 404);
        }

        $titleRow = InvoiceTitle::where('id', $titleId)->where('user_id', $userId)->first();
        if (!$titleRow) {
            return $this->error('抬头不存在', 404);
        }

        Db::transaction(function () use ($userId, $titleId): void {
            InvoiceTitle::where('user_id', $userId)->update(['is_default' => 0]);
            InvoiceTitle::where('id', $titleId)->update(['is_default' => 1]);
        });

        return $this->success(InvoiceTitle::find($titleId), '已设为默认抬头');
    }
}
