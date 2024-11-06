<?php
/**
 * AWS S3 handle class for Holkee
 *
 * @author      Jeff Ho <jeffho@weblisher.com.tw>
 * @access      public
 * @version     Release: 1.0
 */
namespace Jeffho\HkAwsS3;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class AWSS3Handler
{
    const CDNURL = 'https://cloud.holkee.com';
    /**
     * 设置 S3 客户端
     *
     * @param string $region 区域
     * @param string $key 访问密钥
     * @param string $secret 秘密密钥
     * @return S3Client 返回 S3 客户端实例
     */
    public function setClient(string $region, string $key, string $secret): S3Client
    {
        return new S3Client([
            'version' => 'latest',
            'region' => $region,
            'credentials' => [
                'key' => $key,
                'secret' => $secret
            ]
        ]);
    }

    /**
     * 列出存储桶中的对象
     *
     * @param S3Client $client S3 客户端
     * @param string $key 配置键
     * @param string|null $prefix 前缀
     * @return \stdClass 返回包含对象列表或错误信息的响应对象
     */
    public function listObjects(S3Client $client, string $key, ?string $prefix = null): \stdClass
    {
        $response = new \stdClass();
        try {
            $config = $this->getConfig($key);
            if (!isset($config)) {
                throw new \Exception('無效的配置鍵');
            }
            $params = [
                'Bucket' => $config['bucket'],
            ];
            if ($prefix !== null) {
                $params['Prefix'] = $config['root_path'] . $prefix;
            }
            $result = $client->listObjectsV2($params);
            $response->status = 'success';
            $response->objects = $result['Contents'];
        } catch (AwsException $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        } catch (\Exception $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        }
        return $response;
    }

    /**
     * 向存储桶中上传对象
     *
     * @param S3Client $client S3 客户端
     * @param string $bucketKey 存储桶配置鍵
     * @param string $objectKey 对象键
     * @param string $source 源文件路径
     * @return \stdClass 返回包含上传结果或错误信息的响应对象
     */
    public function putObject(S3Client $client, string $bucketKey, string $objectKey, string $source): \stdClass
    {
        $response = new \stdClass();
        try {
            $config = $this->getConfig($bucketKey);
            if (!isset($config)) {
                throw new \Exception('無效的配置鍵');
            }
            $objectKey = $config['root_path'] . $objectKey;
            $result = $client->putObject([
                'Bucket' => $config['bucket'],
                'Key' => $objectKey,
                'SourceFile' => $source,
            ]);
            $response->status = 'success';
            $response->object = $objectKey;
        } catch (AwsException $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        } catch (\Exception $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        }
        return $response;
    }

    /**
     * 複製存储桶中的对象
     *
     * @param S3Client $client S3 客户端
     * @param string $bucketKey 存储桶配置鍵
     * @param string $objectKey 新对象键
     * @param string $copySource 複製對象源路径
     * @return \stdClass 返回包含上传结果或错误信息的响应对象
     */
    public function copyObject(S3Client $client, string $bucketKey, string $objectKey, string $copySource): \stdClass
    {
        $response = new \stdClass();
        try {
            $config = $this->getConfig($bucketKey);
            if (!isset($config)) {
                throw new \Exception('無效的配置鍵');
            }
            $destinationKey = $config['root_path'] . $objectKey;
            $sourceKey = $this->getObjectKey($copySource);
            $sourceBucket = $this->getObjectBucket($sourceKey);
            $result = $client->copyObject([
                'Bucket' => $config['bucket'],
                'Key' => $destinationKey,
                'CopySource' => "{$sourceBucket}/{$sourceKey}",
            ]);
            $response->status = 'success';
            $response->object = $destinationKey;
        } catch (AwsException $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        } catch (\Exception $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        }
        return $response;
    }

