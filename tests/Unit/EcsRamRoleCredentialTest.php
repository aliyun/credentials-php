<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\EcsRamRoleCredential;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;

class EcsRamRoleCredentialTest extends TestCase
{
    /**
     * @var EcsRamRoleCredential
     */
    protected $credential;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    public function setUp()
    {
        parent::setUp();
        $this->credential = new EcsRamRoleCredential('EcsRamRoleTest');
        Credentials::cancelMock();
    }

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

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    public function testSts()
    {
        $result = [
            'Expiration'      => '2020-02-02 11:11:11',
            'AccessKeyId'     => 'foo',
            'AccessKeySecret' => 'bar',
            'SecurityToken'   => 'token',
        ];
        Credentials::mockResponse(200, [], $result);

        self::assertEquals('foo', $this->credential->getAccessKeyId());
        self::assertEquals('bar', $this->credential->getAccessKeySecret());
        self::assertEquals('token', $this->credential->getSecurityToken());
        self::assertEquals(strtotime('2020-02-02 11:11:11'), $this->credential->getExpiration());
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
            'Expiration'  => '2020-02-02 11:11:11',
            'AccessKeyId' => 'foo',
        ];
        $credential = new EcsRamRoleCredential('EcsRamRoleTest2');
        Credentials::mockResponse(200, [], $result);

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
            'Expiration'  => '2020-02-02 11:11:11',
            'AccessKeyId' => 'foo',
        ];
        $credential = new EcsRamRoleCredential('EcsRamRoleTest3');
        Credentials::mockResponse(404, [], $result);

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
            'Expiration'  => '2020-02-02 11:11:11',
            'AccessKeyId' => 'foo',
        ];

        $credential = new EcsRamRoleCredential('EcsRamRoleTest3');
        Credentials::mockResponse(500, [], $result);

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

        // Test
        self::assertEquals('foo', $credential->getAccessKeyId());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage role_name must be a string
     */
    public function testRoleNameFormat()
    {
        // Setup
        $roleName = null;

        // Test
        new EcsRamRoleCredential($roleName);
    }
}
