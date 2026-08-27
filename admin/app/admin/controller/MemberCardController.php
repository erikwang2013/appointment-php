<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\MemberCard;
use app\model\UserMemberCard;
use support\Request;
use support\Response;

/**
 * 会员卡定义管理控制器
 *
 * 管理 appointment_member_card 会员卡定义（月卡/年卡/次卡等）。
 * 类型: month=月卡 vip=权益卡 times=次卡
 * 权限: get/post/put/delete admin/member-cards
 */
class MemberCardController extends BaseController
{
    private const TYPES = ['month', 'vip', 'times'];

    /**
     * 会员卡定义列表（分页）
     * 搜索: keyword(name/type) / status
     */
    public function index(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = trim((string) $request->input('keyword', ''));
        $status  = $request->input('status', '');

        $query = MemberCard::query();
        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                  ->orWhere('type', $keyword);
            });
        }
        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        $total = $query->count();
        $list  = $query->orderBy('id', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($card) => $card->toArray());

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 会员卡定义详情
     */
    public function show(Request $request, string $hashid): Response
    {
        $card = $this->findCard($hashid);
        if (!$card) {
            return $this->fail('会员卡定义不存在', 404);
        }
        return $this->success($card->toArray());
    }

    /**
     * 创建会员卡定义
     * @Apidoc\Param("name", type="string", require=true, desc="卡名称")
     * @Apidoc\Param("type", type="string", require=true, desc="卡类型: month/vip/times")
     * @Apidoc\Param("price", type="float", require=true, desc="售价")
     * @Apidoc\Param("duration_days", type="int", require=false, desc="有效天数")
     * @Apidoc\Param("total_times", type="int", require=false, desc="总次数（次卡）")
     * @Apidoc\Param("services", type="array", require=false, desc="包含服务（JSON 数组）")
     * @Apidoc\Param("status", type="int", require=false, desc="状态: 0=禁用 1=启用")
     */
    public function store(Request $request): Response
    {
        $validator = validator($request->all(), [
            'name'  => 'required|string|max:100',
            'type'  => 'required|string|in:' . implode(',', self::TYPES),
            'price' => 'required|numeric|min:0',
        ]);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $services = $this->parseServices($request->input('services', []));
        if ($services === null) {
            return $this->fail('services 必须是合法 JSON 数组', 422);
        }

        $card = new MemberCard();
        $card->id            = MemberCard::generateId();
        $card->name          = trim((string) $request->input('name'));
        $card->type          = (string) $request->input('type');
        $card->price         = (float) $request->input('price');
        $card->duration_days = (int) $request->input('duration_days', 0);
        $card->total_times   = (int) $request->input('total_times', 0);
        $card->services      = $services;
        $card->status        = (int) $request->input('status', 1);
        $card->created_by    = (int) ($request->adminId ?? 0);
        $card->save();

        return $this->success($card->toArray(), '创建成功');
    }

    /**
     * 编辑会员卡定义（含上架/下架 status）
     */
    public function update(Request $request, string $hashid): Response
    {
        $card = $this->findCard($hashid);
        if (!$card) {
            return $this->fail('会员卡定义不存在', 404);
        }

        $type = (string) $request->input('type', $card->type);
        if (!in_array($type, self::TYPES, true)) {
            return $this->fail('type 非法，仅支持 month/vip/times', 422);
        }
        if ($request->input('price') !== null && (float) $request->input('price') < 0) {
            return $this->fail('price 不能为负数', 422);
        }

        $fillable = ['name', 'type', 'price', 'duration_days', 'total_times', 'status'];
        foreach ($fillable as $field) {
            if ($request->input($field) !== null) {
                $value = $request->input($field);
                if (in_array($field, ['price'], true)) {
                    $value = (float) $value;
                } elseif (in_array($field, ['duration_days', 'total_times', 'status'], true)) {
                    $value = (int) $value;
                }
                $card->$field = $value;
            }
        }
        if ($request->input('services') !== null) {
            $services = $this->parseServices($request->input('services'));
            if ($services === null) {
                return $this->fail('services 必须是合法 JSON 数组', 422);
            }
            $card->services = $services;
        }
        $card->save();

        return $this->success($card->toArray(), '更新成功');
    }

    /**
     * 删除会员卡定义（有用户持卡则拒绝）
     */
    public function destroy(Request $request, string $hashid): Response
    {
        $card = $this->findCard($hashid);
        if (!$card) {
            return $this->fail('会员卡定义不存在', 404);
        }

        $held = UserMemberCard::where('card_id', $card->id)->count();
        if ($held > 0) {
            return $this->fail("该卡已被 {$held} 位用户持有，无法删除（建议改为下架禁用）", 422);
        }

        $card->delete();
        return $this->success([], '删除成功');
    }

    // ── 内部辅助 ──

    private function findCard(string $hashid): ?MemberCard
    {
        $id = $this->decodeId($hashid);
        return $id > 0 ? MemberCard::find($id) : null;
    }

    /**
     * 解析 services 入参：接受 JSON 字符串或数组，返回数组；非法返回 null
     */
    private function parseServices($raw): ?array
    {
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return null;
            }
            $raw = $decoded;
        }
        if (!is_array($raw)) {
            return null;
        }
        return array_values($raw);
    }
}
