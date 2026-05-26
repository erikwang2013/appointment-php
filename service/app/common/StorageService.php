<?php
// Copyright (c) 2026 erik <erik@erik.xyz> — https://erik.xyz

declare(strict_types=1);

namespace app\common;

use support\Db;
use support\Log;
use Webman\Http\UploadFile;

/**
 * 对象存储服务
 *
 * 支持本地存储、阿里云 OSS、腾讯云 COS
 */
class StorageService
{
    private string $provider;
    private string $accessKey;
    private string $secretKey;
    private string $bucket;
    private string $endpoint;
    private string $cdnDomain;

    private const OSS_HOST_FORMAT = '%s.%s'; // bucket.endpoint
    private const COS_HOST_FORMAT = '%s.cos.%s.myqcloud.com'; // bucket.cos.region.myqcloud.com

    public function __construct()
    {
        $configs = Db::table('erik_system_config')
            ->where('group', 'storage')
            ->pluck('value', 'key')
            ->toArray();

        $this->provider  = $configs['provider'] ?? 'local';
        $this->accessKey = $configs['access_key'] ?? '';
        $this->secretKey = $configs['secret_key'] ?? '';
        $this->bucket    = $configs['bucket'] ?? '';
        $this->endpoint  = $configs['endpoint'] ?? '';
        $this->cdnDomain = $configs['cdn_domain'] ?? '';
    }

    /**
     * 上传文件
     *
     * @param UploadFile $file 上传的文件对象
     * @param string     $path 存储路径（不含前缀），如 'avatars/2026/05/'
     * @return array{success: bool, url: string, path: string, message: string}
     */
    public function upload(UploadFile $file, string $path = ''): array
    {
        if (!$file->isValid()) {
            return ['success' => false, 'url' => '', 'path' => '', 'message' => '文件无效'];
        }

        $ext = strtolower($file->getUploadExtension() ?: 'bin');

        // 构造存储路径
        $filename = md5(uniqid((string) random_int(0, 999999), true)) . '.' . $ext;
        $relativePath = rtrim($path, '/') . '/' . $filename;
        $relativePath = ltrim($relativePath, '/');

        $tmpFile = $file->getRealPath();

        try {
            return match ($this->provider) {
                'oss'  => $this->uploadOss($tmpFile, $relativePath, $ext),
                'cos'  => $this->uploadCos($tmpFile, $relativePath, $ext),
                default => $this->uploadLocal($tmpFile, $relativePath),
            };
        } catch (\Throwable $e) {
            Log::error('[StorageService] upload error: ' . $e->getMessage());
            return ['success' => false, 'url' => '', 'path' => $relativePath, 'message' => '上传失败: ' . $e->getMessage()];
        }
    }

    /**
     * 删除文件
     *
     * @param string $url 文件的完整 URL 或路径
     * @return array{success: bool, message: string}
     */
    public function delete(string $url): array
    {
        if (empty($url)) {
            return ['success' => false, 'message' => '文件路径不能为空'];
        }

        // 从 URL 中提取相对路径
        $relativePath = $this->extractPathFromUrl($url);

        if (empty($relativePath)) {
            return ['success' => false, 'message' => '无法解析文件路径'];
        }

        try {
            return match ($this->provider) {
                'oss'  => $this->deleteOss($relativePath),
                'cos'  => $this->deleteCos($relativePath),
                default => $this->deleteLocal($relativePath),
            };
        } catch (\Throwable $e) {
            Log::error('[StorageService] delete error: ' . $e->getMessage());
            return ['success' => false, 'message' => '删除失败: ' . $e->getMessage()];
        }
    }

    /**
     * 获取文件公开访问 URL
     *
     * @param string $path 文件的相对路径
     * @return string 完整的访问 URL
     */
    public function url(string $path): string
    {
        if (empty($path)) {
            return '';
        }

        // 如果已经是完整 URL，直接返回
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        // CDN 域名优先
        if (!empty($this->cdnDomain)) {
            $domain = rtrim($this->cdnDomain, '/');
            return $domain . '/' . $path;
        }

        return match ($this->provider) {
            'oss'  => rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . $path,
            'cos'  => rtrim($this->endpoint, '/') . '/' . $this->bucket . '/' . $path,
            default => '/uploads/' . $path,
        };
    }

