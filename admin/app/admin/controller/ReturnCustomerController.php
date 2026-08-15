<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\admin\controller;

use app\model\SystemConfig;
use app\model\TechnicianEarning;
use support\Request;
use support\Response;

/**
 * 回头客奖励管理控制器（R24：30 天内二次消费奖金）
 *
 * 配置：group=return_customer 的 enabled（开关）/ ratio（比例，默认 0.05）
 * 记录：技师收益流水 type='return_customer' 分页列表（含技师/订单/用户信息）
 */
class ReturnCustomerController extends BaseController
{
    private const CONFIG_GROUP = 'return_customer';
    private const KEY_ENABLED  = 'enabled';
    private const KEY_RATIO    = 'ratio';
    private const DEFAULT_RATIO = 0.05;

    /**
     * 回头客奖励配置查看
     * GET /admin/return-customer/config
     */
    public function config(Request $request): Response
    {
        $enabled = $this->readConfig(self::KEY_ENABLED);
        $ratio   = $this->readConfig(self::KEY_RATIO);

        return $this->success([
            'enabled' => $enabled === null ? 1 : (in_array((string) $enabled, ['0', 'false', 'off'], true) ? 0 : 1),
            'ratio'   => $ratio === null ? self::DEFAULT_RATIO : (float) $ratio,
        ]);
    }

    /**
     * 回头客奖励配置更新
     * PUT /admin/return-customer/config
     */
    public function updateConfig(Request $request): Response
    {
        $enabled = $request->input('enabled');
        $ratio   = $request->input('ratio');

        $validator = validator($request->all(), [
            'enabled' => 'required|in:0,1',
            'ratio'   => 'required|numeric|between:0.01,1',
        ]);
        if ($validator->fails()) {
            return $this->fail($validator->errors()->first(), 422);
        }

        $this->upsertConfig(self::KEY_ENABLED, (string) $enabled);
        $this->upsertConfig(self::KEY_RATIO, number_format((float) $ratio, 4, '.', ''));

        return $this->success([
            'enabled' => (int) $enabled,
            'ratio'   => (float) $ratio,
        ], '回头客奖励配置已更新');
    }

    /**
     * 回头客奖励记录列表
     * GET /admin/return-customer/rewards
     * 筛选: keyword（技师姓名/订单号/用户昵称）
     */
    public function rewards(Request $request): Response
    {
        $page    = (int) $request->input('page', 1);
        $limit   = (int) $request->input('limit', 15);
        $keyword = (string) $request->input('keyword', '');

        $query = TechnicianEarning::with(['technician.user', 'order.user'])
            ->where('type', 'return_customer');

        if ($keyword !== '') {
            $query->where(function ($q) use ($keyword) {
                $q->where('description', 'like', "%{$keyword}%")
                    ->orWhereHas('technician', function ($sub) use ($keyword) {
                        $sub->where('real_name', 'like', "%{$keyword}%");
                    })
                    ->orWhereHas('order', function ($sub) use ($keyword) {
                        $sub->where('order_no', 'like', "%{$keyword}%")
                            ->orWhereHas('user', function ($u) use ($keyword) {
                                $u->where('nickname', 'like', "%{$keyword}%");
                            });
                    });
            });
        }

        $total = $query->count();
        $list  = $query->orderBy('id', 'desc')
                       ->offset(($page - 1) * $limit)
                       ->limit($limit)
                       ->get()
                       ->map(fn($earning) => $this->decorate($earning));

        return $this->success([
            'list'  => $list,
            'total' => $total,
            'page'  => $page,
            'limit' => $limit,
        ]);
    }

    /**
     * 输出装饰：hashid 编码 + 技师姓名/订单号/用户昵称
     */
    private function decorate(TechnicianEarning $earning): array
    {
        $data = $this->encodeIds(
            $earning->toArray(),
            ['id', 'technician_id', 'order_id']
        );

        $data['amount']          = (float) ($earning->amount ?? 0);
        $data['technician_name'] = $earning->technician ? (string) $earning->technician->real_name : '';
        $data['order_no']        = $earning->order ? (string) $earning->order->order_no : '';
        $data['user_nickname']   = $earning->order && $earning->order->user ? (string) $earning->order->user->nickname : '';

        return $data;
    }

    private function readConfig(string $key): ?string
    {
        $value = SystemConfig::where('group', self::CONFIG_GROUP)
            ->where('key', $key)
            ->value('value');
        return $value === null ? null : (string) $value;
    }

    private function upsertConfig(string $key, string $value): void
    {
        $config = SystemConfig::where('group', self::CONFIG_GROUP)
            ->where('key', $key)
            ->first();
        if ($config) {
            $config->value = $value;
            $config->save();
            return;
        }

        $config = new SystemConfig();
        $config->id          = $this->generateId();
        $config->group       = self::CONFIG_GROUP;
        $config->key         = $key;
        $config->value       = $value;
        $config->type        = 'string';
        $config->description = $key === self::KEY_ENABLED
            ? '回头客奖励开关：1=开启 0=关闭（用户对同一技师30天内二次消费时给技师发放奖金）'
            : '回头客奖励比例：奖金=订单实付×比例（0-1，非法值回落 0.05）';
        $config->save();
    }
}
