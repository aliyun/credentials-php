<?php

namespace AlibabaCloud\Credentials\Tests\Feature;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Credentials;
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
        $credential = new Credential([
                                         'type'              => 'access_key',
                                         'access_key_id'     => 'foo',
                                         'access_key_secret' => 'bar',
                                     ]);

        // Assert
        $this->assertEquals('foo', $credential->getAccessKeyId());
        $this->assertEquals('bar', $credential->getAccessKeySecret());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     * @expectedException \GuzzleHttp\Exception\ConnectException
     * @expectedExceptionMessageRegExp /timed/
     */
    public function testEcsRamRoleCredential()
    {
        $credential = new Credential([
                                         'type'      => 'ecs_ram_role',
                                         'role_name' => 'foo',
                                     ]);

        // Assert
        $this->assertEquals('foo', $credential->getRoleName());
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
        $credential = new Credential([
                                         'type'              => 'ram_role_arn',
                                         'access_key_id'     => getenv('ACCESS_KEY_ID'),
                                         'access_key_secret' => getenv('ACCESS_KEY_SECRET'),
                                         'role_arn'          => getenv('ROLE_ARN'),
                                         'role_session_name' => 'role_session_name',
                                         'policy'            => '',
                                     ]);

        // Assert
        $this->assertEquals('access_key_id2', $credential->getAccessKeyId());
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
        $publicKeyId    = getenv('PUBLIC_KEY_ID');
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $credential     = new Credential([
                                             'type'             => 'rsa_key_pair',
                                             'public_key_id'    => $publicKeyId,
                                             'private_key_file' => $privateKeyFile,
                                         ]);

        // Assert
        $this->assertEquals('access_key_id2', $credential->getAccessKeyId());
        $credential->getAccessKeySecret();
    }
}