    /**
     * 生成临时访问 URL（带签名，用于私有资源）
     *
     * @param string $path    文件路径
     * @param int    $expires 过期时间（秒），默认 3600 秒（1 小时）
     * @return string 带签名的临时 URL
     */
    public function temporaryUrl(string $path, int $expires = 3600): string
    {
        if (empty($path)) {
            return '';
        }

        $path = ltrim($path, '/');

        try {
            return match ($this->provider) {
                'oss'  => $this->ossTemporaryUrl($path, $expires),
                'cos'  => $this->cosTemporaryUrl($path, $expires),
                default => $this->url($path), // 本地存储不签名
            };
        } catch (\Throwable $e) {
            Log::error('[StorageService] temporaryUrl error: ' . $e->getMessage());
            return $this->url($path);
        }
    }

    // ── 本地存储 ──

    /**
     * 本地上传
     */
    private function uploadLocal(string $tmpFile, string $relativePath): array
    {
        $uploadDir = public_path() . '/uploads/' . dirname($relativePath);
        $absolutePath = public_path() . '/uploads/' . $relativePath;

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (!copy($tmpFile, $absolutePath)) {
            return ['success' => false, 'url' => '', 'path' => $relativePath, 'message' => '文件保存失败'];
        }

        $fileUrl = $this->url($relativePath);

        return ['success' => true, 'url' => $fileUrl, 'path' => $relativePath, 'message' => '上传成功'];
    }

    /**
     * 本地删除
     */
    private function deleteLocal(string $relativePath): array
    {
        $absolutePath = public_path() . '/uploads/' . $relativePath;

        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }

