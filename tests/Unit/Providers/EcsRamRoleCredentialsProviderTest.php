<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\EcsRamRoleCredentialsProvider;
use AlibabaCloud\Credentials\Providers\SessionCredentialsProvider;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ReflectionClass;

/**
 * Class EcsRamRoleCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Providers
 */
class EcsRamRoleCredentialsProviderTest extends TestCase
{

    /**
     * @before
     */
    protected function initialize()
    {
        parent::setUp();
        Credentials::cancelMock();
        putenv('ALIBABA_CLOUD_ECS_METADATA_DISABLED');
        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE');
        putenv('ALIBABA_CLOUD_IMDSV1_DISABLED');
        putenv('ALIBABA_CLOUD_ECS_METADATA');
        $this->clearCredentialsCache();
    }

    private function clearCredentialsCache()
    {
        $reflection = new ReflectionClass(SessionCredentialsProvider::class);
        $property = $reflection->getProperty('credentialsCache');
        $property->setAccessible(true);
        $property->setValue(null, []);
    }

    private function getPrivateField($instance, $field)
    {
        $reflection = new ReflectionClass(EcsRamRoleCredentialsProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    /**
     * @throws Exception
     */
    private function invokeProtectedFunc($instance, $method)
    {
        $reflection = new ReflectionClass(EcsRamRoleCredentialsProvider::class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        $result = $method->invoke($instance);
        return $result;
    }

    public function testConstruct()
    {

        // Setup
        $params = [
            'roleName' => 'test',
            'disableIMDSv1' => true,
            'metadataTokenDuration' => 3600,
        ];
        $config = [
            'connectTimeout' => 10,
            'readTimeout' => 10,
        ];
        putenv("ALIBABA_CLOUD_ECS_METADATA=roleName");
        putenv("ALIBABA_CLOUD_IMDSV1_DISABLED=false");

        $provider = new EcsRamRoleCredentialsProvider($params, $config);

        $roleName = $this->getPrivateField($provider, 'roleName');
        $disableIMDSv1 = $this->getPrivateField($provider, 'disableIMDSv1');
        $metadataTokenDuration = $this->getPrivateField($provider, 'metadataTokenDuration');

        self::assertEquals('test', $roleName);
        self::assertEquals(true, $disableIMDSv1);
        self::assertEquals(21600, $metadataTokenDuration);
        self::assertEquals('test', $provider->getRoleName());
        self::assertEquals(true, $provider->isDisableIMDSv1());
        self::assertEquals('ecs_ram_role', $provider->getProviderName());

        putenv("ALIBABA_CLOUD_ECS_METADATA=");
        putenv("ALIBABA_CLOUD_IMDSV1_DISABLED=");
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage IMDS credentials is disabled
     */
    public function testEnvDisabled()
    {

        putenv("ALIBABA_CLOUD_ECS_METADATA_DISABLED=true");
        $provider = new EcsRamRoleCredentialsProvider([], []);

        try {
            $this->expectException(RuntimeException::class);
            if (method_exists($this, 'expectExceptionMessageMatches')) {
                $this->expectExceptionMessageMatches('/IMDS credentials is disabled/');
            } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
                $this->expectExceptionMessageRegExp('/IMDS credentials is disabled/');
            }
            $provider->getCredentials();
        } finally {
            putenv('ALIBABA_CLOUD_ECS_METADATA_DISABLED');
        }
    }

    public function testGetDisableECSIMDSv1()
    {
        // Setup
        $params = [
            'roleName' => 'test',
            'disableIMDSv1' => true,
        ];

        // Test

        $provider = new EcsRamRoleCredentialsProvider($params);

        self::assertEquals(true, $this->invokeProtectedFunc($provider, 'isDisableIMDSv1'));

        $params = [
            'roleName' => 'test',
        ];

        $provider = new EcsRamRoleCredentialsProvider($params);

        self::assertEquals(false, $this->invokeProtectedFunc($provider, 'isDisableIMDSv1'));

        putenv('ALIBABA_CLOUD_IMDSV1_DISABLED=true');

        $provider = new EcsRamRoleCredentialsProvider($params);

        self::assertEquals(true, $this->invokeProtectedFunc($provider, 'isDisableIMDSv1'));

        putenv('ALIBABA_CLOUD_IMDSV1_DISABLED=TRUE');

        $provider = new EcsRamRoleCredentialsProvider($params);

        self::assertEquals(true, $this->invokeProtectedFunc($provider, 'isDisableIMDSv1'));

        putenv('ALIBABA_CLOUD_IMDSV1_DISABLED=ok');

        $provider = new EcsRamRoleCredentialsProvider($params);

        self::assertEquals(false, $this->invokeProtectedFunc($provider, 'isDisableIMDSv1'));

        putenv('ALIBABA_CLOUD_IMDSV1_DISABLED=1');

        $provider = new EcsRamRoleCredentialsProvider($params);

        self::assertEquals(false, $this->invokeProtectedFunc($provider, 'isDisableIMDSv1'));

        putenv('ALIBABA_CLOUD_IMDSV1_DISABLED=false');

        $provider = new EcsRamRoleCredentialsProvider($params);

        self::assertEquals(false, $this->invokeProtectedFunc($provider, 'isDisableIMDSv1'));

        putenv('ALIBABA_CLOUD_IMDSV1_DISABLED=');

        $provider = new EcsRamRoleCredentialsProvider($params);

        self::assertEquals(false, $this->invokeProtectedFunc($provider, 'isDisableIMDSv1'));
    }

    public function testGetMetadataToken()
    {
        // Setup
        $params = [
            'roleName' => 'test',
            'disableIMDSv1' => true,
        ];

        // Test
        $provider = new EcsRamRoleCredentialsProvider($params);

        Credentials::mockResponse(200, [], 'Token');

        $token = $this->invokeProtectedFunc($provider, 'getMetadataToken');

        $histroy = Credentials::getHistroy();

        $request = end($histroy)['request'];
        $headers = $request->getHeaders();
        self::assertEquals('Token', $token);
        self::assertEquals('21600', $headers['X-aliyun-ecs-metadata-token-ttl-seconds'][0]);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage  Failed to get token from ECS Metadata Service. HttpCode= 404
     * @throws GuzzleException
     */
    public function testGetMetadataToken404()
    {
        // Setup
        $params = [
            'roleName' => 'test',
            'disableIMDSv1' => true,
        ];

        // Test
        $provider = new EcsRamRoleCredentialsProvider($params);

        Credentials::mockResponse(404, [], 'Error');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to get token from ECS Metadata Service. HttpCode= 404');

        $this->invokeProtectedFunc($provider, 'getMetadataToken');
    }

    public function testEnableV1404()
    {
        // Setup
        $params = [
            'roleName' => 'test',
            'disableIMDSv1' => false,
        ];

        // Test
        $provider = new EcsRamRoleCredentialsProvider($params);

        Credentials::mockResponse(404, [], 'Error');
        $token = $this->invokeProtectedFunc($provider, 'getMetadataToken');

        $histroy = Credentials::getHistroy();

        $request = end($histroy)['request'];
        self::assertEquals(null, $token);
    }

    public function testEnableIMDSv2DefaultTrue()
    {
        $provider = new EcsRamRoleCredentialsProvider(['roleName' => 'test']);
        self::assertEquals(true, $provider->isEnableIMDSv2());
        self::assertEquals(true, $this->getPrivateField($provider, 'enableIMDSv2'));
    }

    public function testEnableIMDSv2FalseFromParams()
    {
        $provider = new EcsRamRoleCredentialsProvider([
            'roleName' => 'test',
            'enableIMDSv2' => false,
        ]);
        self::assertEquals(false, $provider->isEnableIMDSv2());
    }

    public function testEnableIMDSv2FalseFromEnv()
    {
        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=false');
        $provider = new EcsRamRoleCredentialsProvider(['roleName' => 'test']);
        self::assertEquals(false, $provider->isEnableIMDSv2());

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=FALSE');
        $provider = new EcsRamRoleCredentialsProvider(['roleName' => 'test']);
        self::assertEquals(false, $provider->isEnableIMDSv2());

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=true');
        $provider = new EcsRamRoleCredentialsProvider(['roleName' => 'test']);
        self::assertEquals(true, $provider->isEnableIMDSv2());

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=ok');
        $provider = new EcsRamRoleCredentialsProvider(['roleName' => 'test']);
        self::assertEquals(true, $provider->isEnableIMDSv2());

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=');
        $provider = new EcsRamRoleCredentialsProvider(['roleName' => 'test']);
        self::assertEquals(true, $provider->isEnableIMDSv2());
        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE');
    }

    public function testEnableIMDSv2ParamsOverrideEnv()
    {
        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=false');
        $provider = new EcsRamRoleCredentialsProvider([
            'roleName' => 'test',
            'enableIMDSv2' => true,
        ]);
        self::assertEquals(true, $provider->isEnableIMDSv2());
        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE');
    }

    public function testEnableIMDSv2FalseSkipsTokenPut()
    {
        $result = [
            'Expiration' => '2049-10-01 00:00:00',
            'AccessKeyId' => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken' => 'token',
            'Code' => 'Success',
        ];
        $provider = new EcsRamRoleCredentialsProvider([
            'roleName' => 'test',
            'enableIMDSv2' => false,
        ]);

        Credentials::mockResponse(200, [], $result);
        $credential = $provider->getCredentials();

        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals(false, $provider->isEnableIMDSv2());

        $history = Credentials::getHistroy();
        self::assertEquals(1, count($history));
        $request = $history[0]['request'];
        self::assertEquals('GET', $request->getMethod());
        self::assertFalse($request->hasHeader('X-aliyun-ecs-metadata-token'));
    }

    public function testGetMetadataTokenSkippedWhenEnableIMDSv2False()
    {
        $provider = new EcsRamRoleCredentialsProvider([
            'roleName' => 'test',
            'enableIMDSv2' => false,
        ]);

        Credentials::mockResponse(200, [], 'Token');
        $token = $this->invokeProtectedFunc($provider, 'getMetadataToken');
        self::assertEquals(null, $token);
        self::assertEquals(0, count(Credentials::getHistroy()));
    }

    public function testFallbackToIMDSv1WhenCredentialGetFailsAfterToken()
    {
        $result = [
            'Expiration' => '2049-10-01 00:00:00',
            'AccessKeyId' => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken' => 'token',
            'Code' => 'Success',
        ];
        $provider = new EcsRamRoleCredentialsProvider([
            'roleName' => 'test',
            'disableIMDSv1' => false,
        ]);

        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(500, [], 'v2 failed');
        Credentials::mockResponse(200, [], $result);

        $credential = $provider->getCredentials();
        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('bar', $credential->getAccessKeySecret());
        self::assertEquals('token', $credential->getSecurityToken());

        $history = Credentials::getHistroy();
        self::assertEquals(3, count($history));
        self::assertEquals('PUT', $history[0]['request']->getMethod());
        self::assertTrue($history[1]['request']->hasHeader('X-aliyun-ecs-metadata-token'));
        self::assertFalse($history[2]['request']->hasHeader('X-aliyun-ecs-metadata-token'));
    }

    public function testNoFallbackWhenDisableIMDSv1True()
    {
        $provider = new EcsRamRoleCredentialsProvider([
            'roleName' => 'test',
            'disableIMDSv1' => true,
        ]);

        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(500, [], 'v2 failed');

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error refreshing credentials from IMDS, statusCode: 500/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error refreshing credentials from IMDS, statusCode: 500/');
        }

        $provider->getCredentials();
    }

    public function testFallbackToIMDSv1WhenRoleNameGetFailsAfterToken()
    {
        $result = [
            'Expiration' => '2049-10-01 00:00:00',
            'AccessKeyId' => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken' => 'token',
            'Code' => 'Success',
        ];
        $provider = new EcsRamRoleCredentialsProvider([
            'disableIMDSv1' => false,
        ]);

        // getRoleNameFromMeta: token ok, GET with token fails, retry without token ok
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(404, [], 'not found');
        Credentials::mockResponse(200, [], 'fallback-role');
        // refreshCredentials: token + credentials
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], $result);

        $credential = $provider->getCredentials();
        self::assertEquals('foo', $credential->getAccessKeyId());
        self::assertEquals('fallback-role', $provider->getRoleName());

        $history = Credentials::getHistroy();
        self::assertEquals(5, count($history));
        self::assertFalse($history[2]['request']->hasHeader('X-aliyun-ecs-metadata-token'));
    }
}
