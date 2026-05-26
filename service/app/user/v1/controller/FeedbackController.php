<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\user\v1\controller;

use app\common\BaseController;
use app\model\UserFeedback;
use Webman\Http\Request;

/**
 * 用户意见反馈控制器
 */
class FeedbackController extends BaseController
{
    /**
     * 提交意见反馈
     * POST /api/user/feedback
     */
    public function store(Request $request)
    {
        $userId = $request->user_id;
        $content = trim($request->input('content', ''));
        $images = $request->input('images', []);

        if (empty($content)) {
            return $this->error('请输入反馈内容');
        }

        if (mb_strlen($content) > 1000) {
            return $this->error('反馈内容不能超过1000个字符');
        }

        // 验证图片格式
        if (!empty($images)) {
            if (!is_array($images)) {
                $images = [];
            }
            if (count($images) > 9) {
                return $this->error('最多上传9张图片');
            }
        }

        $feedback = UserFeedback::create([
            'id' => UserFeedback::generateId(),
            'user_id' => $userId,
            'content' => $content,
            'images' => !empty($images) ? json_encode($images, JSON_UNESCAPED_UNICODE) : null,
        ]);

        return $this->success($feedback, '感谢您的反馈');
    }
}
