<?php

namespace AlibabaCloud\Credentials\Tests\Feature;

use AlibabaCloud\Credentials\Credential;
use AlibabaCloud\Credentials\Credentials;
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
            $this->expectExceptionMessageMatches('/Timeout was reached/');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/Timeout was reached/');
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
        $result = $credential->getCredential();
        $this->assertNotNull($result->getAccessKeyId());
        $this->assertNotNull($result->getAccessKeySecret());
        $this->assertEquals('ram_role_arn', $result->getType());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testRsaKeyPairCredential()
    {
        $this->expectException(RuntimeException::class);
        $reg = '/Error refreshing credentials from RsaKeyPair, statusCode: 404/';
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches($reg);
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp($reg);
        }
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
        $result = $credential->getCredential();
        $this->assertNotNull($result->getAccessKeyId());
        $this->assertNotNull($result->getAccessKeySecret());
        $this->assertEquals('rsa_key_pair', $result->getType());
    }

    /**
     * @throws GuzzleException
     * @throws ReflectionException
     */
    public function testOIDCRoleArnCredential()
    {
        Credentials::cancelMock();
        $credential = new Credential();

        $result = $credential->getCredential();
        $this->assertNotNull($result->getAccessKeyId());
        $this->assertNotNull($result->getAccessKeySecret());
        $this->assertNotNull($result->getSecurityToken());
        $this->assertEquals('default', $result->getType());

        $config = new Credential\Config([
            'type'            => 'oidc_role_arn',
            'roleSessionName' => 'github_php_oidc_role_session_name',
        ]);

        $credential = new Credential($config);

        $result = $credential->getCredential();
        $this->assertNotNull($result->getAccessKeyId());
        $this->assertNotNull($result->getAccessKeySecret());
        $this->assertNotNull($result->getSecurityToken());
        $this->assertEquals('oidc_role_arn', $result->getType());
    }

}
