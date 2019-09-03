<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Filter;

use ReflectionException;
use PHPUnit\Framework\TestCase;
use AlibabaCloud\Credentials\Credentials;
use AlibabaCloud\Credentials\Providers\ChainProvider;

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
        Credentials::get('no');
    }

    /**
     * @throws ReflectionException
     */
    public function testLoad()
    {
        Credentials::flush();
        Credentials::get(ChainProvider::getDefaultName());

        Credentials::flush();
        ChainProvider::flush();
        Credentials::get(ChainProvider::getDefaultName());
    }
}
