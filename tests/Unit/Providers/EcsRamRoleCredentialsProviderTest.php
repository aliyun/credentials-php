<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Providers;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\EcsRamRoleCredentialsProvider;
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
    private function invokeProtectedFunc($instance, $method) {
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

}
