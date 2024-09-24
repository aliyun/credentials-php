<?php

namespace AlibabaCloud\Credentials\Tests\Feature;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Request\Request as Requests;
use AlibabaCloud\Credentials\Utils\Helper;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualRsaKeyPairCredential;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;

/**
 * Class CredentialTest
 *
 * @package AlibabaCloud\Credentials\Tests\Feature
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

        $result = $credential->getCredential();
        $this->assertEquals('foo', $result->getAccessKeyId());
        $this->assertEquals('bar', $result->getAccessKeySecret());
        $this->assertEquals('access_key', $result->getType());
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
            'type'     => 'ecs_ram_role',
            'roleName' => 'foo',
        ]);
        $credential = new Credential($config);

        $this->expectException(\GuzzleHttp\Exception\ConnectException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/Timeout/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Timeout/');
        }

        // Assert
        $this->assertEquals('foo', $credential->getRoleName());
        $this->assertEquals('ecs_ram_role', $credential->getType());
        $credential->getCredential();
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testRamRoleArnCredential()
    {
        Requests::cancelMock();
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
        $result = $credential->getCredential();
        $this->assertTrue(null !== $result->getAccessKeyId());
        $this->assertTrue(null !== $result->getAccessKeySecret());
        $this->assertEquals('ram_role_arn', $result->getType());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testRsaKeyPairCredential()
    {
        $this->expectException(RuntimeException::class);
        $reg = '/Specified access key is not found or invalid./';
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches($reg);
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp($reg);
        }
        Requests::cancelMock();
        $publicKeyId    = Helper::envNotEmpty('PUBLIC_KEY_ID');
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $config         = new Credential\Config([
            'type'           => 'rsa_key_pair',
            'publicKeyId'    => $publicKeyId,
            'privateKeyFile' => $privateKeyFile,
        ]);
        $credential     = new Credential($config);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Specified access key type is not match with signature type.');
        // Assert
        $result = $credential->getCredential();
        $this->assertTrue(null !== $result->getAccessKeyId());
        $this->assertTrue(null !== $result->getAccessKeySecret());
        $this->assertEquals('rsa_key_pair', $result->getType());
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
        $result = $credential->getCredential();
        $this->assertEquals('foo', $result->getAccessKeyId());
        $this->assertEquals('bar', $result->getAccessKeySecret());
        $this->assertEquals('token', $result->getSecurityToken());
        $this->assertEquals('sts', $result->getType());
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
        $result = $credential->getCredential();
        $this->assertEquals('token', $result->getBearerToken());
        $this->assertEquals('bearer', $result->getType());
    }
}
