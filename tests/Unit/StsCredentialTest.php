<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use PHPUnit\Framework\TestCase;
use AlibabaCloud\Credentials\StsCredential;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;

class StsCredentialTest extends TestCase
{
    public function testConstruct()
    {
        // Setup
        $accessKeyId     = 'accessKeyId';
        $accessKeySecret = 'accessKeySecret';
        $securityToken   = 'securityToken';
        $expiration      = time();

        // Test
        $credential = new StsCredential($accessKeyId, $accessKeySecret, $expiration, $securityToken);

        // Assert
        $this->assertEquals($accessKeyId, $credential->getAccessKeyId());
        $this->assertEquals($accessKeySecret, $credential->getAccessKeySecret());
        $this->assertEquals($securityToken, $credential->getSecurityToken());
        $this->assertEquals($expiration, $credential->getExpiration());
        $this->assertInstanceOf(ShaHmac1Signature::class, $credential->getSignature());
        $this->assertEquals(
            "$accessKeyId#$accessKeySecret#$securityToken",
            (string)$credential
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_id cannot be empty
     */
    public function testAccessKeyIdEmpty()
    {
        // Setup
        $accessKeyId     = '';
        $accessKeySecret = 'accessKeySecret';
        $securityToken   = 'securityToken';

        new StsCredential($accessKeyId, $accessKeySecret, $securityToken);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_id must be a string
     */
    public function testAccessKeyIdFormat()
    {
        // Setup
        $accessKeyId     = null;
        $accessKeySecret = 'accessKeySecret';
        $securityToken   = 'securityToken';

        new StsCredential($accessKeyId, $accessKeySecret, $securityToken);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_secret cannot be empty
     */
    public function testAccessKeySecretEmpty()
    {
        // Setup
        $accessKeyId     = 'accessKeyId';
        $accessKeySecret = '';
        $securityToken   = 'securityToken';

        new StsCredential($accessKeyId, $accessKeySecret, $securityToken);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_secret must be a string
     */
    public function testAccessKeySecretFormat()
    {
        // Setup
        $accessKeyId     = 'accessKeyId';
        $accessKeySecret = null;
        $securityToken   = 'securityToken';

        new StsCredential($accessKeyId, $accessKeySecret, $securityToken);
    }
}
