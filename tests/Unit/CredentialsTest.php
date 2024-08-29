<?php

namespace AlibabaCloud\Credentials\Tests\Unit;

use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\ChainProvider;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use RuntimeException;

/**
 * Class CredentialsTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit
 */
class CredentialsTest extends TestCase
{

    public function testALL()
    {
        self::assertEquals(true, is_array(Credentials::all()));
    }

    /**
     * @throws ReflectionException
     */
    public function testGet()
    {
        Credentials::get();
        Credentials::get(ChainProvider::getDefaultName());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Credential 'no' not found
     * @throws ReflectionException
     */
    public function testGetNotFound()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Credential 'no' not found");
        Credentials::get('no');
    }

    /**
     * @throws ReflectionException
     */
    public function testLoad()
    {
        Credentials::flush();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Credential 'default' not found");
        Credentials::get(ChainProvider::getDefaultName());

        Credentials::flush();
        ChainProvider::flush();
        Credentials::get(ChainProvider::getDefaultName());
        self::assertTrue(true);
    }
}
