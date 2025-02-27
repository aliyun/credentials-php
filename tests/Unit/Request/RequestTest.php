<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Request;

use AlibabaCloud\Credentials\Request\Request;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualRsaKeyPairCredential;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

class RequestTest extends TestCase
{

    public function testCommonOptions()
    {
        $options = Request::commonOptions();
        // Assert
        static::assertFalse($options['http_errors']);
        static::assertEquals(5, $options['connect_timeout']);
        static::assertEquals(5, $options['read_timeout']);
        static::assertTrue(isset($options['headers']['User-Agent']));
    }

    public function testUuid()
    {
        // Assert
        static::assertNotEquals(Request::uuid("test"), Request::uuid("test"));
    }

    public function testSignString()
    {
        $method = 'get';
        $request = [
            'type' => 'access_key',
            'accessKeyId' => 'foo',
            'accessKeySecret' => 'bar',
        ];

        // Assert
        static::assertEquals("get&%2F&accessKeyId%3Dfoo%26accessKeySecret%3Dbar%26type%3Daccess_key", Request::signString($method, $request));
    }

    public function testShaHmac1Signature()
    {
        // Setup
        $string = 'this is a ShaHmac1 test.';
        $accessKeySecret = 'accessKeySecret';
        $expected = 'PEr0Vp78B4Fslzf54dzdXD4Qt3E=';

        // Assert
        static::assertEquals($expected, Request::shaHmac1sign($string, $accessKeySecret));
    }

    public function testShaHmac256Signature()
    {
        // Setup
        $string = 'this is a ShaHmac256 test.';
        $accessKeySecret = 'accessKeySecret';
        $expected = 'v1Kg2HYGWRaLsRu2iXkAZu3R7vDh0txyYHs48HVxkeA=';

        // Assert
        static::assertEquals($expected, Request::shaHmac256sign($string, $accessKeySecret));
    }

    public function testShaHmac256WithRsaSignature()
    {
        // Setup
        $string = 'string';
        $privateKeyFile = VirtualRsaKeyPairCredential::privateKeyFileUrl();
        $expected =
            'gjWgwRf/BjOYbjrPleU9qzNrZXNO+Z9aiwxmbBj1TPF2/PEOjBy5/YSk+GfL2GGg5pkupzrKiG+4FQ4r+EjeQcdByRDv1x1eBrQHwAbieKmjLc1++vJWQQpSKJykMl5dRzADUwsXYzvCCvVCIXjYZJNsrdt/0G+gaRVX7oelAX+d1MiTjRam7Ugzxcr1nELz2dc3DOyhXqCw8loNtsFVNcrDC5B/urx4eYdAFWRYVbORdTTgPdOF/gNJOWPqQgvFQsICJpScwIXP2OntCjYj8EBGNafBK3bCe4jxHwtxBA72PmuJ/ZHxUqSstwbcVk5S40PlRIhqtrfn6ajxYk41SQ==';

        // Assert
        static::assertEquals($expected, Request::shaHmac256WithRsasign($string, \file_get_contents($privateKeyFile)));
    }

    /**
     * @expectedException        \InvalidArgumentException
     * @expectedExceptionCode    0
     * @expectedExceptionMessage openssl_sign(): supplied key param cannot be coerced into a private key
     */
    public function testShaHmac256WithRsaSignatureBadPrivateKey()
    {
        // Setup
        $string = 'string';
        $privateKeyFile = VirtualRsaKeyPairCredential::badPrivateKey();

        $this->expectException(InvalidArgumentException::class);
        if (method_exists($this, 'expectExceptionMessageMatches')) {
            $this->expectExceptionMessageMatches('/supplied key param cannot be coerced into a private key/i');
        } elseif (method_exists($this, 'expectExceptionMessageRegExp')) {
            $this->expectExceptionMessageRegExp('/supplied key param cannot be coerced into a private key/i');
        }

        Request::shaHmac256WithRsasign($string, \file_get_contents($privateKeyFile));
    }
}