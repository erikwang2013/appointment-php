<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\SystemConfig;
use support\Request;
use support\Response;

class WithdrawalConfigController extends BaseController
{
    /**
     * 获取当前提现配置
     */
    public function show(Request $request): Response
    {
        $configs = SystemConfig::where('group', 'withdrawal')->pluck('value', 'key')->toArray();

        $data = [
            'min_amount'      => $configs['min_amount'] ?? '100',
            'reserve_amount'  => $configs['reserve_amount'] ?? '0',
            'round_to_hundred'=> $configs['round_to_hundred'] ?? '1',
            'withdrawal_day'  => $configs['withdrawal_day'] ?? '15',
            'arrival_days'    => $configs['arrival_days'] ?? '3',
        ];

        return $this->success($data);
    }

    /**
     * 更新提现配置
     */
    public function update(Request $request): Response
    {
        $fields = [
            'min_amount'       => $request->input('min_amount'),
            'reserve_amount'   => $request->input('reserve_amount'),
            'round_to_hundred' => $request->input('round_to_hundred'),
            'withdrawal_day'   => $request->input('withdrawal_day'),
            'arrival_days'     => $request->input('arrival_days'),
        ];

        foreach ($fields as $key => $value) {
            if ($value !== null) {
                $config = SystemConfig::where('group', 'withdrawal')
                    ->where('key', $key)
                    ->first();

                if ($config) {
                    $config->value = (string) $value;
                    $config->save();
                } else {
                    $config = new SystemConfig();
                    $config->id          = $this->generateId();
                    $config->group       = 'withdrawal';
                    $config->key         = $key;
                    $config->value       = (string) $value;
                    $config->type        = 'string';
                    $config->description = '提现配置';
                    $config->save();
                }
            }
        }

        return $this->success([], '配置更新成功');
    }
}
