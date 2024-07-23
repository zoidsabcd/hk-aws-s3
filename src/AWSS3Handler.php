<?php

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
            $result = $client->putObject([
                'Bucket' => $config['bucket'],
                'Key' => $config['root_path'] . $objectKey,
                'SourceFile' => $source,
            ]);
            $response->status = 'success';
            $response->object = $result['ObjectURL'];
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
            case 'store_log':
            case 'product':
            default:
                return $oldCdn . '/' . $source;
        }
    }
}


