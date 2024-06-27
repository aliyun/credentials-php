<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Filter;

use AlibabaCloud\Credentials\Helper;
use AlibabaCloud\Credentials\Providers\ChainProvider;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualAccessKeyCredential;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class ChainProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Filter
 */
class ChainProviderTest extends TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage No providers in chain
     */
    public function testNoProvides()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('No providers in chain');
        ChainProvider::set();
    }

    public function testSetIni()
    {
        $vf = VirtualAccessKeyCredential::ok();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
        ChainProvider::set(
            ChainProvider::ini()
        );
        self::assertTrue(ChainProvider::hasCustomChain());
        ChainProvider::customProvider(ChainProvider::getDefaultName());
    }

    public function testSetIniEmpty()
    {
        try {
            putenv('ALIBABA_CLOUD_CREDENTIALS_FILE=');
            ChainProvider::set(
                ChainProvider::ini()
            );
            self::assertTrue(ChainProvider::hasCustomChain());
            ChainProvider::customProvider(ChainProvider::getDefaultName());
        } catch (\Exception $exception) {
            self::assertRegExp('/No such file or directory/', $exception->getMessage());
        }
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Credentials file is not readable: /a/c
     */
    public function testSetIniWithDIYFile()
    {
        putenv('ALIBABA_CLOUD_CREDENTIALS_FILE=/a/c');
        ChainProvider::set(
            ChainProvider::ini()
        );
        self::assertTrue(ChainProvider::hasCustomChain());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Credentials file is not readable: /a/c');
        ChainProvider::customProvider(ChainProvider::getDefaultName());
    }

    public function testInOpenBaseDir()
    {
        if (!Helper::isWindows()) {
            $dirs = 'vfs://AlibabaCloud:/home:/Users:/private:/a/b:/d';
        } else {
            $dirs = 'C:\\projects;C:\\Users';
        }

        putenv('ALIBABA_CLOUD_CREDENTIALS_FILE=/a/c');
        ini_set('open_basedir', $dirs);
        self::assertEquals($dirs, ini_get('open_basedir'));
        ChainProvider::set(
            ChainProvider::ini()
        );
        self::assertTrue(ChainProvider::hasCustomChain());
        ChainProvider::customProvider(ChainProvider::getDefaultName());
    }

    public function testDefaultProvider()
    {
        ChainProvider::defaultProvider(ChainProvider::getDefaultName());
        self::assertTrue(true);
    }

    public function testSetEnv()
    {
        ChainProvider::set(
            ChainProvider::env()
        );
        self::assertTrue(ChainProvider::hasCustomChain());
    }

    public function testSetInstance()
    {
        putenv('ALIBABA_CLOUD_ECS_METADATA=role_arn');
        ChainProvider::set(
            ChainProvider::instance()
        );
        self::assertTrue(ChainProvider::hasCustomChain());
        ChainProvider::customProvider(ChainProvider::getDefaultName());
    }

    public function testDefaultFile()
    {
        self::assertStringEndsWith(
            'credentials',
            ChainProvider::getDefaultFile()
        );
        putenv('ALIBABA_CLOUD_PROFILE=default');
    }

    public function testDefaultName()
    {
        putenv('ALIBABA_CLOUD_PROFILE=default1');
        self::assertEquals(
            'default1',
            ChainProvider::getDefaultName()
        );

        putenv('ALIBABA_CLOUD_PROFILE=null');
        self::assertEquals(
            'default',
            ChainProvider::getDefaultName()
        );
    }

    /**
     * @before
     */
    protected function initialize()
    {
        parent::setUp();
        putenv('ALIBABA_CLOUD_ACCESS_KEY_ID=foo');
        putenv('ALIBABA_CLOUD_ACCESS_KEY_SECRET=bar');
    }
}
