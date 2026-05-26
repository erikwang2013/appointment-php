<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\middleware;

use support\Db;
use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

/**
 * 技师身份认证中间件
 * 验证当前登录用户是否为已审核通过的技师
 * 需要在 Auth 中间件之后执行，依赖 $request->user_id
 * 验证失败返回 403 禁止访问响应
 */
class TechnicianAuth implements MiddlewareInterface
{
    /**
     * @param Request $request
     * @param callable $next
     * @return Response
     */
    public function process(Request $request, callable $next): Response
    {
        // 依赖 Auth 中间件注入的 user_id
        $userId = $request->user_id ?? null;

        if ($userId === null) {
            return $this->forbidden('请先登录');
        }

        // 查询技师档案，验证身份状态
        $technician = Db::table('erik_technician_profile')
            ->where('user_id', $userId)
            ->first();

        if ($technician === null) {
            return $this->forbidden('您还未成为技师，请先提交技师认证申请');
        }

        // 状态为 'approved' 才允许访问技师接口
        if ($technician->status !== 'approved') {
            $statusMap = [
                'pending' => '您的技师申请正在审核中，请耐心等待',
                'rejected' => '您的技师申请未通过审核',
                'disabled' => '您的技师账号已被禁用',
            ];
            $message = $statusMap[$technician->status] ?? '您的技师身份验证未通过';
            return $this->forbidden($message);
        }

        // 注入技师信息供后续控制器使用
        $request->technician_id = $technician->id;

        return $next($request);
    }

    /**
     * 返回 403 禁止访问响应
     * @param string $message
     * @return Response
     */
    private function forbidden(string $message): Response
    {
        return json([
            'code' => 403,
            'message' => $message,
            'data' => null,
        ])->withStatus(403);
    }
}
