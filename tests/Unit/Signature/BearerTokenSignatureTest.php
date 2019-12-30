<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Signature;

use AlibabaCloud\Credentials\Signature\BearerTokenSignature;
use PHPUnit\Framework\TestCase;

/**
 * Class BearerTokenSignatureTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Signature
 */
class BearerTokenSignatureTest extends TestCase
{
    public function testBearerTokenSignature()
    {
        // Setup
        $string          = 'this is a BearToken test.';
        $accessKeySecret = 'accessKeySecret';
        $expected        = null;

        // Test
        $signature = new BearerTokenSignature();

        // Assert
        static::assertInstanceOf(BearerTokenSignature::class, $signature);
        static::assertEquals($expected, $signature->sign($string, $accessKeySecret));
        static::assertEquals('', $signature->getMethod());
        static::assertEquals('1.0', $signature->getVersion());
        static::assertEquals('BEARERTOKEN', $signature->getType());
    }
}
