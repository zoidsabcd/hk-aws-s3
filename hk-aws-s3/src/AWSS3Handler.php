<?php

namespace Jeffho\HkAwsS3;

use Aws\Exception\AwsException;
use Aws\S3\S3Client;

class AWSS3Handler
{
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
     * @param string $bucket 存储桶名称
     * @param string|null $prefix 前缀
     * @return \stdClass 返回包含对象列表或错误信息的响应对象
     */
    public function listObjects(S3Client $client, string $bucket, ?string $prefix = null): \stdClass
    {
        $response = new \stdClass();
        try {
            $params = [
                'Bucket' => $bucket,
            ];
            if ($prefix !== null) {
                $params['Prefix'] = $prefix;
            }
            $result = $client->listObjectsV2($params);
            $response->status = 'success';
            $response->objects = $result['Contents'];
        } catch (AwsException $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        }
        return $response;
    }

    /**
     * 向存储桶中上传对象
     *
     * @param S3Client $client S3 客户端
     * @param string $bucket 存储桶名称
     * @param string $key 对象键
     * @param string $source 源文件路径
     * @return \stdClass 返回包含上传结果或错误信息的响应对象
     */
    public function putObject(S3Client $client, string $bucket, string $key, string $source): \stdClass
    {
        $response = new \stdClass();
        try {
            $result = $client->putObject([
                'Bucket' => $bucket,
                'Key' => $key,
                'SourceFile' => $source,
            ]);
            $response->status = 'success';
            $response->object = $result['ObjectURL'];
        } catch (AwsException $e) {
            $response->status = 'failure';
            $response->message = $e->getMessage();
        }
        return $response;
    }

    /**
     * 删除存储桶中的对象
     *
     * @param S3Client $client S3 客户端
     * @param string $bucket 存储桶名称
     * @param string $object 对象键
     * @return \stdClass 返回包含删除结果或错误信息的响应对象
     */
    public function deleteObject(S3Client $client, string $bucket, string $object): \stdClass
    {
        $response = new \stdClass();
        try {
            $client->deleteObject([
                'Bucket' => $bucket,
                'Key' => $object,
            ]);
            $response->status = 'success';
        } catch (AwsException $e) {
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
}


