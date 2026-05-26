<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use support\Log;

/**
 * 语音服务
 *
 * 处理语音备注的上传、下载与转文字
 * 生产环境中应接入百度语音 / 微信语音识别 API
 */
class VoiceService
{
    /**
     * 语音转文字
     *
     * 接收音频文件 URL，返回文字转录结果。
     * 当前为占位实现（返回 mock 文本），生产环境需接入 ASR 服务。
     *
     * @param string $audioUrl 音频文件的可访问 URL
     * @return array{text: string, confidence: float, duration: int}
     */
    public function transcribe(string $audioUrl): array
    {
        // 生产环境中调用百度语音 / 微信语音识别 API
        // 示例: $result = BaiduAsr::recognize($audioUrl);

        Log::info('[VoiceService] transcribe placeholder called', ['url' => $audioUrl]);

        return [
            'text'       => '[语音转文字占位] ' . $audioUrl,
            'confidence' => 0.0,
            'duration'   => 0,
        ];
    }

    /**
     * 下载微信小程序语音录音
     *
     * 通过 media_id 从微信服务器下载临时语音文件，
     * 上传至自有云存储并返回可访问的 URL。
     *
     * @param string $mediaId 微信临时素材 media_id
     * @return string 云存储中语音文件的 URL
     */
    public function downloadVoice(string $mediaId): string
    {
        // 1. 获取 access_token
        // $accessToken = $this->getAccessToken();

        // 2. 从微信服务器下载语音
        // $url = "https://api.weixin.qq.com/cgi-bin/media/get?access_token={$accessToken}&media_id={$mediaId}";
        // $voiceData = file_get_contents($url);

        // 3. 上传至云存储（OSS / COS）
        // $storage = new StorageService();
        // $result = $storage->upload('voice/' . date('Ymd') . '/' . uniqid() . '.amr', $voiceData);

        // 4. 返回可访问 URL
        // return $result['url'];

        Log::info('[VoiceService] downloadVoice placeholder', ['media_id' => $mediaId]);

        // 占位：返回示意 URL
        return 'https://storage.example.com/voice/' . date('Ymd') . '/' . $mediaId . '.amr';
    }
}