        return ['success' => true, 'message' => '删除成功'];
    }

    // ── 阿里云 OSS ──

    /**
     * OSS 上传
     *
     * 使用 AWS Signature V2 兼容的签名方式
     */
    private function uploadOss(string $tmpFile, string $relativePath, string $ext): array
    {
        $fileContent = file_get_contents($tmpFile);
        if ($fileContent === false) {
            return ['success' => false, 'url' => '', 'path' => $relativePath, 'message' => '读取文件失败'];
        }

        $contentType = $this->getContentType($ext);
        $date        = gmdate('D, d M Y H:i:s \G\M\T');
        $host        = sprintf(self::OSS_HOST_FORMAT, $this->bucket, $this->endpoint);

        $stringToSign = "PUT\n\n{$contentType}\n{$date}\n/{$this->bucket}/{$relativePath}";

        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));

        $headers = [
            'Content-Type: ' . $contentType,
            'Date: ' . $date,
            'Authorization: OSS ' . $this->accessKey . ':' . $signature,
            'Content-Length: ' . strlen($fileContent),
        ];

        $url = 'https://' . $host . '/' . $relativePath;

        $response = $this->httpPut($url, $fileContent, $headers);

        if ($response === false) {
            return ['success' => false, 'url' => '', 'path' => $relativePath, 'message' => 'OSS 上传失败'];
        }

        $fileUrl = $this->url($relativePath);

        return ['success' => true, 'url' => $fileUrl, 'path' => $relativePath, 'message' => '上传成功'];
    }

    /**
     * OSS 删除
     */
    private function deleteOss(string $relativePath): array
    {
        $date = gmdate('D, d M Y H:i:s \G\M\T');
        $host = sprintf(self::OSS_HOST_FORMAT, $this->bucket, $this->endpoint);

        $stringToSign = "DELETE\n\n\n{$date}\n/{$this->bucket}/{$relativePath}";

        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));

        $headers = [
            'Date: ' . $date,
            'Authorization: OSS ' . $this->accessKey . ':' . $signature,
        ];

        $url = 'https://' . $host . '/' . $relativePath;

        $this->httpDelete($url, $headers);

        return ['success' => true, 'message' => '删除成功'];
    }

    /**
     * OSS 临时 URL
     */
    private function ossTemporaryUrl(string $path, int $expires): string
    {
        $expiration = time() + $expires;
        $host       = sprintf(self::OSS_HOST_FORMAT, $this->bucket, $this->endpoint);

        $stringToSign = "GET\n\n\n{$expiration}\n/{$this->bucket}/{$path}";

        $signature = base64_encode(hash_hmac('sha1', $stringToSign, $this->secretKey, true));
        $encodedSignature = urlencode($signature);

        $encodedAccessKey = urlencode($this->accessKey);
        $encodedExpires   = urlencode((string) $expiration);

        return "https://{$host}/{$path}?OSSAccessKeyId={$encodedAccessKey}&Expires={$encodedExpires}&Signature={$encodedSignature}";
    }

    // ── 腾讯云 COS ──

    /**
     * COS 上传
     *
     * 使用 COS V5 签名
     */
    private function uploadCos(string $tmpFile, string $relativePath, string $ext): array
    {
        $fileContent = file_get_contents($tmpFile);
        if ($fileContent === false) {
            return ['success' => false, 'url' => '', 'path' => $relativePath, 'message' => '读取文件失败'];
        }

        $contentType = $this->getContentType($ext);
        $host        = sprintf(self::COS_HOST_FORMAT, $this->bucket, $this->endpoint);

        // COS 签名
        $qSignAlgorithm = 'sha1';
        $qAk            = $this->accessKey;
        $qSignTime      = (string) time() . ';' . (string) (time() + 3600);
        $qKeyTime       = $qSignTime;
        $qHeaderList    = 'content-length;content-type;host';
        $qUrlParamList  = '';

        $httpHeaders   = "content-length=" . strlen($fileContent) . "&content-type={$contentType}&host={$host}";
        $httpString    = "put\n/{$relativePath}\n\n{$httpHeaders}\n";
        $stringToSign  = "{$qSignAlgorithm}\n{$qSignTime}\n" . sha1($httpString) . "\n";

        $signKey    = hash_hmac('sha1', $qKeyTime, $this->secretKey);
        $signature  = hash_hmac('sha1', $stringToSign, $signKey);

        $authorization = "q-sign-algorithm={$qSignAlgorithm}&q-ak={$qAk}&q-sign-time={$qSignTime}"
                       . "&q-key-time={$qKeyTime}&q-header-list={$qHeaderList}&q-url-param-list={$qUrlParamList}"
                       . "&q-signature={$signature}";

        $headers = [
            'Content-Type: ' . $contentType,
            'Host: ' . $host,
            'Authorization: ' . $authorization,
            'Content-Length: ' . strlen($fileContent),
        ];

        $url = 'https://' . $host . '/' . $relativePath;

        $response = $this->httpPut($url, $fileContent, $headers);

        if ($response === false) {
            return ['success' => false, 'url' => '', 'path' => $relativePath, 'message' => 'COS 上传失败'];
        }

        $fileUrl = $this->url($relativePath);

        return ['success' => true, 'url' => $fileUrl, 'path' => $relativePath, 'message' => '上传成功'];
    }

    /**
     * COS 删除
     */
    private function deleteCos(string $relativePath): array
    {
        $host = sprintf(self::COS_HOST_FORMAT, $this->bucket, $this->endpoint);

        $qSignAlgorithm = 'sha1';
        $qAk            = $this->accessKey;
        $qSignTime      = (string) time() . ';' . (string) (time() + 3600);
        $qKeyTime       = $qSignTime;
        $qHeaderList    = 'host';
        $qUrlParamList  = '';

        $httpHeaders   = "host={$host}";
        $httpString    = "delete\n/{$relativePath}\n\n{$httpHeaders}\n";
        $stringToSign  = "{$qSignAlgorithm}\n{$qSignTime}\n" . sha1($httpString) . "\n";

        $signKey    = hash_hmac('sha1', $qKeyTime, $this->secretKey);
        $signature  = hash_hmac('sha1', $stringToSign, $signKey);

        $authorization = "q-sign-algorithm={$qSignAlgorithm}&q-ak={$qAk}&q-sign-time={$qSignTime}"
                       . "&q-key-time={$qKeyTime}&q-header-list={$qHeaderList}&q-url-param-list={$qUrlParamList}"
                       . "&q-signature={$signature}";

        $headers = [
            'Host: ' . $host,
            'Authorization: ' . $authorization,
        ];

        $url = 'https://' . $host . '/' . $relativePath;

        $this->httpDelete($url, $headers);

        return ['success' => true, 'message' => '删除成功'];
    }

    /**
     * COS 临时 URL
     */
    private function cosTemporaryUrl(string $path, int $expires): string
    {
        $host = sprintf(self::COS_HOST_FORMAT, $this->bucket, $this->endpoint);

        $currentTime = time();
        $expireTime  = $currentTime + $expires;

        $qSignAlgorithm = 'sha1';
        $qAk            = $this->accessKey;
        $qSignTime      = "{$currentTime};{$expireTime}";
        $qKeyTime       = $qSignTime;
        $qHeaderList    = 'host';
        $qUrlParamList  = '';

        $httpHeaders   = "host={$host}";
        $httpString    = "get\n/{$path}\n\n{$httpHeaders}\n";
        $stringToSign  = "{$qSignAlgorithm}\n{$qSignTime}\n" . sha1($httpString) . "\n";

        $signKey    = hash_hmac('sha1', $qKeyTime, $this->secretKey);
        $signature  = hash_hmac('sha1', $stringToSign, $signKey);

        $authorization = "q-sign-algorithm={$qSignAlgorithm}&q-ak={$qAk}&q-sign-time={$qSignTime}"
                       . "&q-key-time={$qKeyTime}&q-header-list={$qHeaderList}&q-url-param-list={$qUrlParamList}"
                       . "&q-signature={$signature}";

        return "https://{$host}/{$path}?sign=" . urlencode($authorization);
    }

    // ── HTTP 工具 ──

    /**
     * HTTP PUT 请求
     */
    private function httpPut(string $url, string $body, array $headers = []): string|false
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'PUT',
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_TIMEOUT        => 60,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $error    = curl_error($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($error) {
                Log::error('[StorageService] cURL PUT error: ' . $error);
                return false;
            }

            if ($httpCode >= 400) {
                Log::error('[StorageService] HTTP PUT ' . $httpCode . ' from ' . $url);
                return false;
            }

            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            Log::error('[StorageService] httpPut exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * HTTP DELETE 请求
     */
    private function httpDelete(string $url, array $headers = []): string|false
    {
        try {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL            => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CUSTOMREQUEST  => 'DELETE',
                CURLOPT_TIMEOUT        => 30,
                CURLOPT_HTTPHEADER     => $headers,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
            ]);

            $response = curl_exec($ch);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                Log::error('[StorageService] cURL DELETE error: ' . $error);
                return false;
            }

            return is_string($response) ? $response : '';
        } catch (\Throwable $e) {
            Log::error('[StorageService] httpDelete exception: ' . $e->getMessage());
            return false;
        }
    }

    // ── 工具 ──

    /**
     * 根据文件扩展名获取 MIME 类型
     */
    private function getContentType(string $ext): string
    {
        return match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'  => 'image/png',
            'gif'  => 'image/gif',
            'webp' => 'image/webp',
            'pdf'  => 'application/pdf',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'json' => 'application/json',
            'xml'  => 'application/xml',
            'txt'  => 'text/plain',
            'html' => 'text/html',
            'css'  => 'text/css',
            'js'   => 'application/javascript',
            'mp4'  => 'video/mp4',
            'mp3'  => 'audio/mpeg',
            default => 'application/octet-stream',
        };
    }

    /**
     * 从完整 URL 中提取相对路径
     */
    private function extractPathFromUrl(string $url): string
    {
        // 本地路径
        if (str_starts_with($url, '/uploads/')) {
            return substr($url, 9); // 去掉 '/uploads/'
        }

        // CDN/OSS 完整 URL
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            $parsed = parse_url($url);
            $path = $parsed['path'] ?? '';
            $path = ltrim($path, '/');

            // 如果路径以 bucket 名称开头，去掉它
            if (!empty($this->bucket) && str_starts_with($path, $this->bucket . '/')) {
                $path = substr($path, strlen($this->bucket) + 1);
            }

            return $path;
        }

        return ltrim($url, '/');
    }
}
