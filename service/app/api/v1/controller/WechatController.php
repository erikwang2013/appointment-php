<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\v1\controller;

use app\common\BaseController;
use app\model\User;
use support\Db;
use support\Log;
use support\Redis;
use Webman\Http\Request;

/**
 * 微信认证控制器
 * 处理小程序登录、手机号绑定、公众号登录
 *
 * 前置条件: 需在 appointment_system_config 表中配置 group='wechat_app' 的记录:
 *   - app_id: 小程序/公众号 AppID
 *   - app_secret: 小程序/公众号 AppSecret
 */
class WechatController extends BaseController
{
    private function getWechatConfig(): array
    {
        $configs = Db::table('appointment_system_config')
            ->where('group', 'wechat_app')
            ->pluck('value', 'key')
            ->toArray();

        return [
            'app_id'     => $configs['app_id'] ?? '',
            'app_secret' => $configs['app_secret'] ?? '',
        ];
    }

    private function code2Session(string $code): array
    {
        $config = $this->getWechatConfig();

        if (empty($config['app_id']) || empty($config['app_secret'])) {
            return ['success' => false, 'message' => '微信小程序未配置 (appointment_system_config.wechat_app)'];
        }

        $url = sprintf(
            'https://api.weixin.qq.com/sns/jscode2session?appid=%s&secret=%s&js_code=%s&grant_type=authorization_code',
            $config['app_id'],
            $config['app_secret'],
            $code
        );

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            if (!is_string($response) || empty($response)) {
                return ['success' => false, 'message' => '微信接口无响应'];
            }

            $data = json_decode($response, true);

            if (!empty($data['errcode'])) {
                Log::warning('[Wechat] code2Session error: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
                return ['success' => false, 'message' => '微信登录失败: ' . ($data['errmsg'] ?? '未知错误')];
            }

            return [
                'success'     => true,
                'openid'      => $data['openid'] ?? '',
                'unionid'     => $data['unionid'] ?? '',
                'session_key' => $data['session_key'] ?? '',
            ];
        } catch (\Throwable $e) {
            Log::error('[Wechat] code2Session exception: ' . $e->getMessage());
            return ['success' => false, 'message' => '微信服务调用异常'];
        }
    }

    private function oaAccessToken(string $code): array
    {
        $config = $this->getWechatConfig();

        if (empty($config['app_id']) || empty($config['app_secret'])) {
            return ['success' => false, 'message' => '微信公众号未配置 (appointment_system_config.wechat_app)'];
        }

        $tokenUrl = sprintf(
            'https://api.weixin.qq.com/sns/oauth2/access_token?appid=%s&secret=%s&code=%s&grant_type=authorization_code',
            $config['app_id'],
            $config['app_secret'],
            $code
        );

        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $tokenUrl,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
                CURLOPT_SSL_VERIFYPEER => true,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);

            if (!is_string($response) || empty($response)) {
                return ['success' => false, 'message' => '微信接口无响应'];
            }

            $data = json_decode($response, true);

