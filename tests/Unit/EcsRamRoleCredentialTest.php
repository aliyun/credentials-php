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
        $this->assertEquals(false, $credential->isDisableIMDSv1());
        $this->assertEquals($expected, (string)$credential);

        Credentials::mockResponse(200, [], 'RoleName');
        $this->credential = new EcsRamRoleCredential();
        self::assertEquals('RoleName', $this->credential->getRoleName());
    }

    private function getPrivateField($instance, $field)
    {
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
        $result           = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
            'Code'            => 'Success',
        ];

        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], 'RoleName');
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], $result);
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], 'RoleName');
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], 'RoleName');
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], 'RoleName');

        $this->credential = new EcsRamRoleCredential();
        self::assertEquals('foo', $this->credential->getAccessKeyId());
        self::assertEquals('bar', $this->credential->getAccessKeySecret());
        self::assertEquals('token', $this->credential->getSecurityToken());
        self::assertEquals(strtotime('2049-10-01 00:00:00'), $this->credential->getExpiration());

        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], 'RoleName');
        $credentialModel = $this->credential->getCredential();
        $this->assertEquals('foo', $credentialModel->getAccessKeyId());
        $this->assertEquals('bar', $credentialModel->getAccessKeySecret());
        self::assertEquals('token', $credentialModel->getSecurityToken());
        $this->assertEquals('ecs_ram_role', $credentialModel->getType());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessageRegExp  /The role name was not found in the instance/
     * @throws GuzzleException
     */
    public function testDefault404()
    {
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(404, [], 'RoleName');

        $this->credential = new EcsRamRoleCredential();

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
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(500, [], 'RoleName');
        $this->credential = new EcsRamRoleCredential();

        $this->expectException(RuntimeException::class);

        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error retrieving role name from result: RoleName/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving role name from result: RoleName/');
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

        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], 'RoleNameTest');
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], []);

        $this->credential = new EcsRamRoleCredential();

        $this->expectException(RuntimeException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Error retrieving credentials from IMDS result:\[\]/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error retrieving credentials from IMDS result:\[\]/');
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
            'Code'            => 'Success',
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
        $this->expectExceptionMessage('Error retrieving credentials from IMDS result:{"Expiration":"2049-10-01 00:00:00","AccessKeyId":"foo"}');
        // Test
        self::assertEquals('foo', $credential->getAccessKeyId());
    }

    /**
     * @throws Exception
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Result contains no credentials
     */
    public function testStsNoCode()
    {
        $result = [
            'Expiration'      => '2049-10-01 00:00:00',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
        ];
        $credential = new EcsRamRoleCredential('EcsRamRoleTest2');
        Credentials::mockResponse(200, [], 'Token');
        Credentials::mockResponse(200, [], $result);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error retrieving credentials from IMDS result, Code is not Success:{"Expiration":"2049-10-01 00:00:00","AccessKeyId":"foo","AccessKeySecret":"bar","SecurityToken":"token"}');
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
            $this->expectExceptionMessageMatches('/Error refreshing credentials from IMDS, statusCode: 500, result/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Error refreshing credentials from IMDS, statusCode: 500, result/');
        }

        // Test
        self::assertEquals('foo', $credential->getAccessKeyId());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage roleName cannot be empty
     */
    public function testRoleNameEmpty()
    {
        // Setup
        $roleName = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('roleName cannot be empty');
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
            $this->expectExceptionMessageMatches('/Connection timeout/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Connection timeout/');
        }
        // Test
        self::assertEquals('foo', $credential->getAccessKeyId());
    }

    public function testGetRoleNameFromMeta()
    {
        $provider = new EcsRamRoleCredential();

        Credentials::mockResponse(200, [], 'RoleName');

        $roleName = $provider->getRoleNameFromMeta();
        self::assertEquals('RoleName', $roleName);
    }

    public function testGetRoleNameFromMetaError()
    {
        $provider = new EcsRamRoleCredential();

        Credentials::mockResponse(200, [], '');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error retrieving credentials from result is empty');
        $provider->getRoleNameFromMeta();
    }

    public function testGetRoleNameFromMeta404()
    {
        $provider = new EcsRamRoleCredential();

        Credentials::mockResponse(404, [], 'Error');

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The role name was not found in the instance');

        $provider->getRoleNameFromMeta();

    }

    public function testRoleNameFromMetaError()
    {
        $provider = new EcsRamRoleCredential();

        Credentials::mockResponse(500, [], 'Error');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Error retrieving credentials from result: Error');

        $provider->getRoleNameFromMeta();
    }
}
