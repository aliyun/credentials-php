<?php

namespace AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit;

use AlibabaCloud\Credentials\AccessKeyCredential;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;
use PHPUnit\Framework\TestCase;

/**
 * Class AccessKeyCredentialTest
 *
 * @package AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit
 */
class AccessKeyCredentialTest extends TestCase
{
    public function testConstruct()
    {
        // Setup
        $accessKeyId     = 'foo';
        $accessKeySecret = 'bar';

        // Test
        $credential = new AccessKeyCredential($accessKeyId, $accessKeySecret);

        // Assert
        $this->assertEquals($accessKeyId, $credential->getAccessKeyId());
        $this->assertEquals($accessKeySecret, $credential->getAccessKeySecret());
        $this->assertInstanceOf(ShaHmac1Signature::class, $credential->getSignature());
        $this->assertEquals("$accessKeyId#$accessKeySecret", (string)$credential);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_id cannot be empty
     */
    public function testAccessKeyIdEmpty()
    {
        // Setup
        $accessKeyId     = '';
        $accessKeySecret = 'bar';

        new AccessKeyCredential($accessKeyId, $accessKeySecret);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_id must be a string
     */
    public function testAccessKeyIdFormat()
    {
        // Setup
        $accessKeyId     = null;
        $accessKeySecret = 'bar';

        new AccessKeyCredential($accessKeyId, $accessKeySecret);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_secret cannot be empty
     */
    public function testAccessKeySecretEmpty()
    {
        // Setup
        $accessKeyId     = 'foo';
        $accessKeySecret = '';

        // Test
        new AccessKeyCredential($accessKeyId, $accessKeySecret);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage access_key_secret must be a string
     */
    public function testAccessKeySecretFormat()
    {
        // Setup
        $accessKeyId     = 'foo';
        $accessKeySecret = null;

        // Test
        new AccessKeyCredential($accessKeyId, $accessKeySecret);
    }
}
