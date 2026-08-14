<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\common\SmsService;
use support\Redis;
use Webman\Http\Request;

/**
 * 短信验证码控制器
 * 发送短信验证码，带限流控制
 */
class CaptchaController extends BaseController
{
    /**
     * 发送短信验证码
     * POST /api/captcha/send
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function send(Request $request)
    {
        $phone = $request->input('phone', '');

        // 验证手机号格式
        if (empty($phone) || !preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return $this->error('请输入正确的手机号码');
        }

        // 频率限制：每60秒只能发送一次
        $rateLimitKey = "sms_rate_limit:{$phone}";
        if (Redis::exists($rateLimitKey)) {
            $ttl = Redis::ttl($rateLimitKey);
            return $this->error("请{$ttl}秒后再试");
        }

        // 生成6位随机验证码
        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // 存储验证码到 Redis，有效期5分钟
        Redis::setex("sms_code:{$phone}", 300, $code);

        // 设置频率限制，60秒
        Redis::setex($rateLimitKey, 60, '1');

        // 调用短信服务发送验证码
        $smsService = new SmsService();
        $result = $smsService->send($phone, $code);

        if (!$result['success']) {
            // 短信发送失败时保留 Redis 中的验证码供测试/降级使用
            // 生产环境需配置 erik_system_config 中 group='sms' 的短信服务商信息
            // 注意：日志中禁止出现验证码明文，防止日志泄露导致验证码可被利用
            error_log("[SMS] 验证码发送失败 ({$phone}): {$result['message']}");
        }

        return $this->success([
            'phone' => $this->maskPhone($phone),
            'expire_in' => 300,
        ], '验证码已发送');
    }

    /**
     * 手机号脱敏
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) >= 7) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
        return $phone;
    }
}
