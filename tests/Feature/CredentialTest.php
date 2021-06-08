<?php

namespace AlibabaCloud\Credentials\Tests\Feature;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Tests\Helper;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualRsaKeyPairCredential;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use ReflectionException;

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

        // Assert
        $this->assertEquals('foo', $credential->getRoleName());
        $this->assertEquals('ecs_ram_role', $credential->getType());
        $credential->getAccessKeySecret();
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     * @expectedException \RuntimeException
     * @expectedExceptionMessage You are not authorized to do this action. You should be authorized by RAM.
     */
    public function testRamRoleArnCredential()
    {
        Credentials::cancelMock();
        $config = new Credential\Config([
            'type'            => 'ram_role_arn',
            'accessKeyId'     => Helper::getEnvironment('ACCESS_KEY_ID'),
            'accessKeySecret' => Helper::getEnvironment('ACCESS_KEY_SECRET'),
            'roleArn'         => Helper::getEnvironment('ROLE_ARN'),
            'roleSessionName' => 'role_session_name',
            'policy'          => '',
        ]);

        $credential = new Credential($config);

        // Assert
        $this->assertEquals('access_key_id2', $credential->getAccessKeyId());
        $this->assertEquals('ram_role_arn', $credential->getType());
        $credential->getAccessKeySecret();
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Specified access key is not found.
     */
    public function testRsaKeyPairCredential()
    {
        Credentials::cancelMock();
        $publicKeyId    = Helper::getEnvironment('PUBLIC_KEY_ID');
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $config         = new Credential\Config([
            'type'           => 'rsa_key_pair',
            'publicKeyId'    => $publicKeyId,
            'privateKeyFile' => $privateKeyFile,
        ]);
        $credential     = new Credential($config);

        // Assert
        $this->assertEquals('access_key_id2', $credential->getAccessKeyId());
        $this->assertEquals('rsa_key_pair', $credential->getType());
        $credential->getAccessKeySecret();
    }
}
