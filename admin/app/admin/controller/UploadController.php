<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace app\admin\controller;

use support\Request;
use support\Response;

class UploadController extends BaseController
{
    private array $allowExts = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'xlsx', 'docx'];
    private int $maxSize = 10 * 1024 * 1024;

    private array $allowMimes = [
        'jpg'  => ['image/jpeg', 'image/jpg'],
        'jpeg' => ['image/jpeg', 'image/jpg'],
        'png'  => ['image/png'],
        'gif'  => ['image/gif'],
        'pdf'  => ['application/pdf'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
    ];

    private array $blockedMimes = [
        'text/html', 'application/x-httpd-php', 'application/x-sh',
        'application/x-msdownload', 'application/x-executable',
    ];

    /**
     * @Apidoc\Title("文件上传")
     * @Apidoc\Group("upload")
     * @Apidoc\Method("POST")
     * @Apidoc\Url("/admin/upload")
     * @Apidoc\Desc("上传文件，支持jpg/png/gif/pdf/xlsx/docx")
     * @Apidoc\Param("file", type="file", require=true, desc="上传文件")
     * @Apidoc\Returned("url", type="string", desc="文件访问路径")
     */
    public function upload(Request $request): Response
    {
        $file = $request->file('file');
        if (!$file) {
            return $this->fail('请选择文件', 422);
        }

        if (!$file->isValid()) {
            return $this->fail('文件上传失败', 500);
        }

        $ext = strtolower($file->getUploadExtension() ?: 'bin');
        if (!in_array($ext, $this->allowExts, true)) {
            return $this->fail('不支持的文件类型: .' . $ext, 422);
        }

        if ($file->getSize() > $this->maxSize) {
            return $this->fail('文件大小不能超过 10MB', 422);
        }

        $mime = $file->getUploadMimeType();
        if (!empty($mime)) {
            if (in_array($mime, $this->blockedMimes, true)) {
                return $this->fail('不允许的文件类型', 422);
            }
            $allowed = $this->allowMimes[$ext] ?? [];
            if (!empty($allowed) && !in_array($mime, $allowed, true)) {
                return $this->fail('文件 MIME 类型与扩展名不匹配', 422);
            }
        }

        // M4: 魔数校验 —— 以文件真实内容识别 MIME，防止伪造客户端 MIME 声明上传恶意文件
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $realMime = $file->getRealPath() ? $finfo->file($file->getRealPath()) : false;
        if (!$realMime) {
            return $this->fail('无法读取文件内容', 422);
        }
        // xlsx/docx 为 ZIP 容器，finfo 识别为 application/zip 等，同族放行
        $magicMap = [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'gif'  => ['image/gif'],
            'pdf'  => ['application/pdf'],
            'xlsx' => ['application/zip', 'application/x-zip-compressed', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
            'docx' => ['application/zip', 'application/x-zip-compressed', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        ];
        $realAllowed = $magicMap[$ext] ?? [];
        if (!in_array($realMime, $realAllowed, true)) {
            return $this->fail('文件内容与扩展名不匹配，拒绝上传', 422);
        }

        $dateDir  = date('Y-m-d');
        $filename = md5(uniqid((string) mt_rand(), true)) . '.' . $ext;
        $relativePath = "/upload/{$dateDir}/{$filename}";
        $absoluteDir  = public_path() . "/upload/{$dateDir}";

        if (!is_dir($absoluteDir)) {
            mkdir($absoluteDir, 0755, true);
        }

        $file->move($absoluteDir . '/' . $filename);

        return $this->success(['url' => $relativePath], '上传成功');
    }
}