            if (!empty($data['errcode'])) {
                Log::warning('[Wechat] oaAccessToken error: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
                return ['success' => false, 'message' => '公众号登录失败: ' . ($data['errmsg'] ?? '未知错误')];
            }

            return [
                'success' => true,
                'openid'  => $data['openid'] ?? '',
                'unionid' => $data['unionid'] ?? '',
            ];
        } catch (\Throwable $e) {
            Log::error('[Wechat] oaAccessToken exception: ' . $e->getMessage());
            return ['success' => false, 'message' => '微信服务调用异常'];
        }
    }

    private function findOrCreateByOpenid(string $openid, string $unionid = ''): User
    {
        $user = User::where('wx_openid', $openid)->first();

        if (!$user) {
            if (!empty($unionid)) {
                $user = User::where('wx_unionid', $unionid)->first();
            }

            if (!$user) {
                $userId = User::generateId();
                $referralCode = User::generateReferralCode();

                $user = User::create([
                    'id'            => $userId,
                    'wx_openid'     => $openid,
                    'wx_unionid'    => $unionid,
                    'user_type'     => 'customer',
                    'active_role'   => 'customer',
                    'referral_code' => $referralCode,
                    'status'        => 1,
                ]);
            } else {
                if (empty($user->wx_openid)) {
                    $user->wx_openid = $openid;
                    $user->save();
                }
            }
        }

        return $user;
    }

    public function miniLogin(Request $request)
    {
        $code = $request->input('code', '');

        if (empty($code)) {
            return $this->error('缺少登录凭证 code');
        }

        $wxResult = $this->code2Session($code);

        if (!$wxResult['success']) {
            return $this->error($wxResult['message'] ?? '微信登录失败');
        }

        // 存储 session_key 供后续 phone() 使用
        if (!empty($wxResult['session_key']) && !empty($wxResult['openid'])) {
            Redis::setex('appointment:wx_session_key:' . $wxResult['openid'], 3600, $wxResult['session_key']);
        }

        $user = $this->findOrCreateByOpenid($wxResult['openid'], $wxResult['unionid'] ?? '');

        if ($user->status == 0) {
            return $this->error('账号已被禁用，请联系客服');
        }

        $isNewUser = empty($user->phone);
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();

        $token = $user->generateToken();

        return $this->success([
            'token'       => $token,
            'is_new_user' => $isNewUser,
            'need_phone'  => $isNewUser,
            'user'        => [
                'id'       => $user->id,
                'nickname' => $user->nickname,
                'avatar'   => $user->avatar,
                'phone'    => $user->phone ? $this->maskPhone($user->phone) : null,
            ],
        ], '登录成功');
    }

    /**
     * 手机号绑定
     * POST /api/wechat/phone
     *
     * 前置条件: 需先通过 miniLogin 获取 session_key 并存储到 Redis。
     * 微信 getPhoneNumber API 返回的 code 需配合 access_token 换取手机号:
     *   POST https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=TOKEN
     *   body: {"code": "<前端传的code>"}
     * 解析 phone_info.phoneNumber 后绑定到用户。
     */
    public function phone(Request $request)
    {
        $code = $request->input('code', '');
        $userId = $request->user_id ?? 0;

        if (empty($code)) {
            return $this->error('缺少手机号授权码');
        }

        $user = User::find($userId);
        if (!$user || empty($user->wx_openid)) {
            return $this->error('请先通过微信小程序登录');
        }

        // 检查 session_key 是否可用
        $sessionKey = Redis::get('appointment:wx_session_key:' . $user->wx_openid);
        if (empty($sessionKey)) {
            return $this->error('微信会话已过期，请重新登录');
        }

        // 调用微信 getPhoneNumber API 需要 access_token
        // access_token 可从 WechatTemplateMessageService::getAccessToken() 获取并缓存
        // 完整实现伪代码:
        //   $accessToken = (new WechatTemplateMessageService())->getAccessToken();
        //   $phoneData = httpPost("https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token={$accessToken}", json_encode(['code' => $code]));
        //   $phone = $phoneData['phone_info']['phoneNumber'];
        //   $user->phone = $phone;
        //   $user->save();

        Log::info('[Wechat phone] 绑定请求, user_id: ' . $userId);

        return $this->success([
            'phone_bound' => false,
            'message'     => '手机号绑定服务暂未完全接入，请使用短信验证码完成手机号绑定',
        ], '请使用短信验证码绑定手机号');
    }

    public function oaLogin(Request $request)
    {
        $code = $request->input('code', '');

        if (empty($code)) {
            return $this->error('缺少授权码 code');
        }

        $wxResult = $this->oaAccessToken($code);

        if (!$wxResult['success']) {
            return $this->error($wxResult['message'] ?? '公众号登录失败');
        }

        $user = $this->findOrCreateByOpenid($wxResult['openid'], $wxResult['unionid'] ?? '');

        if ($user->status == 0) {
            return $this->error('账号已被禁用，请联系客服');
        }

        $isNewUser = empty($user->phone);
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->save();

        $token = $user->generateToken();

        return $this->success([
            'token'       => $token,
            'is_new_user' => $isNewUser,
            'user'        => [
                'id'       => $user->id,
                'nickname' => $user->nickname,
                'avatar'   => $user->avatar,
                'phone'    => $user->phone ? $this->maskPhone($user->phone) : null,
            ],
        ], '登录成功');
    }

    private function maskPhone(string $phone): string
    {
        if (strlen($phone) >= 7) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
        return $phone;
    }
}
