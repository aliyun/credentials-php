<?php

namespace AlibabaCloud\Credentials\Tests\Unit\Filter;

use AlibabaCloud\Credentials\Utils\Helper;
use AlibabaCloud\Credentials\Providers\ProfileCredentialsProvider;
use AlibabaCloud\Credentials\Tests\Unit\Ini\VirtualAccessKeyCredential;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use InvalidArgumentException;

/**
 * Class ProfileCredentialsProviderTest
 *
 * @package AlibabaCloud\Credentials\Tests\Unit\Filter
 */
class ProfileCredentialsProviderTest extends TestCase
{

    public function testSetIni()
    {
        $vf = VirtualAccessKeyCredential::ok();
        putenv("ALIBABA_CLOUD_CREDENTIALS_FILE=$vf");
    }

    public function testSetIniEmpty()
    {
        try {
            putenv('ALIBABA_CLOUD_CREDENTIALS_FILE=');
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
