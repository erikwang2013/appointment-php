<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\User;
use ErikJwt\JWTFactory;
use support\Redis;
use Webman\Http\Request;

/**
 * 用户个人资料控制器
 * 查看/编辑个人资料、修改密码、更换手机号、注销账号
 */
class ProfileController extends BaseController
{
    /**
     * 获取当前用户信息
     * GET /api/user/profile
     */
    public function show(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        return $this->success([
            'id' => $user->id,
            'phone' => $this->maskPhone($user->phone),
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'gender' => $user->gender,
            'user_type' => $user->user_type,
            'active_role' => $user->active_role,
            'referral_code' => $user->referral_code,
            'created_at' => $user->created_at,
        ]);
    }

    /**
     * 更新个人资料
     * PUT /api/user/profile
     */
    public function update(Request $request)
    {
        $userId = $request->user_id;

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $data = [];

        if ($request->has('nickname')) {
            $nickname = trim($request->input('nickname', ''));
            if (mb_strlen($nickname) > 50) {
                return $this->error('昵称长度不能超过50个字符');
            }
            $data['nickname'] = $nickname;
        }

        if ($request->has('avatar')) {
            $data['avatar'] = trim($request->input('avatar', ''));
        }

        if ($request->has('gender')) {
            $gender = (int)$request->input('gender', 0);
            if (!in_array($gender, [0, 1, 2])) {
                return $this->error('无效的性别参数');
            }
            $data['gender'] = $gender;
        }

        if (empty($data)) {
            return $this->error('没有需要更新的信息');
        }

        $user->fill($data);
        $user->save();

        return $this->success([
            'nickname' => $user->nickname,
            'avatar' => $user->avatar,
            'gender' => $user->gender,
        ], '资料更新成功');
    }

    /**
     * 修改密码
     * POST /api/user/change-password
     */
    public function changePassword(Request $request)
    {
        $userId = $request->user_id;
        $oldPassword = $request->input('old_password', '');
        $newPassword = $request->input('new_password', '');
        $confirmPassword = $request->input('confirm_password', '');

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            return $this->error('请填写完整信息');
        }

        if ($newPassword !== $confirmPassword) {
            return $this->error('两次输入的新密码不一致');
        }

        if (strlen($newPassword) < 6) {
            return $this->error('新密码长度不能少于6位');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // SMS注册用户没有旧密码，跳过旧密码验证
        $hasNoPassword = empty($user->password);
        if (!$hasNoPassword && !password_verify($oldPassword, $user->password)) {
            return $this->error('原密码错误');
        }

        $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->save();

        return $this->success(null, '密码修改成功');
    }

    /**
     * 更换手机号
     * POST /api/user/change-phone
     */
    public function changePhone(Request $request)
    {
        $userId = $request->user_id;
        $newPhone = $request->input('new_phone', '');
        $oldCode = $request->input('old_code', '');
        $newCode = $request->input('new_code', '');

        if (empty($newPhone) || empty($oldCode) || empty($newCode)) {
            return $this->error('请填写完整信息');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $newPhone)) {
            return $this->error('请输入正确的手机号码');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        // 验证旧手机验证码
        $storedOldCode = Redis::get("sms_code:{$user->phone}");
        if (!$storedOldCode || $storedOldCode != $oldCode) {
            return $this->error('旧手机验证码错误或已过期');
        }

        // 验证新手机验证码
        $storedNewCode = Redis::get("sms_code:{$newPhone}");
        if (!$storedNewCode || $storedNewCode != $newCode) {
            return $this->error('新手机验证码错误或已过期');
        }

        // 检查新手机号是否已被占用
        $exists = User::where('phone', $newPhone)
            ->where('id', '!=', $userId)
            ->exists();
        if ($exists) {
            return $this->error('该手机号已被其他账号绑定');
        }

        $user->phone = $newPhone;
        $user->save();

        // 清除验证码
        Redis::del("sms_code:{$user->phone}");
        Redis::del("sms_code:{$newPhone}");

        return $this->success([
            'phone' => $this->maskPhone($newPhone),
        ], '手机号更换成功');
    }

    /**
     * 注销账号（软删除）
     * POST /api/user/cancel-account
     */
    public function cancelAccount(Request $request)
    {
        $userId = $request->user_id;
        $password = $request->input('password', '');

        if (empty($password)) {
            return $this->error('请输入密码确认注销');
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->error('用户不存在', 404);
        }

        $hasNoPassword = empty($user->password);
        if (!$hasNoPassword && !password_verify($password, $user->password)) {
            return $this->error('密码错误');
        }

        $user->delete();

        // 将当前 token 加入黑名单
        $header = $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            $token = $matches[1];
            try {
                $jwtConfig = config('plugin.erikwang2013.jwt.jwt');
                $jwt = JWTFactory::createFromConfig($jwtConfig);
                $jwt->blacklist($token);
            } catch (\Exception $e) {
                // 静默处理
            }
        }

        return $this->success(null, '账号已注销');
    }

    /**
     * 退出登录
     * POST /api/user/logout
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
                // 静默处理
            }
        }

        return $this->success(null, '已退出登录');
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
