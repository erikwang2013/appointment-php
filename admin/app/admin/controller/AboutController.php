<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\SystemConfig;
use support\Request;
use support\Response;

class AboutController extends BaseController
{
    /**
     * 获取关于信息
     */
    public function show(Request $request): Response
    {
        $configs = SystemConfig::where('group', 'about')->pluck('value', 'key')->toArray();

        $data = [
            'logo'    => $configs['logo'] ?? '',
            'phone'   => $configs['phone'] ?? '',
            'website' => $configs['website'] ?? '',
            'email'   => $configs['email'] ?? '',
            'intro'   => $configs['intro'] ?? '',
        ];

        return $this->success($data);
    }

    /**
     * 更新关于信息
     */
    public function update(Request $request): Response
    {
        $fields = [
            'logo'    => $request->input('logo'),
            'phone'   => $request->input('phone'),
            'website' => $request->input('website'),
            'email'   => $request->input('email'),
            'intro'   => $request->input('intro'),
        ];

        foreach ($fields as $key => $value) {
            if ($value !== null) {
                $config = SystemConfig::where('group', 'about')
                    ->where('key', $key)
                    ->first();

                if ($config) {
                    $config->value = (string) $value;
                    $config->save();
                } else {
                    $config = new SystemConfig();
                    $config->id          = $this->generateId();
                    $config->group       = 'about';
                    $config->key         = $key;
                    $config->value       = (string) $value;
                    $config->type        = 'string';
                    $config->description = '关于我们配置';
                    $config->save();
                }
            }
        }

        return $this->success([], '关于信息更新成功');
    }
}
