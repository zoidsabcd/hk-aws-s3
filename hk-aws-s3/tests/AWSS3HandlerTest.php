<?php

use PHPUnit\Framework\TestCase;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Jeffho\HkAwsS3\AWSS3Handler;

class AWSS3HandlerTest extends TestCase
{
    private $s3Client;
    private $s3Handler;

    protected function setUp(): void
    {
        $this->s3Client = $this->createMock(S3Client::class);
        $this->s3Handler = new AWSS3Handler();
    }

    public function testSetClient()
    {
        $region = 'us-west-2';
        $key = 'fake-key';
        $secret = 'fake-secret';

        $client = $this->s3Handler->setClient($region, $key, $secret);

        $this->assertInstanceOf(S3Client::class, $client);
    }

    public function testListObjectsSuccess()
    {
        $bucket = 'my-bucket';
        $prefix = 'my-prefix';

        $this->s3Client->method('listObjects')->willReturn([
            'Contents' => [
                ['Key' => 'object1'],
                ['Key' => 'object2']
            ]
        ]);

        $response = $this->s3Handler->listObjects($this->s3Client, $bucket, $prefix);

        $this->assertEquals('success', $response->status);
        $this->assertCount(2, $response->objects);
    }

    public function testListObjectsFailure()
    {
        $bucket = 'my-bucket';
        $prefix = 'my-prefix';

        $this->s3Client->method('listObjects')->will($this->throwException(new AwsException('Error', null)));

        $response = $this->s3Handler->listObjects($this->s3Client, $bucket, $prefix);

        $this->assertEquals('failure', $response->status);
        $this->assertNotEmpty($response->message);
    }

    public function testPutObjectSuccess()
    {
        $bucket = 'my-bucket';
        $key = 'my-key';
        $source = '/path/to/source/file';

        $this->s3Client->method('putObject')->willReturn([
            'ObjectURL' => 'http://example.com/my-key'
        ]);

        $response = $this->s3Handler->putObject($this->s3Client, $bucket, $key, $source);

        $this->assertEquals('success', $response->status);
        $this->assertEquals('http://example.com/my-key', $response->object);
    }

    public function testPutObjectFailure()
    {
        $bucket = 'my-bucket';
        $key = 'my-key';
        $source = '/path/to/source/file';

        $this->s3Client->method('putObject')->will($this->throwException(new AwsException('Error', null)));

        $response = $this->s3Handler->putObject($this->s3Client, $bucket, $key, $source);

        $this->assertEquals('failure', $response->status);
        $this->assertNotEmpty($response->message);
    }

    public function testDeleteObjectSuccess()
    {
        $bucket = 'my-bucket';
        $object = 'my-object';

        $response = $this->s3Handler->deleteObject($this->s3Client, $bucket, $object);

        $this->assertEquals('success', $response->status);
    }

    public function testDeleteObjectFailure()
    {
        $bucket = 'my-bucket';
        $object = 'my-object';

        $this->s3Client->method('deleteObject')->will($this->throwException(new AwsException('Error', null)));

        $response = $this->s3Handler->deleteObject($this->s3Client, $bucket, $object);

        $this->assertEquals('failure', $response->status);
        $this->assertNotEmpty($response->message);
    }

    public function testGetConfig()
    {
        $config = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];

        $filePath = realpath(__DIR__ . '/../config/hk-aws-s3.php');

        file_put_contents($filePath, '<?php return ' . var_export($config, true) . ';');

        $result = $this->s3Handler->getConfig();

        $this->assertEquals($config, $result);
    }

    public function testGetConfigKey()
    {
        $config = [
            'key1' => 'value1',
            'key2' => 'value2'
        ];

        $filePath = realpath(__DIR__ . '/../config/hk-aws-s3.php');

        file_put_contents($filePath, '<?php return ' . var_export($config, true) . ';');

        $result = $this->s3Handler->getConfig('key1');

        $this->assertEquals('value1', $result);
    }
}

