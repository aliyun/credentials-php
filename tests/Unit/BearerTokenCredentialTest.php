<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\BearerTokenCredential;
use AlibabaCloud\Credentials\Signature\BearerTokenSignature;
use InvalidArgumentException;
use Exception;
use PHPUnit\Framework\TestCase;

/**
 * Class BearerTokenCredentialTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit
 */
class BearerTokenCredentialTest extends TestCase
{

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage bearerToken cannot be empty
     */
    public function testBearerTokenEmpty()
    {
        // Setup
        $bearerToken = '';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bearerToken cannot be empty');
        // Test
        new BearerTokenCredential($bearerToken);
        
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage bearerToken must be a string
     */
    public function testBearerTokenFormat()
    {
        // Setup
        $bearerToken = null;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('bearerToken must be a string');

        // Test
        new BearerTokenCredential($bearerToken);
    }

    public function testConstruct()
    {
        // Setup
        $bearerToken = 'BEARER_TOKEN';
        $expected    = 'bearerToken#BEARER_TOKEN';

        // Test
        $credential = new BearerTokenCredential($bearerToken);

        // Assert
        $this->assertEquals($bearerToken, $credential->getBearerToken());
        $this->assertEquals($expected, (string)$credential);
        $this->assertInstanceOf(BearerTokenSignature::class, $credential->getSignature());

        $credentialModel = $credential->getCredential();
        $this->assertEquals($bearerToken, $credentialModel->getBearerToken());
        $this->assertEquals('bearer', $credentialModel->getType());
    }
}
