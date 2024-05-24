<?php

namespace AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit\Signature;

use AlibabaCloud\Credentials\Signature\ShaHmac1Signature;
use PHPUnit\Framework\TestCase;

/**
 * Class ShaHmac1SignatureTest
 *
 * @package AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit\Signature
 */
class ShaHmac1SignatureTest extends TestCase
{
    public function testShaHmac1Signature()
    {
        // Setup
        $string          = 'this is a ShaHmac1 test.';
        $accessKeySecret = 'accessKeySecret';
        $expected        = 'PEr0Vp78B4Fslzf54dzdXD4Qt3E=';

        // Test
        $signature = new ShaHmac1Signature();

        // Assert
        static::assertInstanceOf(ShaHmac1Signature::class, $signature);
        static::assertEquals('HMAC-SHA1', $signature->getMethod());
        static::assertEquals('1.0', $signature->getVersion());
        static::assertEquals('', $signature->getType());
        static::assertEquals($expected, $signature->sign($string, $accessKeySecret));
    }
}
