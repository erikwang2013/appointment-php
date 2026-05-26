<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use app\model\SystemConfig;
use support\Request;
use support\Response;

/**
 * 对象存储配置管理
 *
 * 管理文件存储服务商配置（本地/阿里云OSS/腾讯云COS）
 */
class StorageConfigController extends BaseController
{
    /**
     * 获取当前存储配置
     *
     * @Apidoc\Title("获取存储配置")
     * @Apidoc\Group("storage-config")
     * @Apidoc\Url("/admin/storage-config")
     * @Apidoc\Desc("获取当前文件存储服务商配置信息")
     * @Apidoc\Returned("provider", type="string", desc="存储服务商: local/oss/cos")
     * @Apidoc\Returned("access_key", type="string", desc="AccessKey")
     * @Apidoc\Returned("secret_key", type="string", desc="SecretKey（脱敏显示）")
     * @Apidoc\Returned("bucket", type="string", desc="存储桶/Bucket名称")
     * @Apidoc\Returned("endpoint", type="string", desc="访问端点")
     * @Apidoc\Returned("cdn_domain", type="string", desc="CDN加速域名")
     */
    public function show(Request $request): Response
    {
        $configs = SystemConfig::where('group', 'storage')->pluck('value', 'key')->toArray();

        $secretKey = $configs['secret_key'] ?? '';

        $data = [
            'provider'    => $configs['provider'] ?? 'local',
            'access_key'  => $configs['access_key'] ?? '',
            'secret_key'  => $this->maskSensitive($secretKey),
            'bucket'      => $configs['bucket'] ?? '',
            'endpoint'    => $configs['endpoint'] ?? '',
            'cdn_domain'  => $configs['cdn_domain'] ?? '',
        ];

        return $this->success($data);
    }

    /**
     * 更新存储配置
     *
     * @Apidoc\Title("更新存储配置")
     * @Apidoc\Group("storage-config")
     * @Apidoc\Method("PUT")
     * @Apidoc\Url("/admin/storage-config")
     * @Apidoc\Desc("更新文件存储服务商配置")
     * @Apidoc\Param("provider", type="string", require=false, desc="存储服务商: local/oss/cos")
     * @Apidoc\Param("access_key", type="string", require=false, desc="AccessKey/SecretId")
     * @Apidoc\Param("secret_key", type="string", require=false, desc="SecretKey")
     * @Apidoc\Param("bucket", type="string", require=false, desc="存储桶名称")
     * @Apidoc\Param("endpoint", type="string", require=false, desc="Endpoint（如 oss-cn-hangzhou.aliyuncs.com）")
     * @Apidoc\Param("cdn_domain", type="string", require=false, desc="CDN加速域名（如 cdn.example.com）")
     */
    public function update(Request $request): Response
    {
        $fields = [
            'provider'    => $request->input('provider'),
            'access_key'  => $request->input('access_key'),
            'secret_key'  => $request->input('secret_key'),
            'bucket'      => $request->input('bucket'),
            'endpoint'    => $request->input('endpoint'),
            'cdn_domain'  => $request->input('cdn_domain'),
        ];

        // 验证 provider 值
        $provider = $fields['provider'];
        if ($provider !== null && !in_array($provider, ['local', 'oss', 'cos'], true)) {
            return $this->fail('不支持的存储服务商，可选: local, oss, cos', 422);
        }

        foreach ($fields as $key => $value) {
            if ($value === null) {
                continue;
            }

            $config = SystemConfig::where('group', 'storage')
                ->where('key', $key)
                ->first();

            if ($config) {
                $config->value = (string) $value;
                $config->save();
            } else {
                $config = new SystemConfig();
                $config->id          = $this->generateId();
                $config->group       = 'storage';
                $config->key         = $key;
                $config->value       = (string) $value;
                $config->type        = 'string';
                $config->description = $this->fieldDescription($key);
                $config->save();
            }
        }

        return $this->success([], '存储配置更新成功');
    }

    /**
     * 敏感数据脱敏显示
     */
    private function maskSensitive(string $value): string
    {
        if (empty($value) || strlen($value) <= 6) {
            return str_repeat('*', max(strlen($value), 4));
        }

        return substr($value, 0, 3) . str_repeat('*', strlen($value) - 6) . substr($value, -3);
    }

    /**
     * 字段说明
     */
    private function fieldDescription(string $key): string
    {
        return match ($key) {
            'provider'    => '存储服务商: local=本地 oss=阿里云OSS cos=腾讯云COS',
            'access_key'  => 'AccessKey（OSS）/ SecretId（COS）',
            'secret_key'  => 'SecretKey',
            'bucket'      => '存储桶名称',
            'endpoint'    => '访问端点（如 oss-cn-hangzhou.aliyuncs.com）',
            'cdn_domain'  => 'CDN 加速域名（如 cdn.example.com）',
            default       => '存储配置',
        };
    }
}
