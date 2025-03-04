<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\RamRoleArnCredentialsProvider;
use AlibabaCloud\Credentials\Providers\StaticAKCredentialsProvider;
use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class RamRoleArnCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class RamRoleArnCredentialsProviderTest extends TestCase
{

    /**
     * @before
     */
    protected function initialize()
    {
        parent::setUp();
        Credentials::cancelMock();
    }

    private function getPrivateField($instance, $field)
    {
        $reflection = new ReflectionClass(RamRoleArnCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    public function testConstruct()
    {
        // Setup
        $params = [
            'accessKeyId' => 'foo',
            'accessKeySecret' => 'bar',
            'roleArn' => 'test',
            'roleSessionName' => 'default',
            'durationSeconds' => 3600,
            'policy' => 'policy',
            'externalId' => 'externalId',
            'stsRegionId' => 'cn-beijing',
            'enableVpc' => true,
            'stsEndpoint' => 'sts.cn-zhangjiakou.aliyuncs.com'
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];
        putenv("ALIBABA_CLOUD_ROLE_ARN=roleArn");
        putenv("ALIBABA_CLOUD_ROLE_SESSION_NAME=sessionName");
        putenv("ALIBABA_CLOUD_STS_REGION=cn-hangzhou");
        putenv("ALIBABA_CLOUD_VPC_ENDPOINT_ENABLED=true");

        $provider = new RamRoleArnCredentialsProvider($params, $config);

        self::assertEquals('foo', $provider->getOriginalAccessKeyId());
        self::assertEquals('bar', $provider->getOriginalAccessKeySecret());
        self::assertEquals('test', $provider->getRoleArn());
        self::assertEquals('default', $provider->getRoleSessionName());
        self::assertEquals('policy', $provider->getPolicy());
        self::assertEquals('ram_role_arn/static_ak', $provider->getProviderName());
        self::assertEquals('ram_role_arn#credential#foo#roleArn#test#roleSessionName#default', $provider->key());
        $stsEndpoint = $this->getPrivateField($provider, 'stsEndpoint');
        $externalId = $this->getPrivateField($provider, 'externalId');
        self::assertEquals('sts.cn-zhangjiakou.aliyuncs.com', $stsEndpoint);
        self::assertEquals('externalId', $externalId);

        $params = [
            'accessKeyId' => 'foo',
            'accessKeySecret' => 'bar',
            'securityToken' => 'token',
        ];
        $provider = new RamRoleArnCredentialsProvider($params, $config);
        self::assertEquals('foo', $provider->getOriginalAccessKeyId());
        self::assertEquals('bar', $provider->getOriginalAccessKeySecret());
        self::assertEquals('roleArn', $provider->getRoleArn());
        self::assertEquals('sessionName', $provider->getRoleSessionName());
        self::assertNull($provider->getPolicy());
        self::assertEquals('ram_role_arn/static_sts', $provider->getProviderName());
        self::assertEquals('ram_role_arn#credential#foo#roleArn#roleArn#roleSessionName#sessionName', $provider->key());
        $stsEndpoint = $this->getPrivateField($provider, 'stsEndpoint');
        $externalId = $this->getPrivateField($provider, 'externalId');
        self::assertEquals('sts-vpc.cn-hangzhou.aliyuncs.com', $stsEndpoint);
        self::assertNull($externalId);

        putenv("ALIBABA_CLOUD_ROLE_ARN=");
        putenv("ALIBABA_CLOUD_ROLE_SESSION_NAME=");
        putenv("ALIBABA_CLOUD_STS_REGION=");
        putenv("ALIBABA_CLOUD_VPC_ENDPOINT_ENABLED=");
    }

    public function testConstructErrorCredentials()
    {
        // Setup
        $params = [];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required credentials option for ram_role_arn');
        new RamRoleArnCredentialsProvider($params, $config);
    }

    public function testConstructErrorDurationSeconds()
    {
        // Setup
        $params = [
            'credentialsProvider' => new StaticAKCredentialsProvider([
                'accessKeyId' => 'foo',
                'accessKeySecret' => 'bar',
            ]),
            'roleArn' => 'test',
            'durationSeconds' => 800,
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role session expiration should be in the range of 900s - max session duration');
        new RamRoleArnCredentialsProvider($params, $config);
    }

    public function testSts()
    {
        $params = [
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'roleArn' => 'testSts',
            'policy' => [],
            'externalId' => 'externalId',
        ];
        $provider = new RamRoleArnCredentialsProvider($params);

        $result = '{
        "RequestId": "88FEA385-EF5D-4A8A-8C00-A07DAE3BFD44",
        "AssumedRoleUser": {
            "AssumedRoleId": "********************",
            "Arn": "********************"
        },
        "Credentials": {
            "AccessKeySecret": "bar",
            "AccessKeyId": "foo",
            "Expiration": "2049-10-25T03:56:19Z",
            "SecurityToken": "token"
        }
    }';
        Credentials::mockResponse(200, [], $result);
        $credential = $provider->getCredentials();

        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals('token', $credential->getSecurityToken());
        self::assertEquals('ram_role_arn/static_ak', $credential->getProviderName());

        $credential = $provider->getCredentials();
        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals('token', $credential->getSecurityToken());
        self::assertEquals('ram_role_arn/static_ak', $credential->getProviderName());
    }

    public function testStsIncomplete()
    {
        // Setup
        $params = [
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'roleArn' => 'test',
        ];
        $provider = new RamRoleArnCredentialsProvider($params);

        $result = '{
        "RequestId": "88FEA385-EF5D-4A8A-8C00-A07DAE3BFD44",
        "AssumedRoleUser": {
            "AssumedRoleId": "********************",
            "Arn": "********************"
        },
        "Credentials": {
            "AccessKeyId": "STS.**************",
            "Expiration": "2020-02-25T03:56:19Z",
            "SecurityToken": "**************"
        }
    }';
        Credentials::mockResponse(200, [], $result);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error retrieving credentials from RamRoleArn/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving credentials from RamRoleArn/');
        }
        // Test
        $provider->getCredentials();
    }

    public function testSts500()
    {
        // Setup
        $params = [
            'accessKeyId' => 'test',
            'accessKeySecret' => 'test',
            'roleArn' => 'test',
        ];
        $provider = new RamRoleArnCredentialsProvider($params);
        Credentials::mockResponse(500, [], 'Error');

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error refreshing credentials from RamRoleArn, statusCode: 500, result: Error/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error refreshing credentials from RamRoleArn, statusCode: 500, result: Error/');
        }
        // Test
        $provider->getCredentials();
    }

    public function testAccessKeyIdEmpty()
    {

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeyId cannot be empty');
        // Test
        $params = [
            'accessKeyId' => '',
            'accessKeySecret' => 'test',
            'roleArn' => 'test',
        ];
        new RamRoleArnCredentialsProvider($params);
    }

    public function testAccessKeyFormat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Missing required credentials option for ram_role_arn');
        // Test
        $params = [
            'accessKeyId' => 'test',
            'accessKeySecret' => null,
            'roleArn' => 'test',
        ];
        new RamRoleArnCredentialsProvider($params);
    }
}
