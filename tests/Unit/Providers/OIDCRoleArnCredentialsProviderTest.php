<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Tests\Mock\VirtualFile;
use AlibabaCloud\Credentials\Providers\OIDCRoleArnCredentialsProvider;
use RuntimeException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * Class OIDCRoleArnCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class OIDCRoleArnCredentialsProviderTest extends TestCase
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
        $reflection = new ReflectionClass(OIDCRoleArnCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    public function testConstruct()
    {
        // Setup
        $params = [
            'roleArn' => 'test',
            'oidcProviderArn' => 'test',
            'roleSessionName' => 'default',
            'oidcTokenFilePath' => '/a/b',
            'durationSeconds' => 3600,
            'policy' => 'policy',
            'stsRegionId' => 'cn-beijing',
            'enableVpc' => true,
            'stsEndpoint' => 'sts.cn-zhangjiakou.aliyuncs.com'
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];
        putenv("ALIBABA_CLOUD_ROLE_ARN=roleArn");
        putenv("ALIBABA_CLOUD_OIDC_PROVIDER_ARN=providerArn");
        putenv("ALIBABA_CLOUD_OIDC_TOKEN_FILE=/b/c");
        putenv("ALIBABA_CLOUD_ROLE_SESSION_NAME=sessionName");
        putenv("ALIBABA_CLOUD_STS_REGION=cn-hangzhou");
        putenv("ALIBABA_CLOUD_VPC_ENDPOINT_ENABLED=true");

        $provider = new OIDCRoleArnCredentialsProvider($params, $config);
        self::assertEquals('oidc_role_arn', $provider->getProviderName());
        self::assertEquals('oidc_role_arn#roleArn#test#oidcProviderArn#test#roleSessionName#default', $provider->key());
        $stsEndpoint = $this->getPrivateField($provider, 'stsEndpoint');
        $policy = $this->getPrivateField($provider, 'policy');
        $oidcTokenFilePath = $this->getPrivateField($provider, 'oidcTokenFilePath');
        $durationSeconds = $this->getPrivateField($provider, 'durationSeconds');
        self::assertEquals('sts.cn-zhangjiakou.aliyuncs.com', $stsEndpoint);
        self::assertEquals('policy', $policy);
        self::assertEquals('/a/b', $oidcTokenFilePath);
        self::assertEquals(3600, $durationSeconds);

        $provider = new OIDCRoleArnCredentialsProvider([], $config);
        self::assertEquals('oidc_role_arn', $provider->getProviderName());
        self::assertEquals('oidc_role_arn#roleArn#roleArn#oidcProviderArn#providerArn#roleSessionName#sessionName', $provider->key());
        $stsEndpoint = $this->getPrivateField($provider, 'stsEndpoint');
        $policy = $this->getPrivateField($provider, 'policy');
        $oidcTokenFilePath = $this->getPrivateField($provider, 'oidcTokenFilePath');
        $durationSeconds = $this->getPrivateField($provider, 'durationSeconds');
        self::assertEquals('sts-vpc.cn-hangzhou.aliyuncs.com', $stsEndpoint);
        self::assertNull($policy);
        self::assertEquals('/b/c', $oidcTokenFilePath);
        self::assertEquals(3600, $durationSeconds);

        putenv("ALIBABA_CLOUD_ROLE_ARN=");
        putenv("ALIBABA_CLOUD_OIDC_PROVIDER_ARN=");
        putenv("ALIBABA_CLOUD_OIDC_TOKEN_FILE=");
        putenv("ALIBABA_CLOUD_ROLE_SESSION_NAME=");
        putenv("ALIBABA_CLOUD_STS_REGION=");
        putenv("ALIBABA_CLOUD_VPC_ENDPOINT_ENABLED=");
    }

    public function testConstructErrorRoleArn()
    {
        // Setup
        $params = [];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('roleArn cannot be empty');
        new OIDCRoleArnCredentialsProvider($params, $config);
    }

    public function testConstructErroOIDCProviderArn()
    {
        // Setup
        $params = [
            'roleArn' => 'oidc_role_arn',
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('oidcProviderArn cannot be empty');
        new OIDCRoleArnCredentialsProvider($params, $config);
    }

    public function testConstructErroOIDCTokenFilePath()
    {
        // Setup
        $params = [
            'roleArn' => 'oidc_role_arn',
            'oidcProviderArn' => 'oidc_provider_arn',
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('oidcTokenFilePath cannot be empty');
        new OIDCRoleArnCredentialsProvider($params, $config);
    }

    public function testConstructErrorDurationSeconds()
    {
        // Setup
        $params = [
            'roleArn' => 'test',
            'oidcProviderArn' => 'oidc_provider_arn',
            'oidcTokenFilePath' => 'oidc_token_file_path',
            'durationSeconds' => 800,
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Role session expiration should be in the range of 900s - max session duration');
        new OIDCRoleArnCredentialsProvider($params, $config);
    }

    public function testSts()
    {
        $vf = new VirtualFile("token");
        $url = $vf->url("token-file");
        $params = [
            'roleArn' => 'testSts',
            'oidcProviderArn' => 'oidc_provider_arn',
            'oidcTokenFilePath' => $url,
            'policy' => [],
            'externalId' => 'externalId',
        ];
        $provider = new OIDCRoleArnCredentialsProvider($params);

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
        self::assertEquals('oidc_role_arn', $credential->getProviderName());

        $credential = $provider->getCredentials();
        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals('token', $credential->getSecurityToken());
        self::assertEquals('oidc_role_arn', $credential->getProviderName());
    }

    public function testStsIncomplete()
    {
        // Setup
        $vf = new VirtualFile("token");
        $url = $vf->url("token-file");
        $params = [
            'roleArn' => 'test',
            'oidcProviderArn' => 'oidc_provider_arn',
            'oidcTokenFilePath' => $url,
        ];
        $provider = new OIDCRoleArnCredentialsProvider($params);

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
            $this->expectExceptionMessageMatches('/Error retrieving credentials from OIDC/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving credentials from OIDC/');
        }
        // Test
        $provider->getCredentials();
    }

    public function testSts500()
    {
        // Setup
        $vf = new VirtualFile("token");
        $url = $vf->url("token-file");
        $params = [
            'roleArn' => 'test',
            'oidcProviderArn' => 'oidc_provider_arn',
            'oidcTokenFilePath' => $url,
        ];
        $provider = new OIDCRoleArnCredentialsProvider($params);
        Credentials::mockResponse(500, [], 'Error');

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error refreshing credentials from OIDC, statusCode: 500, result: Error/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error refreshing credentials from OIDC, statusCode: 500, result: Error/');
        }
        // Test
        $provider->getCredentials();
    }
}