    /**
     * 删除存储桶中的对象
     *
     * @param S3Client $client S3 客户端
     * @param string $bucket 存储桶
     * @param string $objectKey 对象键
     * @return \stdClass 返回包含删除结果或错误信息的响应对象
     */
    public function deleteObject(S3Client $client, string $bucket, string $objectKey): \stdClass
    {
        $response = new \stdClass();
        try {
            $client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $objectKey
            ]);
            $response->status = 'success';
        } catch (AwsException $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        } catch (\Exception $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        }
        return $response;
    }

    /**
     * 获取配置
     *
     * @param string|null $key 配置键
     * @return mixed 返回指定配置项或整个配置数组
     * @throws \RuntimeException 如果配置文件不存在
     */
    public function getConfig(?string $key = null)
    {
        $configPath = realpath(__DIR__ . '/../config/hk-aws-s3.php');
        if (!file_exists($configPath)) {
            throw new \RuntimeException('Configuration file not found.');
        }
        $config = include $configPath;
        if ($key !== null) {
            return $config[$key] ?? null;
        }
        return $config;
    }

    /**
     * 检查文件扩展名是否在白名单内
     *
     * @param string $key 配置键
     * @param string $fileName 文件名
     * @return bool 如果文件扩展名在白名单内返回 true，否则返回 false
     */
    public function isAllowedFileType(string $key, string $fileName): bool
    {
        // 从配置文件获取允许的文件扩展名
        $config = $this->getConfig($key);
        $allowedExtensions = $config['allowed_file'] ?? [];

        // 获取文件扩展名
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // 检查扩展名是否在允许的列表中
        return in_array($fileExtension, $allowedExtensions, true);
    }

    /**
     * 获取对象的儲存桶
     *
     * @param string $objectKey 物件對象鍵
     * @return string 对象的儲存桶
     */
    public function getObjectBucket(string $objectKey)
    {
        $segments = explode('/', $objectKey);
        $rootPath = $segments[0] . '/';
        $key = $this->findKeyByRootPath($rootPath);
        // 取得配置文件
        $config = $this->getConfig($key);
        // 從配置文件取得儲存桶, 若設定檔沒有訊息, 則為舊儲存桶
        return $config['bucket'] ?? 'holkee';
    }

    /**
     * 获取对象的鍵值
     *
     * @param string $source 源路径
     * @return string 对象的鍵值
     */
    public function getObjectKey(string $source)
    {
        if (preg_match('/(https?:)?\/\/img\.holkee\.com/', $source)) {
            // 定義正規表達式
            $pattern = '/(https?:)?\/\/img\.holkee\.com/';
            // 使用 preg_replace 進行替換
            $result = preg_replace($pattern, 'images', $source);
        } else {
            // 定義正規表達式
            $pattern = '/(https?:)?\/\/(cdn|cloud)\.holkee\.com\//';
            // 使用 preg_replace 進行替換
            $result = preg_replace($pattern, '', $source);
        }
        // 如果替換後的字串是以 'site/' 開頭，將其替換為 'image/site/'
        if (strpos($result, 'site/') === 0) {
            $result = 'images/' . $result;
        }
        // 回傳替換後的結果
        return $result;
    }

    /**
     * 获取对象的 URL
     *
     * @param string $type 类型
     * @param string $source 源路径
     * @param string|null $option 选项
     * @return string 对象的 URL
     */
    public function getObjectUrl(string $type, string $source, string $option = null)
    {
        switch ($type) {
            case 'carousel':
            case 'latest_news':
            case 'store_intro':
            case 'service_item':
            case 'marketing':
            case 'appointed':
            case 'official_theme':
                return self::CDNURL . '/' . $source;
            case 'favicon':
            case 'share':
            case 'store_logo':
                if (preg_match('/^(?:user-website-assets)\//i', $source)) {
                    return self::CDNURL . '/' . $source;
                } else {
                    return $this->getOldBucketObjectUrl($type, $source, $option);
                }
            case 'product':
                if (preg_match('/^(?:user-product-assets)\//i', $source)) {
                    return self::CDNURL . '/' . $source;
                } else {
                    return $this->getOldBucketObjectUrl($type, $source, $option);
                }
            default:
                return $this->getOldBucketObjectUrl($type, $source, $option);
        }
    }

    /**
     * 获取旧存储桶中对象的 URL
     *
     * @param string $type 类型
     * @param string $source 源路径
     * @param string|null $option 选项
     * @return string 旧存储桶中对象的 URL
     */
    private function getOldBucketObjectUrl(string $type, string $source, string $option = null)
    {
        $oldCdn = 'https://img.holkee.com';
        switch ($type) {
            case 'favicon':
                return $oldCdn . '/site/' . $option . 'icons/favicon-' . $source . '-32x32.png';
            case 'share':
            case 'store_logo':
            case 'product':
            default:
                return $oldCdn . '/' . $source;
        }
    }

    /**
     * 根據指定的 root_path 尋找對應的陣列 key。
     *
     * @param string $rootPath 要尋找的 root_path 字串。
     * @return string|null 找到的 key 或者 null 如果找不到。
     */
    private function findKeyByRootPath(string $rootPath): ?string {
        $config = $this->getConfig();
        // 使用 foreach 迭代陣列的每個項目
        foreach ($config as $key => $value) {
            // 檢查當前項目中是否有 'root_path' 且與指定的 $rootPath 相同
            if (isset($value['root_path']) && $value['root_path'] === $rootPath) {
                // 如果匹配，返回當前項目的 key
                return $key;
            }
        }
        // 如果沒有匹配的，返回 null
        return null;
    }
}


