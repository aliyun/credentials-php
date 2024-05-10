<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\EcsRamRoleCredential;
use AlibabaCloud\Credentials\Providers\EcsRamRoleProvider;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ReflectionClass;

class EcsRamRoleProviderTest extends TestCase
{

    public function setUp()
    {
        parent::setUp();
        Credentials::cancelMock();
    }
    /**
     * @throws GuzzleException
     */
    public function testConstruct()
    {

        $roleName = 'test';
        // Setup
        $config = [
            'enableIMDSv2' => true,
            'metadataTokenDuration' => 3600,
        ];

        // Test
        $credential = new EcsRamRoleCredential($roleName);

        $sessionCredential = new EcsRamRoleProvider($credential, $config);

        $sessionConfig = $this->getPrivateField($sessionCredential, 'config');

        self::assertEquals(true, $sessionConfig['enableIMDSv2']);
        self::assertEquals(3600, $sessionConfig['metadataTokenDuration']);
    }


    /**
     * @throws Exception
     */
    private function invokeProtectedFunc($instance, $method) {
        $reflection = new ReflectionClass(EcsRamRoleProvider::class);
        $method = $reflection->getMethod($method);
        $method->setAccessible(true);

        $result = $method->invoke($instance);
        return $result;
    }

    /**
     * @throws GuzzleException
     */
    public function testGetEnableECSIMDSv2()
    {
        // Setup
        $roleName = 'test';
        $config = [
            'enableIMDSv2' => true,
            'metadataTokenDuration' => 3600,
        ];

        // Test
        $credential = new EcsRamRoleCredential($roleName);

        $sessionCredential = new EcsRamRoleProvider($credential, $config);

        self::assertEquals(true, $this->invokeProtectedFunc($sessionCredential, 'getEnableECSIMDSv2'));

        $config = [
            'metadataTokenDuration' => 3600,
        ];

        $sessionCredential = new EcsRamRoleProvider($credential, $config);

        self::assertEquals(false, $this->invokeProtectedFunc($sessionCredential, 'getEnableECSIMDSv2'));

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=true');

        self::assertEquals(true, $this->invokeProtectedFunc($sessionCredential, 'getEnableECSIMDSv2'));

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=TRUE');

        self::assertEquals(true, $this->invokeProtectedFunc($sessionCredential, 'getEnableECSIMDSv2'));

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=ok');

        self::assertEquals(false, $this->invokeProtectedFunc($sessionCredential, 'getEnableECSIMDSv2'));

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=1');

        self::assertEquals(false, $this->invokeProtectedFunc($sessionCredential, 'getEnableECSIMDSv2'));

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=false');

        self::assertEquals(false, $this->invokeProtectedFunc($sessionCredential, 'getEnableECSIMDSv2'));

        putenv('ALIBABA_CLOUD_ECS_IMDSV2_ENABLE=');

        self::assertEquals(false, $this->invokeProtectedFunc($sessionCredential, 'getEnableECSIMDSv2'));
    }

    private function getPrivateField($instance, $field) {
        $reflection = new ReflectionClass(EcsRamRoleProvider::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    /**
     * @throws GuzzleException
     */
    public function testRefreshMetadataTokenDefault()
    {
        // Setup
        $roleName = 'test';
        $config = [
            'enableIMDSv2' => true,
            'metadataTokenDuration' => 3600,
        ];

        // Test
        $credential = new EcsRamRoleCredential($roleName);

        $sessionCredential = new EcsRamRoleProvider($credential, $config);

        Credentials::mockResponse(200, [], 'Token');

        $this->invokeProtectedFunc($sessionCredential, 'refreshMetadataToken');
        
        $histroy = Credentials::getHistroy();

        $request = end($histroy)['request'];
        $headers = $request->getHeaders();
        self::assertEquals('Token', $this->getPrivateField($sessionCredential, 'metadataToken'));
        self::assertEquals('3600', $headers['X-aliyun-ecs-metadata-token-ttl-seconds'][0]);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage  Failed to get token from ECS Metadata Service. HttpCode= 404
     * @throws GuzzleException
     */
    public function testDefault404()
    {
        // Setup
        $roleName = 'test';
        $config = [
            'enableIMDSv2' => true,
            'metadataTokenDuration' => 3600,
        ];

        // Test
        $credential = new EcsRamRoleCredential($roleName);

        $sessionCredential = new EcsRamRoleProvider($credential, $config);

        Credentials::mockResponse(404, [], 'Error');
        $this->invokeProtectedFunc($sessionCredential, 'refreshMetadataToken');
        
    }

    /**
     * @throws GuzzleException
     */
    public function testNeedToRefresh()
    {
        // Setup
        $roleName = 'test';
        $config = [
            'enableIMDSv2' => true,
            'metadataTokenDuration' => 5,
        ];

        // Test
        $credential = new EcsRamRoleCredential($roleName);

        $sessionCredential = new EcsRamRoleProvider($credential, $config);

        

        self::assertEquals(true, $this->invokeProtectedFunc($sessionCredential, 'needToRefresh'));

        Credentials::mockResponse(200, [], 'Token');

        $this->invokeProtectedFunc($sessionCredential, 'refreshMetadataToken');

        self::assertEquals(false, $this->invokeProtectedFunc($sessionCredential, 'needToRefresh'));

        sleep(3);

        self::assertEquals(false, $this->invokeProtectedFunc($sessionCredential, 'needToRefresh'));

        sleep(3);

        self::assertEquals(true, $this->invokeProtectedFunc($sessionCredential, 'needToRefresh'));
    }
}
