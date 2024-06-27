<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\EcsRamRoleCredential;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use ReflectionClass;

class EcsRamRoleCredentialTest extends TestCase
{
    /**
     * @var EcsRamRoleCredential
     */
    protected $credential;

    /**
     * @before
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function initialize()
    {
        parent::setUp();
        $this->credential = new EcsRamRoleCredential('EcsRamRoleTest');
        Credentials::cancelMock();
    }

    /**
     * @throws GuzzleException
     */
    public function testConstruct()
    {
        // Setup
        $roleName = 'role_arn';
        $expected = "roleName#$roleName";

        // Test
        $credential = new EcsRamRoleCredential($roleName);

        // Assert
        $this->assertEquals($roleName, $credential->getRoleName());
        $this->assertInstanceOf(ShaHmac1Signature::class, $credential->getSignature());
        $this->assertEquals($expected, (string)$credential);
    }

    private function getPrivateField($instance, $field) {
        $reflection = new ReflectionClass(EcsRamRoleCredential::class);
        $privateProperty = $reflection->getProperty($field);
        $privateProperty->setAccessible(true);
        return $privateProperty->getValue($instance);
    }

    /**
     * @throws GuzzleException
     */
    public function testConstructWithIMDSv2()
    {
        // Setup
        $roleName = 'role_arn';
        $disabl1e_imdsv1 = true;
        $metadataTokenDuration = 3600;
        $credential = new EcsRamRoleCredential($roleName, $disabl1e_imdsv1, $metadataTokenDuration);

        self::assertEquals(true, $this->getPrivateField($credential, 'disableIMDSv1'));
        self::assertEquals(3600, $this->getPrivateField($credential, 'metadataTokenDuration'));

        $credential = new EcsRamRoleCredential($roleName);

        self::assertEquals(false, $this->getPrivateField($credential, 'disableIMDSv1'));
        self::assertEquals(21600, $this->getPrivateField($credential, 'metadataTokenDuration'));
    }

    /**
     * @throws GuzzleException
     */
    public function testDefault()
    {
        $this->credential = new EcsRamRoleCredential();
        $result           = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
        ];
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], 'RoleName');
        Credentials::mockResponse(200, [], $result);

        self::assertEquals('foo', $this->credential->getAccessKeyId());
        self::assertEquals('bar', $this->credential->getAccessKeySecret());
        self::assertEquals('token', $this->credential->getSecurityToken());
        self::assertEquals(strtotime('2049-10-01 00:00:00'), $this->credential->getExpiration());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp  /The role name was not found in the instance/
     * @throws GuzzleException
     */
    public function testDefault404()
    {
        $this->credential = new EcsRamRoleCredential();

        Credentials::mockResponse(404, [], 'RoleName');

        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/The role name was not found in the instance/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/The role name was not found in the instance/');
        }
        
        self::assertEquals('foo', $this->credential->getAccessKeyId());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp  /Error retrieving credentials from result: RoleName/
     * @throws GuzzleException
     */
    public function testDefault500()
    {
        $this->credential = new EcsRamRoleCredential();

        Credentials::mockResponse(500, [], 'RoleName');

        $this->expectException(RuntimeException::class);
        
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error retrieving credentials from result: RoleName/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving credentials from result: RoleName/');
        }
        self::assertEquals('foo', $this->credential->getAccessKeyId());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessageRegExp  /Error retrieving credentials from result is empty/
     * @throws GuzzleException
     */
    public function testDefaultEmpty()
    {
        $this->credential = new EcsRamRoleCredential();

        Credentials::mockResponse(200, [], '');
        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error retrieving credentials from result is empty/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving credentials from result is empty/');
        }
        
        self::assertEquals('foo', $this->credential->getAccessKeyId());
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @throws GuzzleException
     */
    public function testSts()
    {
        $result = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
        ];
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], $result);

        self::assertEquals('foo', $this->credential->getAccessKeyId());
        self::assertEquals('bar', $this->credential->getAccessKeySecret());
        self::assertEquals('token', $this->credential->getSecurityToken());
        self::assertEquals(strtotime('2049-10-01 00:00:00'), $this->credential->getExpiration());
    }

    /**
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Result contains no credentials
     */
    public function testStsIncomplete()
    {
        $result     = [
            'Expiration'  => '2049-10-01 00:00:00',
            'AccessKeyId' => 'foo',
        ];
        $credential = new EcsRamRoleCredential('EcsRamRoleTest2');
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], $result);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Result contains no credentials');
        // Test
        self::assertEquals('foo', $credential->getAccessKeyId());
    }

    /**
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The role was not found in the instance
     */
    public function testSts404()
    {
        $result     = [
            'Expiration'  => '2049-10-01 00:00:00',
            'AccessKeyId' => 'foo',
        ];
        $credential = new EcsRamRoleCredential('EcsRamRoleTest3');
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(404, [], $result);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The role was not found in the instance');
        // Test
        self::assertEquals('foo', $credential->getAccessKeyId());
    }

    /**
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /Error retrieving credentials from result/
     */
    public function testSts500()
    {
        $result = [
            'Expiration'  => '2049-10-01 00:00:00',
            'AccessKeyId' => 'foo',
        ];

        $credential = new EcsRamRoleCredential('EcsRamRoleTest3');
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(500, [], $result);

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error retrieving credentials from result/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving credentials from result/');
        }
        
        // Test
        self::assertEquals('foo', $credential->getAccessKeyId());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage role_name cannot be empty
     */
    public function testRoleNameEmpty()
    {
        // Setup
        $roleName = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('role_name cannot be empty');
        // Test
        new EcsRamRoleCredential($roleName);
    }

    /**
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @expectedException \RuntimeException
     * @expectedExceptionMessageRegExp /timed/
     */
    public function testStsWithoutMock()
    {
        Credentials::cancelMock();

        $credential = new EcsRamRoleCredential('EcsRamRoleTest4');

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/timed/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/timed/');
        }
        // Test
        self::assertEquals('foo', $credential->getAccessKeyId());
    }
}
