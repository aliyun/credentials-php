<?php

namespace AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit;

use AlibabaCloud\Credentials\Helper;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

/**
 * Class HelperTest
 *
 * @package AlibabaCloud\Credentials\Tests\HigherthanorEqualtoVersion7_2\Unit
 */
class HelperTest extends TestCase
{
    public static function testDefault()
    {
        self::assertEquals('default', Helper::env('default', 'default'));
    }

    public static function testEnv()
    {
        self::assertEquals(null, Helper::env('null'));
    }

    public static function testSwitch()
    {
        putenv('TRUE=true');
        self::assertEquals('true', getenv('TRUE'));
        self::assertEquals(true, Helper::env('TRUE'));
        putenv('TRUE=(true)');
        self::assertEquals('(true)', getenv('TRUE'));
        self::assertEquals(true, Helper::env('TRUE'));

        putenv('FALSE=false');
        self::assertEquals('false', getenv('FALSE'));
        self::assertEquals(false, Helper::env('FALSE'));
        putenv('FALSE=(false)');
        self::assertEquals('(false)', getenv('FALSE'));
        self::assertEquals(false, Helper::env('FALSE'));

        putenv('EMPTY=empty');
        self::assertEquals('empty', getenv('EMPTY'));
        self::assertEquals(false, Helper::env('EMPTY'));
        putenv('EMPTY=(empty)');
        self::assertEquals('(empty)', getenv('EMPTY'));
        self::assertEquals('', Helper::env('EMPTY'));

        putenv('NULL=null');
        self::assertEquals('null', getenv('NULL'));
        self::assertEquals(null, Helper::env('NULL'));
        putenv('NULL=(null)');
        self::assertEquals('(null)', getenv('NULL'));
        self::assertEquals(null, Helper::env('NULL'));
    }

    public static function testString()
    {
        putenv('STRING="Alibaba Cloud"');
        self::assertEquals('"Alibaba Cloud"', getenv('STRING'));
        self::assertEquals('Alibaba Cloud', Helper::env('STRING'));

        putenv('STRING="Alibaba Cloud');
        self::assertEquals('"Alibaba Cloud', getenv('STRING'));
        self::assertEquals('"Alibaba Cloud', Helper::env('STRING'));
    }

    public static function testEnvNotEmpty()
    {
        self::assertFalse(Helper::envNotEmpty('ALIBABA_CLOUD_NOT_EXISTS'));
    }

    public static function testEnvNotEmptyException()
    {
        putenv('ALIBABA_CLOUD_NOT_EXISTS=');

        self::assertFalse(Helper::envNotEmpty('ALIBABA_CLOUD_NOT_EXISTS'));
    }

    public static function testInOpenBaseDir()
    {
        if (Helper::isWindows()) {
            $dirs = 'C:\\projects;C:\\Users';
            ini_set('open_basedir', $dirs);
            self::assertEquals($dirs, ini_get('open_basedir'));
        } else {
            $dirs = 'vfs://AlibabaCloud:/home:/Users:/private:/a/b:/d';
            ini_set('open_basedir', $dirs);
            self::assertEquals($dirs, ini_get('open_basedir'));
            self::assertTrue(Helper::inOpenBasedir('/Users/alibabacloud'));
            self::assertTrue(Helper::inOpenBasedir('/private/alibabacloud'));
            self::assertFalse(Helper::inOpenBasedir('/no/permission'));
            self::assertFalse(Helper::inOpenBasedir('/a'));
            self::assertTrue(Helper::inOpenBasedir('/a/b/'));
            self::assertTrue(Helper::inOpenBasedir('/a/b/c'));
            self::assertFalse(Helper::inOpenBasedir('/b'));
            self::assertFalse(Helper::inOpenBasedir('/b/'));
            self::assertFalse(Helper::inOpenBasedir('/x/d/c.txt'));
            self::assertFalse(Helper::inOpenBasedir('/a/b.php'));
        }
    }

    public function testMerge()
    {
        $params = Helper::merge(
            [
                [1 => 'abc'],
                ['a', 'b'],
                [['c', 'd']],
                ['e' => ['a', 'b']],
                ['e' => ['c', 'd']],
            ]
        );

        self::assertEquals(
            [
                0   => 'abc',
                1   => 'a',
                2   => 'b',
                3   => [
                    0 => 'c',
                    1 => 'd',
                ],
                'e' => [
                    0 => 'a',
                    1 => 'b',
                    2 => 'c',
                    3 => 'd',
                ],
            ],
            $params
        );
    }

    /**
     * @throws ReflectionException
     */
    public function testGetsHomeDirectoryForWindowsUser()
    {
        putenv('HOME=');
        putenv('HOMEDRIVE=C:');
        putenv('HOMEPATH=\\Users\\Alibaba');
        $ref    = new ReflectionClass(Helper::class);
        $method = $ref->getMethod('getHomeDirectory');
        $method->setAccessible(true);
        $this->assertEquals('C:\\Users\\Alibaba', $method->invoke(null));
    }

    /**
     * @depends testGetsHomeDirectoryForWindowsUser
     * @throws ReflectionException
     */
    public function testGetsHomeDirectoryForLinuxUser()
    {
        putenv('HOME=/root');
        putenv('HOMEDRIVE=');
        putenv('HOMEPATH=');
        $ref    = new ReflectionClass(Helper::class);
        $method = $ref->getMethod('getHomeDirectory');
        $method->setAccessible(true);
        $this->assertEquals('/root', $method->invoke(null));
    }
}
