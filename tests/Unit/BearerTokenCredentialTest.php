<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\BearerTokenCredential;
use AlibabaCloud\Credentials\Signature\BearerTokenSignature;
use InvalidArgumentException;
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
     * @expectedExceptionMessage Bearer Token cannot be empty
     */
    public static function testBearerTokenEmpty()
    {
        // Setup
        $bearerToken = '';

        // Test
        new BearerTokenCredential($bearerToken);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Bearer Token must be a string
     */
    public static function testBearerTokenFormat()
    {
        // Setup
        $bearerToken = null;

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
    }
}
