<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\api\controller;

use app\common\BaseController;
use app\model\User;
use app\model\UserReferral;
use ErikJwt\JWTFactory;
use support\Db;
use support\Redis;
use Webman\Http\Request;

/**
 * 认证控制器
 * 处理用户注册、登录、密码重置、角色切换、令牌刷新
 */
class AuthController extends BaseController
{
    /**
     * 用户注册
     * POST /api/auth/register
     * @param Request $request
     * @return \Webman\Http\Response
     */
    public function register(Request $request)
    {
        $phone = $request->input('phone', '');
        $code = $request->input('code', '');
        $password = $request->input('password', '');
        $confirmPassword = $request->input('confirm_password', '');
        $referralCode = $request->input('referral_code', '');
        $inviteCode = $request->input('invite_code', '');

        // 参数校验
        if (empty($phone) || empty($code) || empty($password) || empty($confirmPassword)) {
            return $this->error('请填写完整信息');
        }

        if ($password !== $confirmPassword) {
            return $this->error('两次输入的密码不一致');
        }

        if (strlen($password) < 6) {
            return $this->error('密码长度不能少于6位');
        }

        // 验证短信验证码
        $storedCode = Redis::get("sms_code:{$phone}");
        if (!$storedCode || $storedCode != $code) {
            return $this->error('验证码错误或已过期');
        }

        // 检查手机号是否已注册
        $exists = User::where('phone', $phone)->exists();
        if ($exists) {
            return $this->error('该手机号已注册');
        }

        // 处理推荐码
        $referrerId = 0;
        $effectiveReferralCode = $referralCode ?: $inviteCode;
        if (!empty($effectiveReferralCode)) {
            $referrer = User::where('referral_code', $effectiveReferralCode)->first();
            if ($referrer) {
                $referrerId = $referrer->id;
            }
        }

        Db::beginTransaction();
        try {
            // 生成用户ID
            $userId = User::generateId();

            // 生成用户推荐码
            $userReferralCode = User::generateReferralCode();

            $user = User::create([
                'id' => $userId,
                'phone' => $phone,
                'password' => password_hash($password, PASSWORD_BCRYPT),
                'user_type' => 'customer',
                'active_role' => 'customer',
                'referral_code' => $userReferralCode,
                'referrer_id' => $referrerId,
                'status' => 1,
                'last_login_ip' => $request->getRealIp() ?: '',
            ]);

            // 如果有推荐人，创建推广记录
            if ($referrerId > 0) {
                UserReferral::create([
                    'id' => UserReferral::generateId(),
                    'referrer_id' => $referrerId,
                    'referred_user_id' => $userId,
                    'registered_at' => date('Y-m-d H:i:s'),
                ]);
            }

            // 自动发放新用户优惠券（TODO: Phase 5 实现）
            // $this->issueNewUserCoupon($userId);

            // 清除验证码
            Redis::del("sms_code:{$phone}");

            Db::commit();

            // 生成JWT令牌
            $token = $user->generateToken();

            return $this->success([
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'phone' => $this->maskPhone($user->phone),
                    'nickname' => $user->nickname,
                    'avatar' => $user->avatar,
                    'user_type' => $user->user_type,
                    'active_role' => $user->active_role,
                ],
            ], '注册成功');
        } catch (\Throwable $e) {
            Db::rollBack();
            return $this->error('注册失败，请稍后重试');
        }
    }

    /**
     * 手机号 + 密码登录
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        $phone = $request->input('phone', '');
        $password = $request->input('password', '');

        if (empty($phone) || empty($password)) {
            return $this->error('请输入手机号和密码');
        }

        $user = User::where('phone', $phone)->first();

        if (!$user || !password_verify($password, $user->password)) {
            return $this->error('手机号或密码错误');
        }

        if ($user->status == 0) {
            return $this->error('账号已被禁用，请联系客服');
        }

        // 更新登录信息
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->last_login_ip = $request->getRealIp() ?: '';
        $user->save();

        $token = $user->generateToken();

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'phone' => $this->maskPhone($user->phone),
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'user_type' => $user->user_type,
                'active_role' => $user->active_role,
                'referral_code' => $user->referral_code,
            ],
        ], '登录成功');
    }

    /**
     * 手机号 + 短信验证码登录
     * POST /api/auth/login-by-code
     */
    public function loginByCode(Request $request)
    {
        $phone = $request->input('phone', '');
        $code = $request->input('code', '');

        if (empty($phone) || empty($code)) {
            return $this->error('请输入手机号和验证码');
        }

        // 验证短信验证码
        $storedCode = Redis::get("sms_code:{$phone}");
        if (!$storedCode || $storedCode != $code) {
            return $this->error('验证码错误或已过期');
        }

        // 查找或创建用户
        $user = User::where('phone', $phone)->first();

        if (!$user) {
            // 新用户，自动注册
            $userId = User::generateId();
            $referralCode = User::generateReferralCode();

            $user = User::create([
                'id' => $userId,
                'phone' => $phone,
                'password' => '',
                'user_type' => 'customer',
                'active_role' => 'customer',
                'referral_code' => $referralCode,
                'status' => 1,
                'last_login_ip' => $request->getRealIp() ?: '',
            ]);
        }

        if ($user->status == 0) {
            return $this->error('账号已被禁用，请联系客服');
        }

        // 更新登录信息
        $user->last_login_at = date('Y-m-d H:i:s');
        $user->last_login_ip = $request->getRealIp() ?: '';
        $user->save();

        // 清除验证码
        Redis::del("sms_code:{$phone}");

        $token = $user->generateToken();

        return $this->success([
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'phone' => $this->maskPhone($user->phone),
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
                'gender' => $user->gender,
                'user_type' => $user->user_type,
                'active_role' => $user->active_role,
            ],
        ], '登录成功');
    }

    /**
     * 忘记密码 - 通过短信验证码重置密码
     * POST /api/auth/forget-password
     */
    public function forgetPassword(Request $request)
    {
        $phone = $request->input('phone', '');
        $code = $request->input('code', '');
        $password = $request->input('password', '');
        $confirmPassword = $request->input('confirm_password', '');

        if (empty($phone) || empty($code) || empty($password)) {
            return $this->error('请填写完整信息');
        }

        if ($password !== $confirmPassword) {
            return $this->error('两次输入的密码不一致');
        }

        if (strlen($password) < 6) {
            return $this->error('密码长度不能少于6位');
        }

        // 验证短信验证码
        $storedCode = Redis::get("sms_code:{$phone}");
        if (!$storedCode || $storedCode != $code) {
            return $this->error('验证码错误或已过期');
        }

        $user = User::where('phone', $phone)->first();
        if (!$user) {
            return $this->error('该手机号未注册');
        }

        $user->password = password_hash($password, PASSWORD_BCRYPT);
        $user->save();

        // 清除验证码
        Redis::del("sms_code:{$phone}");

        return $this->success(null, '密码重置成功');
    }

    /**
     * 切换用户身份角色
     * POST /api/auth/switch-role
     */
    public function switchRole(Request $request)
    {
        $userId = $request->user_id;
        $role = $request->input('role', 'customer');

        if (!in_array($role, ['customer', 'technician'])) {
            return $this->error('无效的角色类型');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在');
        }

        // 切换到技师身份时，需要验证是否有已通过的技师档案
        if ($role === 'technician') {
            $profile = \app\model\TechnicianProfile::where('user_id', $userId)
                ->where('status', 'approved')
                ->first();

            if (!$profile) {
                return $this->error('您还没有通过技师认证，无法切换为技师身份');
            }
        }

        $user->active_role = $role;
        $user->save();

        // 生成新 token（包含更新后的角色信息）
        $token = $user->generateToken();

        return $this->success([
            'token' => $token,
            'active_role' => $role,
        ], '角色切换成功');
    }

    /**
     * 刷新 JWT 令牌
     * POST /api/auth/refresh
     */
    public function refresh(Request $request)
    {
        $userId = $request->user_id;

        // 从 Authorization 头提取 token
        $header = $request->header('Authorization', '');
        $token = '';
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $token = $matches[1];
        }

        if (empty($token)) {
            return $this->error('缺少认证令牌', 401);
        }

        try {
            $jwtConfig = config('plugin.erikwang2013.jwt.jwt');
            $jwt = JWTFactory::createFromConfig($jwtConfig);

            // 验证当前 token 是否有效
            $payload = $jwt->decode($token);

            // 将当前 token 加入黑名单
            $jwt->blacklist($token);

            // 查找用户并生成新 token
            $user = User::find($userId);
            if (!$user) {
                return $this->error('用户不存在', 401);
            }

            $newToken = $user->generateToken();

            return $this->success([
                'token' => $newToken,
            ], '令牌刷新成功');
        } catch (\Exception $e) {
            return $this->error('令牌无效或已过期', 401);
        }
    }

    /**
     * 退出登录（客户端处理为主，服务端可做黑名单等）
     * POST /api/auth/logout
     */
    public function logout(Request $request)
    {
        // 从 Authorization 头提取 token 并加入黑名单
        $header = $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $token = $matches[1];
            try {
                $jwtConfig = config('plugin.erikwang2013.jwt.jwt');
                $jwt = JWTFactory::createFromConfig($jwtConfig);
                $jwt->blacklist($token);
            } catch (\Exception $e) {
                // 静默处理，不影响登出体验
            }
        }

        return $this->success(null, '已退出登录');
    }

    /**
     * 手机号脱敏：保留前3后4，中间用****代替
     */
    private function maskPhone(string $phone): string
    {
        if (strlen($phone) >= 7) {
            return substr($phone, 0, 3) . '****' . substr($phone, -4);
        }
        return $phone;
    }
}
