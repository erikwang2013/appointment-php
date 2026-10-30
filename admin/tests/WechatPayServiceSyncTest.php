<?php
/*
 * Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz
 */

declare(strict_types=1);

namespace tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * admin 端保留了一份 WechatPayService 副本（service 目录未挂载时兜底），
 * 两份实现必须保持一致，否则兜底路径会与 service 版行为分叉。
 * 部署布局重构（admin 直接引用 service 代码）后可删除本测试。
 */
class WechatPayServiceSyncTest extends TestCase
{
    #[Test]
    public function admin_copy_matches_service_copy(): void
    {
        $adminFile = __DIR__ . '/../app/common/WechatPayService.php';
        $serviceFile = __DIR__ . '/../../service/app/common/WechatPayService.php';

        if (!is_file($serviceFile)) {
            $this->markTestSkipped('service 端 WechatPayService 未挂载，跳过同步校验');
        }

        $adminCode = $this->codeAfterStrictTypes((string) file_get_contents($adminFile));
        $serviceCode = $this->codeAfterStrictTypes((string) file_get_contents($serviceFile));

        $this->assertSame($serviceCode, $adminCode, 'admin 端 WechatPayService 副本与 service 端实现不一致');
    }

    /**
     * 去掉文件头（版权声明）与类文档注释，只比较实现
     *
     * 类文档注释允许不同：admin 副本需说明自己是 service 未挂载时的兜底实现；
     * 命名空间、use 导入与类实现必须逐字一致。
     */
    private function codeAfterStrictTypes(string $code): string
    {
        $marker = 'declare(strict_types=1);';
        $pos = strpos($code, $marker);
        $code = $pos === false ? $code : substr($code, $pos + strlen($marker));

        // 仅剔除紧邻 class 声明的文档注释块
        return preg_replace('#/\*\*.*?\*/\s*(?=class\s+WechatPayService)#s', '', $code, 1) ?? $code;
    }
}
