<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\AccessKeyCredential;
use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * Class AccessKeyCredentialTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit
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
        $this->assertEquals('', $credential->getSecurityToken());
        $this->assertInstanceOf(ShaHmac1Signature::class, $credential->getSignature());
        $this->assertEquals("$accessKeyId#$accessKeySecret", (string)$credential);

        $credentialModel = $credential->getCredential();
        $this->assertEquals($accessKeyId, $credentialModel->getAccessKeyId());
        $this->assertEquals($accessKeySecret, $credentialModel->getAccessKeySecret());
        $this->assertEquals('access_key', $credentialModel->getType());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage accessKeyId cannot be empty
     */
    public function testAccessKeyIdEmpty()
    {
        // Setup
        $accessKeyId     = '';
        $accessKeySecret = 'bar';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeyId cannot be empty');

        new AccessKeyCredential($accessKeyId, $accessKeySecret);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage accessKeyId must be a string
     */
    public function testAccessKeyIdFormat()
    {
        // Setup
        $accessKeyId     = null;
        $accessKeySecret = 'bar';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeyId must be a string');

        new AccessKeyCredential($accessKeyId, $accessKeySecret);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage accessKeySecret cannot be empty
     */
    public function testAccessKeySecretEmpty()
    {
        // Setup
        $accessKeyId     = 'foo';
        $accessKeySecret = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeySecret cannot be empty');

        // Test
        new AccessKeyCredential($accessKeyId, $accessKeySecret);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage accessKeySecret must be a string
     */
    public function testAccessKeySecretFormat()
    {
        // Setup
        $accessKeyId     = 'foo';
        $accessKeySecret = null;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('accessKeySecret must be a string');

        // Test
        new AccessKeyCredential($accessKeyId, $accessKeySecret);
    }
}
