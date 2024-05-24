<?php

namespace AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit\Signature;

use AlibabaCloud\Credentials\Signature\ShaHmac256WithRsaSignature;
use AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit\Ini\VirtualRsaKeyPairCredential;
use PHPUnit\Framework\TestCase;

/**
 * Class ShaHmac256WithRsaSignatureTest
 *
 * @package AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit\Signature
 */
class ShaHmac256WithRsaSignatureTest extends TestCase
{
    public function testShaHmac256Signature()
    {
        // Setup
        $string         = 'string';
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $expected       =
            'gjWgwRf/BjOYbjrPleU9qzNrZXNO+Z9aiwxmbBj1TPF2/PEOjBy5/YSk+GfL2GGg5pkupzrKiG+4FQ4r+EjeQcdByRDv1x1eBrQHwAbieKmjLc1++vJWQQpSKJykMl5dRzADUwsXYzvCCvVCIXjYZJNsrdt/0G+gaRVX7oelAX+d1MiTjRam7Ugzxcr1nELz2dc3DOyhXqCw8loNtsFVNcrDC5B/urx4eYdAFWRYVbORdTTgPdOF/gNJOWPqQgvFQsICJpScwIXP2OntCjYj8EBGNafBK3bCe4jxHwtxBA72PmuJ/ZHxUqSstwbcVk5S40PlRIhqtrfn6ajxYk41SQ==';

        // Test
        $signature = new ShaHmac256WithRsaSignature();

        // Assert
        static::assertInstanceOf(ShaHmac256WithRsaSignature::class, $signature);
        static::assertEquals('SHA256withRSA', $signature->getMethod());
        static::assertEquals('1.0', $signature->getVersion());
        static::assertEquals('PRIVATEKEY', $signature->getType());
        static::assertEquals(
            $expected,
            $signature->sign($string, \file_get_contents($privateKeyFile))
        );
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionCode    0
     * @expectedExceptionMessage openssl_sign(): supplied key param cannot be coerced into a private key
     */
    public function testShaHmac256SignatureBadPrivateKey()
    {
        // Setup
        $string         = 'string';
        $privateKeyFile = VirtualRsaKeyPairCredential::badPrivateKey();

        // Test
        $signature = new ShaHmac256WithRsaSignature();

        // Assert
        $signature->sign($string, \file_get_contents($privateKeyFile));
    }
}
