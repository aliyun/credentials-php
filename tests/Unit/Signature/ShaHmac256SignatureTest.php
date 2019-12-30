<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Signature;

use AlibabaCloud\Credentials\Signature\ShaHmac256Signature;
use PHPUnit\Framework\TestCase;

/**
 * Class ShaHmac256SignatureTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Signature
 */
class ShaHmac256SignatureTest extends TestCase
{
    public function testShaHmac256Signature()
    {
        // Setup
        $string          = 'this is a ShaHmac256 test.';
        $accessKeySecret = 'accessKeySecret';
        $expected        = 'v1Kg2HYGWRaLsRu2iXkAZu3R7vDh0txyYHs48HVxkeA=';

        // Test
        $signature = new ShaHmac256Signature();

        // Assert
        static::assertInstanceOf(ShaHmac256Signature::class, $signature);
        static::assertEquals('HMAC-SHA256', $signature->getMethod());
        static::assertEquals('1.0', $signature->getVersion());
        static::assertEquals('', $signature->getType());
        static::assertEquals(
            $expected,
            $signature->sign($string, $accessKeySecret)
        );
    }
}
