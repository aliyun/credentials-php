<?php

namespace AlibabaCloud\Credentials\Tests\LowerthanVersion7_2\Feature;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Helper;
use AlibabaCloud\Credentials\Tests\LowerthanVersion7_2\Unit\Ini\VirtualRsaKeyPairCredential;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use ReflectionException;

/**
 * Class CredentialTest
 *
 * @package AlibabaCloud\Credentials\Tests\LowerthanVersion7_2\Feature
 */
class CredentialTest extends TestCase
{

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testAccessKey()
    {
        $config     = new Credential\Config([
            'type'            => 'access_key',
            'accessKeyId'     => 'foo',
            'accessKeySecret' => 'bar',
        ]);
        $credential = new Credential($config);

        // Assert
        $this->assertEquals('foo', $credential->getAccessKeyId());
        $this->assertEquals('bar', $credential->getAccessKeySecret());
        $this->assertEquals('access_key', $credential->getType());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     * @expectedException \GuzzleHttp\Exception\ConnectException
     * @expectedExceptionMessageRegExp /timed/
     */
    public function testEcsRamRoleCredential()
    {
        $config     = new Credential\Config([
            'type'         => 'ecs_ram_role',
            'roleName'     => 'foo',
            'enableIMDSv2' => true,
        ]);
        $credential = new Credential($config);

        // Assert
        $this->assertEquals('foo', $credential->getRoleName());
        $this->assertEquals('ecs_ram_role', $credential->getType());
        $this->assertTrue($credential->isEnableIMDSv2());
        $credential->getAccessKeySecret();
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testRamRoleArnCredential()
    {
        Credentials::cancelMock();
        $config = new Credential\Config([
            'type'            => 'ram_role_arn',
            'accessKeyId'     => Helper::envNotEmpty('ACCESS_KEY_ID'),
            'accessKeySecret' => Helper::envNotEmpty('ACCESS_KEY_SECRET'),
            'roleArn'         => Helper::envNotEmpty('ROLE_ARN'),
            'roleSessionName' => 'role_session_name',
            'policy'          => '',
        ]);

        $credential = new Credential($config);

        // Assert
        $this->assertTrue(null !== $credential->getAccessKeyId());
        $this->assertTrue(null !== $credential->getAccessKeySecret());
        $this->assertEquals('ram_role_arn', $credential->getType());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Specified access key type is not match with signature type.
     */
    public function testRsaKeyPairCredential()
    {
        Credentials::cancelMock();
        $publicKeyId    = Helper::envNotEmpty('PUBLIC_KEY_ID');
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $config         = new Credential\Config([
            'type'           => 'rsa_key_pair',
            'publicKeyId'    => $publicKeyId,
            'privateKeyFile' => $privateKeyFile,
        ]);
        $credential     = new Credential($config);

        // Assert
        $this->assertTrue(null !== $credential->getAccessKeyId());
        $this->assertTrue(null !== $credential->getAccessKeySecret());
        $this->assertEquals('rsa_key_pair', $credential->getType());
        $credential->getAccessKeySecret();
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testSTS()
    {
        $config     = new Credential\Config([
            'type'            => 'sts',
            'accessKeyId'     => 'foo',
            'accessKeySecret' => 'bar',
            'securityToken'   => 'token',
        ]);
        $credential = new Credential($config);

        // Assert
        $this->assertEquals('foo', $credential->getAccessKeyId());
        $this->assertEquals('bar', $credential->getAccessKeySecret());
        $this->assertEquals('token', $credential->getSecurityToken());
        $this->assertEquals('sts', $credential->getType());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testBearerToken()
    {
        $config     = new Credential\Config([
            'type'            => 'bearer',
            'bearerToken'     => 'token',
        ]);
        $credential = new Credential($config);

        // Assert
        $this->assertEquals('token', $credential->getBearerToken());
        $this->assertEquals('bearer', $credential->getType());
    }
}
