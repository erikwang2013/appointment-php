<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\User;
use support\Log;
use Webman\Http\Request;

/**
 * 微信认证控制器
 * 处理小程序登录、手机号绑定、公众号登录
 */
class WechatController extends BaseController
{
    /**
     * 小程序登录
     * POST /api/wechat/mini-login
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function miniLogin(Request $request)
    {
        $code = $request->input('code', '');

        if (empty($code)) {
            return $this->error('缺少登录凭证 code');
        }

        // 占位：记录 code，返回 mock 响应
        Log::info('[Wechat miniLogin] code: ' . $code);

        // TODO: 调用微信小程序 API code2Session 获取 openid / unionid
        // $wxResult = $this->code2Session($code);
        // 查找或创建用户，新用户需后续绑定手机号

        return $this->success([
            'token' => 'mock_jwt_token',
            'is_new_user' => true,
            'need_phone' => true,
        ], '登录成功（占位）');
    }

    /**
     * 手机号绑定
     * POST /api/wechat/phone
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function phone(Request $request)
    {
        $code = $request->input('code', '');

        if (empty($code)) {
            return $this->error('缺少手机号授权码');
        }

        // 占位：记录 code
        Log::info('[Wechat phone] code: ' . $code);

        // TODO: 使用 access_token 解密手机号
        // $phone = $this->decryptPhone($code, $sessionKey);

        return $this->success([
            'phone_bound' => true,
        ], '手机号绑定成功');
    }

    /**
     * 公众号登录
     * POST /api/wechat/oa-login
     *
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function oaLogin(Request $request)
    {
        $code = $request->input('code', '');

        if (empty($code)) {
            return $this->error('缺少授权码 code');
        }

        // 占位：记录 code，返回 mock 响应
        Log::info('[Wechat oaLogin] code: ' . $code);

        // TODO: 调用微信公众号 OAuth2 API 获取 openid / unionid
        // $wxResult = $this->oaAccessToken($code);
        // 查找或创建用户

        return $this->success([
            'token' => 'mock_jwt_token',
            'is_new_user' => true,
        ], '登录成功（占位）');
    }
}
